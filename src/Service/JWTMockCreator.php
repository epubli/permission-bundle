<?php

namespace Epubli\PermissionBundle\Service;

use DateInterval;
use DateTime;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use ReflectionException;

/**
 * Class JsonWebTokenMockCreator
 * @package Epubli\PermissionBundle\Service
 */
class JWTMockCreator
{
    /** @var PermissionDiscovery */
    private $permissionDiscovery;

    /** @var CustomPermissionDiscovery */
    private $customPermissionDiscovery;

    /** @var string|null */
    private $headerForAllPermissions;

    /** @var string|null */
    private $headerForThisMicroservice;

    /**
     * JWTMockCreator constructor.
     * @param PermissionDiscovery $permissionDiscovery
     * @param CustomPermissionDiscovery $customPermissionDiscovery
     */
    public function __construct(
        PermissionDiscovery $permissionDiscovery,
        CustomPermissionDiscovery $customPermissionDiscovery
    ) {
        $this->permissionDiscovery = $permissionDiscovery;
        $this->customPermissionDiscovery = $customPermissionDiscovery;
    }

    /**
     * Returns an authorization header with a token which contains permissions for everything
     * @return string
     * @throws Exception
     */
    public function getMockAuthorizationHeaderForAllPermissions(): ?string
    {
        if ($this->headerForAllPermissions !== null) {
            return $this->headerForAllPermissions;
        }

        $client = new Client(['base_uri' => 'http://user']);
        $header = $this->getMockAuthorizationHeader(['user.permission.read']);
        $permissionKeys = $this->getAllPermissionKeys($client, $header, '/api/roles/permissions?page=1');

        $this->headerForAllPermissions = $this->getMockAuthorizationHeader($permissionKeys);
        return $this->headerForAllPermissions;
    }

    /**
     * @param Client $client
     * @param string $header
     * @param string $path
     * @return string[]
     * @throws Exception
     */
    private function getAllPermissionKeys(Client $client, string $header, string $path): array
    {
        try {
            $response = $client->get(
                $path,
                [
                    'headers' => [
                        'AUTHORIZATION' => $header
                    ]
                ]
            );

            $json = json_decode($response->getBody(), true);

            $permissionKeys = array_column($json['hydra:member'], 'key');

            if (isset($json['hydra:view']['hydra:next'])) {
                $nextPath = $json['hydra:view']['hydra:next'];
                $permissionKeys = array_merge(
                    $permissionKeys,
                    $this->getAllPermissionKeys($client, $header, $nextPath)
                );
            }

            return $permissionKeys;
        } catch (ServerException | ClientException $exp) {
            $statusCode = $exp->getResponse()->getStatusCode();
            throw new Exception('Could not get all permissions. Returned status code: ' . $statusCode);
        }
    }

    /**
     * Returns an authorization header with a token which contains only the specified permission keys
     * @param string[] $permissionKeys
     * @return string
     */
    public function getMockAuthorizationHeader(array $permissionKeys): string
    {
        return 'Bearer ' . $this->getMockAccessToken($permissionKeys);
    }

    /**
     * @param string[] $permissionKeys
     * @return string
     */
    private function getMockAccessToken(array $permissionKeys): string
    {
        $mockAccessTokenPayload = [
            'iss' => 'https://epubli.de',
            'sub' => '-1',
            'user_id' => -1,
            'jti' => '-1',
            'exp' => (new DateTime())->add(new DateInterval('PT60M'))->getTimestamp(),
            'roles' => ['access_token'],
            'permissions' => $permissionKeys,
        ];

        return implode(
            '.',
            array_map(
                static function ($item) {
                    return base64_encode(json_encode($item));
                },
                ['empty', $mockAccessTokenPayload, 'empty']
            )
        );
    }

    /**
     * Returns an authorization header with a token which contains permissions to everything in this microservice
     * @return string
     * @throws ReflectionException
     */
    public function getMockAuthorizationHeaderForThisMicroservice(): ?string
    {
        if ($this->headerForThisMicroservice !== null) {
            return $this->headerForThisMicroservice;
        }

        $permissionKeys = $this->permissionDiscovery->getAllPermissionKeys();
        $permissionKeys = array_merge($permissionKeys, $this->customPermissionDiscovery->getAllPermissionKeys());

        $this->headerForThisMicroservice = $this->getMockAuthorizationHeader($permissionKeys);
        return $this->headerForThisMicroservice;
    }
}