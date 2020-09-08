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

    /**
     * UnitTestPostData constructor.
     * @param string $resourceURI
     * @param string $permissionKey must grant access to create this resource.
     * @param int $userId needs to be valid for this $resourceURI.
     * Value is ignored if self::$unitTestConfig->implementsSelfPermissionInterface() is false
     * @param string $payload
     */
    public function __construct(string $resourceURI, string $permissionKey, int $userId, string $payload)
    {
        $this->resourceURI = $resourceURI;
        $this->permissionKey = $permissionKey;
        $this->userId = $userId;
        $this->payload = $payload;
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
}