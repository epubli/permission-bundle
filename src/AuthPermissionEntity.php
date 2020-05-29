<?php

namespace Epubli\PermissionBundle;

use Epubli\PermissionBundle\Annotation\AuthPermission;

/**
 * Class AuthPermissionEntity
 * @package Epubli\PermissionBundle
 */
class AuthPermissionEntity
{
    /** @var string */
    private $classPath;
    /** @var AuthPermission */
    private $annotation;
    /** @var AuthPermissionEndpoint[] */
    private $endpoints;

    /**
     * AuthPermissionEntity constructor.
     * @param string $classPath
     * @param AuthPermission $annotation
     * @param AuthPermissionEndpoint[] $endpoints
     */
    public function __construct(string $classPath, AuthPermission $annotation, array $endpoints)
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
     * @return AuthPermission
     */
    public function getAnnotation(): AuthPermission
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