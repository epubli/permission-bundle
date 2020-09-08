<?php

namespace Epubli\PermissionBundle\UnitTestHelpers;

class UnitTestGetCollectionData
{
    /** @var string */
    private $resourceURI;
    /** @var string */
    private $permissionKey;
    /** @var int */
    private $userId;
    /** @var int */
    private $expectedCount;

    /**
     * UnitTestGetCollectionData constructor.
     * @param string $resourceURI
     * @param string $permissionKey must grant access to read this resource.
     * @param int $userId needs to be valid for this $resourceURI.
     * Value is ignored if self::$unitTestConfig->implementsSelfPermissionInterface() is false
     * @param int $expectedCount The count of items which are expected for this specific $userId
     */
    public function __construct(string $resourceURI, string $permissionKey, int $userId, int $expectedCount)
    {
        $this->resourceURI = $resourceURI;
        $this->permissionKey = $permissionKey;
        $this->userId = $userId;
        $this->expectedCount = $expectedCount;
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
     * @return int
     */
    public function getExpectedCount(): int
    {
        return $this->expectedCount;
    }
}