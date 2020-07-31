<?php

namespace Epubli\PermissionBundle;

/**
 * Class EntityWithPermissions
 * @package Epubli\PermissionBundle
 */
class EntityWithPermissions
{
    /** @var EndpointWithPermission[] */
    private $endpoints;

    /**
     * @param EndpointWithPermission[] $endpoints
     */
    public function __construct(array $endpoints)
    {
        $this->endpoints = $endpoints;
    }

    /**
     * @return EndpointWithPermission[]
     */
    public function getEndpoints(): array
    {
        return $this->endpoints;
    }
}