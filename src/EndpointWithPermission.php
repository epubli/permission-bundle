<?php

namespace Epubli\PermissionBundle;

/**
 * Class EndpointWithPermission
 * @package Epubli\PermissionBundle
 */
class EndpointWithPermission
{
    public const SELF_PERMISSION = '.self';

    /** @var string */
    private $permissionKey;
    /** @var string */
    private $description;

    /**
     * @param string $permissionKey
     * @param string $description
     */
    public function __construct(
        string $permissionKey,
        string $description
    ) {
        $this->permissionKey = $permissionKey;
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getPermissionKey(): string
    {
        return $this->permissionKey;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return bool
     */
    public function isSelfPermission(): bool
    {
        return $this->descriptionEndsWith(self::SELF_PERMISSION);
    }

    /**
     * Checks if the description ends with a specific string
     * @param $str
     * @return bool
     */
    private function descriptionEndsWith($str)
    {
        return substr($this->description, -strlen($str)) === $str;
    }
}