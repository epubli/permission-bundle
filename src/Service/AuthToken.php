<?php

namespace Epubli\PermissionBundle\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class AuthToken
 * @package Epubli\PermissionBundle\Service
 */
class AuthToken
{
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
    private $isValid = false;

    /** @var bool */
    private $isRefreshToken = false;

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
        $payload = self::getPayloadFromHeader($request);
        if ($payload === null) {
            return;
        }

        if (!isset($payload['jti'], $payload['roles'], $payload['user_id'])) {
            return;
        }

        $this->jti = $payload['jti'];
        $this->userId = $payload['user_id'];
        $this->permissionKeys = $payload['permissions'] ?? [];
        $this->isRefreshToken = in_array('refresh_token', $payload['roles']);
        $this->isValid = true;
    }

    /**
     * @param Request $request
     * @return array|null
     */
    private static function getPayloadFromHeader(Request $request): ?array
    {
        $header = $request->headers->get('Authorization');
        if (empty($header)) {
            $header = $request->headers->get('authorization');
        }
        if (empty($header)) {
            return null;
        }
        $token = substr($header, strlen('Bearer '));

        $encodedPayload = explode('.', $token)[1] ?? '';
        return json_decode(base64_decode($encodedPayload), true);
    }

    /**
     * Returns true if the token exists and every required field is present
     * @return bool
     */
    public function isValid(): bool
    {
        if (!$this->isInitialized) {
            $this->initialize();
        }
        return $this->isValid;
    }

    /**
     * If this token is valid AND a refresh token then this will return true.
     * @return bool
     */
    public function isRefreshToken(): bool
    {
        if (!$this->isInitialized) {
            $this->initialize();
        }
        return $this->isValid && $this->isRefreshToken;
    }

    /**
     * If this token is valid AND an access token then this will return true.
     * @return bool
     */
    public function isAccessToken(): bool
    {
        if (!$this->isInitialized) {
            $this->initialize();
        }
        return $this->isValid && !$this->isRefreshToken;
    }

    /**
     * If this token is valid then this wont't be null.
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
     * If this token is valid then this wont't be null.
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
}