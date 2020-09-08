<?php

namespace Epubli\PermissionBundle\UnitTestHelpers;

class UnitTestPostData extends UnitTestData
{
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
        parent::__construct($resourceURI, $permissionKey, $userId);
        $this->payload = $payload;
    }

    /**
     * @return string
     */
    public function getPayload(): string
    {
        return $this->payload;
    }
}