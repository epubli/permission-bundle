<?php

namespace Epubli\PermissionBundle;

use Epubli\PermissionBundle\Annotation\Permission;

/**
 * Class AuthPermissionEntity
 * @package Epubli\PermissionBundle
 */
class AuthPermissionEntity
{
    /** @var string */
    private $classPath;
    /** @var Permission */
    private $annotation;
    /** @var AuthPermissionEndpoint[] */
    private $endpoints;

    /**
     * AuthPermissionEntity constructor.
     * @param string $classPath
     * @param Permission $annotation
     * @param AuthPermissionEndpoint[] $endpoints
     */
    public function __construct(string $classPath, Permission $annotation, array $endpoints)
    {
        $this->classPath = $classPath;
        $this->annotation = $annotation;
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
     * @return Permission
     */
    public function getAnnotation(): Permission
    {
        return $this->annotation;
    }

    /**
     * @return AuthPermissionEndpoint[]
     */
    public function getEndpoints(): array
    {
        return $this->endpoints;
    }
}