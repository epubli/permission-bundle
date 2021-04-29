<?php

namespace Epubli\PermissionBundle\Service;

use DateInterval;
use DateTime;
use GuzzleHttp\Cookie\CookieJar;
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
     * Returns a json web token which has access to everything.
     * This token can only be used internally
     * because the json web token is not signed correctly.
     * @return string
     */
    public function createJsonWebTokenWithAccessToEverything(): string
    {
        return $this->createJsonWebToken([], -1, true);
    }

    /**
     * Returns a json web token which contains only the specified permission keys
     * @param string[] $permissionKeys
     * @param int $userId
     * @param bool $accessToEverything
     * @return string
     */
    public function createJsonWebToken(
        array $permissionKeys,
        int $userId = -1,
        bool $accessToEverything = false
    ): string {
        $mockAccessTokenPayload = [
            'iss' => 'https://epubli.de',
            'sub' => '-1',
            'user_id' => $userId,
            'jti' => '-1',
            'exp' => (new DateTime())->add(new DateInterval('PT60M'))->getTimestamp(),
            'type' => 'access_token',
            'permissions' => $permissionKeys,
        ];

        if ($accessToEverything) {
            $mockAccessTokenPayload['hasAccessToEverything'] = true;
        }

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