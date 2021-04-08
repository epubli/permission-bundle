<?php

namespace Epubli\PermissionBundle\Service;

use Epubli\PermissionBundle\EndpointWithPermission;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class AccessToken
 * @package Epubli\PermissionBundle\Service
 */
class AccessToken
{
    /** @var string */
    public const ACCESS_TOKEN_COOKIE_NAME = 'access_token';

    /** @var Client */
    private $client;

    /** @var string */
    private $path;

    /** @var string */
    private $permissionKeyForAggregatedPermissionsRoute;

    /** @var bool */
    private $isTestEnvironment;

    /** @var RequestStack */
    private $requestStack;

    /** @var JWTMockCreator */
    private $jwtMockCreator;

    /** @var bool */
    private $isInitialized = false;

    /** @var string|null */
    private $jti;

    /** @var int|null */
    private $userId;

    /** @var int[] */
    private $roleIds = [];

    /** @var string[] */
    private $permissionKeys = [];

    /** @var bool */
    private $hasAccessToEverything = false;

    /** @var bool */
    private $exists = false;

    /**
     * AccessToken constructor.
     * @param Client $client
     * @param string $path
     * @param string $permissionKeyForAggregatedPermissionsRoute
     * @param RequestStack $requestStack
     * @param JWTMockCreator $jwtMockCreator
     */
    public function __construct(
        Client $client,
        string $path,
        string $permissionKeyForAggregatedPermissionsRoute,
        bool $isTestEnvironment,
        RequestStack $requestStack,
        JWTMockCreator $jwtMockCreator
    ) {
        $this->client = $client;
        $this->path = $path;
        $this->permissionKeyForAggregatedPermissionsRoute = $permissionKeyForAggregatedPermissionsRoute;
        $this->isTestEnvironment = $isTestEnvironment;
        $this->requestStack = $requestStack;
        $this->jwtMockCreator = $jwtMockCreator;
    }

    /**
     * This method is called before any other method is called.
     */
    private function initialize(): void
    {
        $this->isInitialized = true;

        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            return;
        }
        $payload = self::getPayloadFromCookie($request);
        if ($payload === null) {
            return;
        }

        if (!isset($payload['jti'], $payload['user_id'])) {
            return;
        }

        $this->jti = $payload['jti'];
        $this->userId = $payload['user_id'];
        $this->roleIds = $payload['role_ids'] ?? [];
        $this->permissionKeys = $payload['permissions'] ?? [];
        $this->hasAccessToEverything = $payload['hasAccessToEverything'] ?? false;
        $this->exists = true;

        $this->addPermissionKeysOfUser();
    }

    /**
     * @param Request $request
     * @return array|null
     */
    private static function getPayloadFromCookie(Request $request): ?array
    {
        $accessToken = $request->cookies->get(self::ACCESS_TOKEN_COOKIE_NAME);

        if ($accessToken === null) {
            return null;
        }

        $encodedPayload = explode('.', $accessToken)[1] ?? '';
        return json_decode(base64_decode($encodedPayload), true);
    }

    private function addPermissionKeysOfUser(): void
    {
        if ($this->userId === null || $this->userId <= 0 || $this->isTestEnvironment) {
            return;
        }

        try {
            $response = $this->client->get(
                str_replace('{user_id}', $this->userId, $this->path),
                [
                    'cookies' => $this->jwtMockCreator->createCookieJar(
                        $this->jwtMockCreator->createJsonWebToken(
                            [$this->permissionKeyForAggregatedPermissionsRoute]
                        ),
                        $this->client->getConfig('base_uri') ?? $this->path
                    )
                ]
            );

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                throw new RuntimeException(
                    'Expected status code 200. Received instead: ' . $statusCode
                );
            }
        } catch (ServerException | ClientException $exp) {
            $statusCode = $exp->getResponse()->getStatusCode();
            $body = $exp->getResponse()->getBody();

            throw new RuntimeException('Status Code: ' . $statusCode . '\nBody: ' . $body);
        }
        $this->permissionKeys = array_merge($this->permissionKeys, json_decode($response->getBody(), true));
    }

    /**
     * Returns whether or not the access token exists
     * @return bool
     */
    public function exists(): bool
    {
        if (!$this->isInitialized) {
            $this->initialize();
        }
        return $this->exists;
    }

    /**
     * If the token exists then this won't be null.
     * @return string|null
     */
    public function getJTI(): ?string
    {
        if (!$this->isInitialized) {
            $this->initialize();
        }
        return $this->jti;
    }

    /**
     * If the token exists then this won't be null.
     * @return int|null
     */
    public function getUserId(): ?int
    {
        if (!$this->isInitialized) {
            $this->initialize();
        }
        return $this->userId;
    }

    /**
     * @return int[]
     */
    public function getRoleIds(): array
    {
        if (!$this->isInitialized) {
            $this->initialize();
        }
        return $this->roleIds;
    }

    /**
     * @param string $permissionKey
     * @return bool
     */
    public function hasPermissionKey(string $permissionKey): bool
    {
        if (!$this->isInitialized) {
            $this->initialize();
        }
        if ($this->hasAccessToEverything) {
            return true;
        }
        return in_array($permissionKey, $this->permissionKeys, true);
    }

    /**
     * @param string[] $permissionKeys
     * @param bool $testForAlternatives
     * If set to true then permissions will be converted to their alternatives.
     * E.g. user.user.create --> user.user.create.self
     * @return bool
     */
    public function hasPermissionKeys(array $permissionKeys, bool $testForAlternatives = false): bool
    {
        if (!$this->isInitialized) {
            $this->initialize();
        }
        if ($this->hasAccessToEverything) {
            return true;
        }

        $missingPermissionKeys = $this->getMissingPermissionKeys($permissionKeys);
        if ($testForAlternatives) {
            return $this->hasPermissionKeys(
                array_map(
                    static function ($item) {
                        return $item . EndpointWithPermission::SELF_PERMISSION;
                    },
                    $missingPermissionKeys
                )
            );
        }

        return empty($missingPermissionKeys);
    }

    /**
     * @param string[] $permissionKeys
     * @return string[]
     */
    public function getMissingPermissionKeys(array $permissionKeys): array
    {
        if (!$this->isInitialized) {
            $this->initialize();
        }
        return array_diff($permissionKeys, $this->permissionKeys);
    }
}