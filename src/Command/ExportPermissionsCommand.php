<?php

namespace Epubli\PermissionBundle\Command;

use DateInterval;
use DateTime;
use Epubli\PermissionBundle\Service\AuthPermissionDiscovery;
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

    /** @var AuthPermissionDiscovery */
    private $permissionDiscovery;

    public function __construct(AuthPermissionDiscovery $permissionDiscovery)
    {
        parent::__construct();
        $this->permissionDiscovery = $permissionDiscovery;
    }

    protected function configure(): void
    {
        $this->setDescription('Exports permissions and imports them to the user microservice.')
            ->setHelp('Exports all permissions for this microservice and imports them to the user microservice.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
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
                        'HTTP_AUTHORIZATION' => 'Bearer ' . $this->getMockAccessToken()
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

    private function getMockAccessToken()
    {
        $mockAccessTokenPayload = [
            'iss' => 'https://epubli.de',
            'sub' => '-1',
            'user_id' => -1,
            'jti' => '-1',
            'exp' => (new DateTime())->add(new DateInterval('PT60M'))->getTimestamp(),
            'roles' => ['access_token'],
            'permissions' => ['user.role.create_permissions'],
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
}