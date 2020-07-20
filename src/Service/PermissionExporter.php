<?php

namespace Epubli\PermissionBundle\Service;

use Epubli\PermissionBundle\DependencyInjection\Configuration;
use Epubli\PermissionBundle\PermissionExportException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use ReflectionException;

class PermissionExporter
{
    /** @var PermissionDiscovery */
    private $permissionDiscovery;

    /** @var CustomPermissionDiscovery */
    private $customPermissionDiscovery;

    /** @var JWTMockCreator */
    private $jwtMockCreator;

    /** @var Client */
    private $client;

    /** @var string */
    private $path;

    /**
     * PermissionExporter constructor.
     * @param Client $client
     * @param string $path
     * @param PermissionDiscovery $permissionDiscovery
     * @param CustomPermissionDiscovery $customPermissionDiscovery
     * @param JWTMockCreator $jwtMockCreator
     */
    public function __construct(
        Client $client,
        string $path,
        PermissionDiscovery $permissionDiscovery,
        CustomPermissionDiscovery $customPermissionDiscovery,
        JWTMockCreator $jwtMockCreator
    ) {
        $this->client = $client;
        $this->path = $path;
        $this->permissionDiscovery = $permissionDiscovery;
        $this->customPermissionDiscovery = $customPermissionDiscovery;
        $this->jwtMockCreator = $jwtMockCreator;
    }

    /**
     * @return int the number of permissions which were exported
     * @throws ReflectionException
     * @throws PermissionExportException
     */
    public function export(): int
    {
        if ($this->permissionDiscovery->getMicroserviceName() === Configuration::DEFAULT_MICROSERVICE_NAME) {
            throw new PermissionExportException(
                'Please make sure to set the name of your microservice in config/packages/epubli_permission.yaml!'
            );
        }

        $permissions = $this->getAllPermissions();

        if (empty($permissions)) {
            return 0;
        }

        $this->postPermissions($permissions);

        return count($permissions);
    }

    /**
     * @return array
     * @throws ReflectionException
     * @throws PermissionExportException
     */
    private function getAllPermissions(): array
    {
        $entityPermissions = $this->permissionDiscovery->getAllPermissionKeysWithDescriptions();
        $customPermissions = $this->customPermissionDiscovery->getAllPermissionKeysWithDescriptions();

        $intersection = array_intersect(
            array_column($entityPermissions, 'key'),
            array_column($customPermissions, 'key')
        );
        if (!empty($intersection)) {
            throw new PermissionExportException(
                'Please make sure to not have any custom permissions with the same key '
                . 'as the ones which are automatically generated! The following permissions are already in use:\n'
                . implode('\n', $intersection)
            );
        }

        return array_merge($entityPermissions, $customPermissions);
    }

    /**
     * @param array $permissions
     * @throws PermissionExportException
     */
    private function postPermissions(array $permissions): void
    {
        try {
            $response = $this->client->post(
                $this->path,
                [
                    'json' => [
                        'microservice' => $this->permissionDiscovery->getMicroserviceName(),
                        'permissions' => $permissions,
                    ],
                    'headers' => [
                        'AUTHORIZATION' => $this->jwtMockCreator->getMockAuthorizationHeader(
                            ['user.permission.create_permissions']
                        )
                    ]
                ]
            );

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 204) {
                throw new PermissionExportException(
                    'Expected status code 204. Received instead: ' . $statusCode
                );
            }
        } catch (ServerException | ClientException $exp) {
            $statusCode = $exp->getResponse()->getStatusCode();
            $body = $exp->getResponse()->getBody();

            throw new PermissionExportException('Status Code: ' . $statusCode . '\nBody: ' . $body);
        }
    }
}