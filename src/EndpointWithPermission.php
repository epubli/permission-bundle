<?php

namespace Epubli\PermissionBundle;

/**
 * Class EndpointWithPermission
 * @package Epubli\PermissionBundle
 */
class EndpointWithPermission
{
    /** @var string */
    private $path;
    /** @var string */
    private $regex;
    /** @var string */
    private $httpMethod;
    /** @var string */
    private $controllerClass;
    /** @var string */
    private $permissionKey;

    /**
     * AuthPermissionEndpoint constructor.
     * @param string $path
     * @param string $regex
     * @param string $httpMethod
     * @param string $controllerClass
     * @param string $permissionKey
     */
    public function __construct(
        string $path,
        string $regex,
        string $httpMethod,
        string $controllerClass,
        string $permissionKey
    ) {
        $this->path = $path;
        $this->regex = $regex;
        $this->httpMethod = $httpMethod;
        $this->controllerClass = $controllerClass;
        $this->permissionKey = $permissionKey;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function getRegex(): string
    {
        return $this->regex;
    }

    /**
     * @return string
     */
    public function getHttpMethod(): string
    {
        return $this->httpMethod;
    }

    /**
     * @return string
     */
    public function getControllerClass(): string
    {
        return $this->controllerClass;
    }

    /**
     * @return string
     */
    public function getPermissionKey(): string
    {
        return $this->permissionKey;
    }
}