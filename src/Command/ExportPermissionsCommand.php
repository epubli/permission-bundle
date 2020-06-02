<?php

namespace Epubli\PermissionBundle\Command;

use Epubli\PermissionBundle\DependencyInjection\Configuration;
use Epubli\PermissionBundle\Service\JsonWebTokenMockCreator;
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

    /** @var JsonWebTokenMockCreator */
    private $mockJsonWebToken;

    public function __construct(PermissionDiscovery $permissionDiscovery, JsonWebTokenMockCreator $mockJsonWebToken)
    {
        parent::__construct();
        $this->permissionDiscovery = $permissionDiscovery;
        $this->mockJsonWebToken = $mockJsonWebToken;
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
                'Please make sure to set the name of your microservice in config/packages/epubli_permission.yaml!'
            );
            return 1;
        }

        $permissions = $this->permissionDiscovery->getAllPermissionKeysWithDescriptions();
        $permissions = $this->removeDuplicates($permissions);

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
                    'header' => [
                        'HTTP_AUTHORIZATION' => $this->mockJsonWebToken->getMockAuthorizationHeader(
                            ['user.role.create_permissions']
                        )
                    ]
                ]
            );

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 204) {
                $output->writeln('Expected status code 204 exported. Received instead: ' . $statusCode);
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

    /**
     * @param array $permissions
     * @return array
     */
    private function removeDuplicates(array $permissions): array
    {
        $permissionKeys = [];
        $newPermissions = [];

        foreach ($permissions as $permission) {
            if (in_array($permission['key'], $permissionKeys)) {
                continue;
            }
            $permissionKeys[] = $permission['key'];
            $newPermissions[] = $permission;
        }

        return $newPermissions;
    }
}