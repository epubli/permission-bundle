<?php

namespace Epubli\PermissionBundle\Service;

use Epubli\PermissionBundle\EndpointWithPermission;
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

    /** @var RequestStack */
    private $requestStack;

    /** @var bool */
    private $isInitialized = false;

    /** @var string|null */
    private $jti;

    /** @var string|null */
    private $userId;

    /** @var string[] */
    private $permissionKeys = [];

    /** @var bool */
    private $exists = false;

    /**
     * AccessToken constructor.
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
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
        $this->permissionKeys = $payload['permissions'] ?? [];
        $this->exists = true;
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
     * @param string $permissionKey
     * @return bool
     */
    public function hasPermissionKey(string $permissionKey): bool
    {
        if (!$this->isInitialized) {
            $this->initialize();
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