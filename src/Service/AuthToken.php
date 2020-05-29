<?php

namespace Epubli\PermissionBundle\Service;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class AuthToken
 * @package Epubli\PermissionBundle\Service
 */
class AuthToken
{
    public const ATTRIBUTE_KEY = 'epubli_permission_token_payload';

    /** @var string|null */
    private $jti;

    /** @var string[] */
    private $permissionKeys = [];

    /** @var bool */
    private $isValid = false;

    /** @var bool */
    private $isRefreshToken = false;

    public function __construct(RequestStack $requestStack)
    {
        $request = $requestStack->getCurrentRequest();
        if ($request === null) {
            return;
        }
        $payload = $request->attributes->get(self::ATTRIBUTE_KEY);
        if ($payload === null) {
            return;
        }

        if (!isset($payload['jti'], $payload['roles'])) {
            return;
        }

        $this->jti = $payload['jti'];
        $this->permissionKeys = $payload['permissions'] ?? [];
        $this->isRefreshToken = in_array('refresh_token', $payload['roles']);
        $this->isValid = true;
    }

    public function isValid(): bool
    {
        return $this->isValid;
    }

    public function isRefreshToken(): bool
    {
        return $this->isValid && $this->isRefreshToken;
    }

    public function isAccessToken(): bool
    {
        return $this->isValid && !$this->isRefreshToken;
    }

    public function getJTI(): ?string
    {
        return $this->jti;
    }

    /**
     * @param string $permissionKey
     * @return bool
     */
    public function hasPermissionKey(string $permissionKey): bool
    {
        return in_array($permissionKey, $this->permissionKeys, true);
    }
}