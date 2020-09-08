<?php

namespace Epubli\PermissionBundle\UnitTestHelpers;

class UnitTestGetCollectionData extends UnitTestData
{
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
        parent::__construct($resourceURI, $permissionKey, $userId);
        $this->expectedCount = $expectedCount;
    }

    /**
     * @return int
     */
    public function getExpectedCount(): int
    {
        return $this->expectedCount;
    }
}