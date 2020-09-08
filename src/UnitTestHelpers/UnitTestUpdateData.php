<?php

namespace Epubli\PermissionBundle\UnitTestHelpers;

class UnitTestUpdateData
{
    /** @var string */
    private $resourceURI;
    /** @var string */
    private $permissionKey;
    /** @var int */
    private $userId;
    /** @var string */
    private $payload;
    /** @var string|null */
    private $jsonKey;
    /** @var string|null */
    private $newValue;

    /**
     * UnitTestUpdateData constructor.
     * @param string $resourceURI
     * @param string $permissionKey must grant access to update this resource.
     * @param int $userId needs to be valid for this $resourceURI.
     * Value is ignored if self::$unitTestConfig->implementsSelfPermissionInterface() is false
     * @param string $payload
     * @param string|null $jsonKey the key in the response json for the new value. If <code>null</code> no check will be made.
     * @param string|null $newValue the new value in the response. If <code>null</code> no check will be made.
     */
    public function __construct(string $resourceURI, string $permissionKey, int $userId, string $payload, ?string $jsonKey = null, ?string $newValue = null)
    {
        $this->resourceURI = $resourceURI;
        $this->permissionKey = $permissionKey;
        $this->userId = $userId;
        $this->payload = $payload;
        $this->jsonKey = $jsonKey;
        $this->newValue = $newValue;
    }

    /**
     * @return string
     */
    public function getResourceURI(): string
    {
        return $this->resourceURI;
    }

    /**
     * @return string
     */
    public function getPermissionKey(): string
    {
        return $this->permissionKey;
    }

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * @return string
     */
    public function getPayload(): string
    {
        return $this->payload;
    }

    /**
     * @return string|null
     */
    public function getJsonKey(): ?string
    {
        return $this->jsonKey;
    }

    /**
     * @return string|null
     */
    public function getNewValue(): ?string
    {
        return $this->newValue;
    }
}