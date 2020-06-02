<?php

namespace Epubli\PermissionBundle\Service;

use DateInterval;
use DateTime;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;

/**
 * Class JsonWebTokenMockCreator
 * @package Epubli\PermissionBundle\Service
 */
class JsonWebTokenMockCreator
{
    /** @var PermissionDiscovery */
    private $permissionDiscovery;

    /** @var string|null */
    private $headerForAllPermissions;

    /** @var string|null */
    private $headerForThisMicroservice;

    public function __construct(PermissionDiscovery $permissionDiscovery)
    {
        $this->permissionDiscovery = $permissionDiscovery;
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

        try {
            $response = (new Client())->get(
                'http://user/api/roles/permissions',
                [
                    'header' => [
                        'HTTP_AUTHORIZATION' => $this->getMockAuthorizationHeader(
                            ['user.permission.read']
                        )
                    ]
                ]
            );

            $json = json_decode($response->getBody(), true);

            $permissionKeys = array_column($json['hydra:member'], 'key');
        } catch (ServerException | ClientException $exp) {
            $statusCode = $exp->getResponse()->getStatusCode();
            throw new Exception('Could not get all permissions. Returned status code: ' . $statusCode);
        }

        $this->headerForAllPermissions = $this->getMockAuthorizationHeader($permissionKeys);
        return $this->headerForAllPermissions;
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
     * @throws \ReflectionException
     */
    public function getMockAuthorizationHeaderForThisMicroservice(): ?string
    {
        if ($this->headerForThisMicroservice !== null) {
            return $this->headerForThisMicroservice;
        }

        $permissionKeys = $this->permissionDiscovery->getAllPermissionKeys();

        $this->headerForThisMicroservice = $this->getMockAuthorizationHeader($permissionKeys);
        return $this->headerForThisMicroservice;
    }
}