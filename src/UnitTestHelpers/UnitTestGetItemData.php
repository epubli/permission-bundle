<?php

namespace Epubli\PermissionBundle\UnitTestHelpers;

class UnitTestGetItemData
{
    /** @var string */
    private $resourceURI;
    /** @var string */
    private $permissionKey;
    /** @var int */
    private $userId;

    /**
     * UnitTestGetItemData constructor.
     * @param string $resourceURI
     * @param string $permissionKey must grant access to read this resource.
     * @param int $userId needs to be valid for this $resourceURI.
     * Value is ignored if self::$unitTestConfig->implementsSelfPermissionInterface() is false
     */
    public function __construct(string $resourceURI, string $permissionKey, int $userId)
    {
        $this->resourceURI = $resourceURI;
        $this->permissionKey = $permissionKey;
        $this->userId = $userId;
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
}