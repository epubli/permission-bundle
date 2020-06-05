<?php

namespace Epubli\PermissionBundle;

/**
 * Class EntityWithPermissions
 * @package Epubli\PermissionBundle
 */
class EntityWithPermissions
{
    /** @var string */
    private $classPath;
    /** @var EndpointWithPermission[] */
    private $endpoints;

    /**
     * @param string $classPath
     * @param EndpointWithPermission[] $endpoints
     */
    public function __construct(string $classPath, array $endpoints)
    {
        $this->classPath = $classPath;
        $this->endpoints = $endpoints;
    }

    /**
     * @return string
     */
    public function getClassPath(): string
    {
        return $this->classPath;
    }

    /**
     * @return EndpointWithPermission[]
     */
    public function getEndpoints(): array
    {
        return $this->endpoints;
    }
}