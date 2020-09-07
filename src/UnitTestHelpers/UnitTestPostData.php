<?php

namespace Epubli\PermissionBundle\UnitTestHelpers;

class UnitTestPostData
{
    /** @var string */
    private $resourceURI;
    /** @var string */
    private $permissionKey;
    /** @var int */
    private $userId;
    /** @var string */
    private $payload;
    /** @var string */
    private $jsonKey;
    /** @var string */
    private $newValue;

    /**
     * UnitTestPostData constructor.
     * @param string $resourceURI
     * @param string $permissionKey must grant access to delete this resource.
     * @param int $userId needs to be valid for this $resourceURI.
     * Value is ignored if self::$unitTestConfig->implementsSelfPermissionInterface() is false
     * @param string $payload
     * @param string $jsonKey
     * @param string $newValue
     */
    public function __construct(string $resourceURI, string $permissionKey, int $userId, string $payload, string $jsonKey, string $newValue)
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
     * @return string
     */
    public function getJsonKey(): string
    {
        return $this->jsonKey;
    }

    /**
     * @return string
     */
    public function getNewValue(): string
    {
        return $this->newValue;
    }
}