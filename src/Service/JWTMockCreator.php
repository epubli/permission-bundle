<?php

namespace Epubli\PermissionBundle\Service;

use DateInterval;
use DateTime;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use ReflectionException;
use Symfony\Component\HttpFoundation\Cookie;

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
    private $jwtForAllPermissions;

    /** @var string|null */
    private $jwtForThisMicroservice;

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
     * For unit tests
     * @param string $jsonWebToken
     * @return \Symfony\Component\BrowserKit\Cookie
     */
    public function createBrowserKitCookie(string $jsonWebToken): \Symfony\Component\BrowserKit\Cookie
    {
        return new \Symfony\Component\BrowserKit\Cookie(AccessToken::ACCESS_TOKEN_COOKIE_NAME, $jsonWebToken);
    }

    /**
     * For the constructor of the request class
     * @param string $jsonWebToken
     * @return Cookie
     */
    public function createHTTPCookie(string $jsonWebToken): Cookie
    {
        return Cookie::create(AccessToken::ACCESS_TOKEN_COOKIE_NAME, $jsonWebToken);
    }

    /**
     * For guzzle
     * @param string $jsonWebToken
     * @param string $url
     * @return CookieJar
     */
    public function createCookieJar(string $jsonWebToken, string $url): CookieJar
    {
        $domain = parse_url($url, PHP_URL_HOST);

        return CookieJar::fromArray(
            [
                AccessToken::ACCESS_TOKEN_COOKIE_NAME => $jsonWebToken
            ],
            $domain
        );
    }

    /**
     * Returns a json web token which contains permissions for everything
     * @return string
     * @throws Exception
     */
    public function createJsonWebTokenForAllPermissions(): ?string
    {
        if ($this->jwtForAllPermissions !== null) {
            return $this->jwtForAllPermissions;
        }

        $client = new Client(['base_uri' => 'http://user']);
        $jwt = $this->createJsonWebToken(['user.permission.read']);
        $permissionKeys = $this->getAllPermissionKeys($client, $jwt, '/api/roles/permissions?page=1');

        $this->jwtForAllPermissions = $this->createJsonWebToken($permissionKeys);
        return $this->jwtForAllPermissions;
    }

    /**
     * @param Client $client
     * @param string $jsonWebToken
     * @param string $path
     * @return string[]
     * @throws Exception
     */
    private function getAllPermissionKeys(Client $client, string $jsonWebToken, string $path): array
    {
        try {
            $response = $client->get(
                $path,
                ['cookies' => $this->createCookieJar($jsonWebToken, $client->getConfig('base_uri') . $path)]
            );

            $json = json_decode($response->getBody(), true);

            $permissionKeys = array_column($json['hydra:member'], 'key');

            if (isset($json['hydra:view']['hydra:next'])) {
                $nextPath = $json['hydra:view']['hydra:next'];
                $permissionKeys = array_merge(
                    $permissionKeys,
                    $this->getAllPermissionKeys($client, $jsonWebToken, $nextPath)
                );
            }

            return $permissionKeys;
        } catch (ServerException | ClientException $exp) {
            $statusCode = $exp->getResponse()->getStatusCode();
            throw new Exception('Could not get all permissions. Returned status code: ' . $statusCode);
        }
    }

    /**
     * Returns a json web token which contains only the specified permission keys
     * @param string[] $permissionKeys
     * @param int $userId
     * @return string
     */
    public function createJsonWebToken(array $permissionKeys, int $userId = -1): string
    {
        $mockAccessTokenPayload = [
            'iss' => 'https://epubli.de',
            'sub' => '-1',
            'user_id' => $userId,
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
     * Returns a json web token which contains permissions to everything in this microservice
     * @return string
     * @throws ReflectionException
     */
    public function createJsonWebTokenForThisMicroservice(): ?string
    {
        if ($this->jwtForThisMicroservice !== null) {
            return $this->jwtForThisMicroservice;
        }

        $permissionKeys = $this->permissionDiscovery->getAllPermissionKeys();
        $permissionKeys = array_merge($permissionKeys, $this->customPermissionDiscovery->getAllPermissionKeys());

        $this->jwtForThisMicroservice = $this->createJsonWebToken($permissionKeys);
        return $this->jwtForThisMicroservice;
    }
}