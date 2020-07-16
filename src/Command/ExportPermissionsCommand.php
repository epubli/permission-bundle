<?php

namespace Epubli\PermissionBundle\Command;

use Epubli\PermissionBundle\DependencyInjection\Configuration;
use Epubli\PermissionBundle\Service\CustomPermissionDiscovery;
use Epubli\PermissionBundle\Service\JWTMockCreator;
use Epubli\PermissionBundle\Service\PermissionDiscovery;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ExportPermissionsCommand
 * @package Epubli\PermissionBundle\Command
 */
class ExportPermissionsCommand extends Command
{
    protected static $defaultName = 'epubli:export-permissions';

    /** @var PermissionDiscovery */
    private $permissionDiscovery;

    /** @var CustomPermissionDiscovery */
    private $customPermissionDiscovery;

    /** @var JWTMockCreator */
    private $jwtMockCreator;

    /**
     * ExportPermissionsCommand constructor.
     * @param PermissionDiscovery $permissionDiscovery
     * @param CustomPermissionDiscovery $customPermissionDiscovery
     * @param JWTMockCreator $jwtMockCreator
     */
    public function __construct(
        PermissionDiscovery $permissionDiscovery,
        CustomPermissionDiscovery $customPermissionDiscovery,
        JWTMockCreator $jwtMockCreator
    ) {
        parent::__construct();
        $this->permissionDiscovery = $permissionDiscovery;
        $this->customPermissionDiscovery = $customPermissionDiscovery;
        $this->jwtMockCreator = $jwtMockCreator;
    }

    protected function configure(): void
    {
        $this->setDescription('Exports permissions and imports them to the user microservice.')
            ->setHelp('Exports all permissions for this microservice and imports them to the user microservice.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->permissionDiscovery->getMicroserviceName() === Configuration::DEFAULT_MICROSERVICE_NAME) {
            $output->writeln(
                'ERROR\n'
                . 'Please make sure to set the name of your microservice in config/packages/epubli_permission.yaml!'
            );
            return 1;
        }

        $entityPermissions = $this->permissionDiscovery->getAllPermissionKeysWithDescriptions();
        $customPermissions = $this->customPermissionDiscovery->getAllPermissionKeysWithDescriptions();

        $intersection = array_intersect_key($entityPermissions, $customPermissions);
        if (!empty($intersection)) {
            $output->writeln(
                'ERROR\n'
                . 'Please make sure to not have any custom permissions with the same key '
                . 'as the ones which are automatically generated! The following permissions are already in use:\n'
                . implode(
                    '\n',
                    array_map(
                        static function (string $permission) {
                            return $permission['key'];
                        },
                        $intersection
                    )
                )
            );
            return 1;
        }

        $permissions = array_merge($entityPermissions, $customPermissions);

        if (empty($permissions)) {
            $output->writeln('No permissions found! Nothing to export!');
            return 0;
        }

        $output->writeln(count($permissions) . ' permissions found.');

        try {
            $response = (new Client())->post(
                'http://user/api/roles/permissions/import',
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
                $output->writeln(
                    'ERROR\n'
                    . 'Expected status code 204. Received instead: ' . $statusCode
                );
                return 1;
            }
        } catch (ServerException | ClientException $exp) {
            $statusCode = $exp->getResponse()->getStatusCode();
            $body = $exp->getResponse()->getBody();

            $output->writeln('Error Status Code: ' . $statusCode);
            $output->writeln('Error: ' . $body);
            return 1;
        }

        $output->writeln('Successfully exported.');
        return 0;
    }
}