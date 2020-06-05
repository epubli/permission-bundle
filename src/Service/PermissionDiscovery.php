<?php

namespace Epubli\PermissionBundle\Service;

use ApiPlatform\Core\Action\PlaceholderAction;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Annotations\Reader;
use Epubli\PermissionBundle\Annotation\Permission;
use Epubli\PermissionBundle\EndpointWithPermission;
use Epubli\PermissionBundle\EntityWithPermissions;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Class PermissionDiscovery
 * @package Epubli\PermissionBundle\Service
 */
class PermissionDiscovery
{
    /**
     * @var Reader
     */
    private $annotationReader;

    /**
     * The Kernel root directory
     * @var string
     */
    private $rootDir;

    /**
     * @var EntityWithPermissions[]|null
     */
    private $entities;

    /**
     * @var string
     */
    private $microserviceName;

    /**
     * @param string $microserviceName
     * @param ParameterBagInterface $parameterBag
     * @param Reader $annotationReader
     */
    public function __construct(string $microserviceName, ParameterBagInterface $parameterBag, Reader $annotationReader)
    {
        $this->microserviceName = strtolower($microserviceName);
        $this->annotationReader = $annotationReader;
        $this->rootDir = $parameterBag->get('kernel.project_dir');
    }

    /**
     * @param object $controller
     * @param string $httpMethod
     * @param string $requestPath
     * @return bool
     * @throws ReflectionException
     */
    public function needsAuthentication($controller, string $httpMethod, string $requestPath): bool
    {
        foreach ($this->getEntities() as $entity) {
            foreach ($entity->getEndpoints() as $endpoint) {
                if ($httpMethod === $endpoint->getHttpMethod()
                    && is_a($controller, $endpoint->getControllerClass())
                    && preg_match($endpoint->getRegex(), $requestPath)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param object $controller
     * @param string $httpMethod
     * @param string $requestPath
     * @return string|null
     * @throws ReflectionException
     */
    public function getPermissionKey($controller, string $httpMethod, string $requestPath): ?string
    {
        foreach ($this->getEntities() as $entity) {
            foreach ($entity->getEndpoints() as $endpoint) {
                if ($httpMethod === $endpoint->getHttpMethod()
                    && is_a($controller, $endpoint->getControllerClass())
                    && preg_match($endpoint->getRegex(), $requestPath)) {
                    return $endpoint->getPermissionKey();
                }
            }
        }

        return null;
    }

    /**
     * @return array
     * @throws ReflectionException
     */
    public function getAllPermissionKeysWithDescriptions(): array
    {
        $permissions = [];

        foreach ($this->getEntities() as $entity) {
            foreach ($entity->getEndpoints() as $endpoint) {
                $permissions[] = [
                    'key' => $endpoint->getPermissionKey(),
                    'description' => ''
                ];
            }
        }

        return $permissions;
    }

    /**
     * @return string[]
     * @throws ReflectionException
     */
    public function getAllPermissionKeys(): array
    {
        $permissions = [];

        foreach ($this->getEntities() as $entity) {
            foreach ($entity->getEndpoints() as $endpoint) {
                $permissions[] = $endpoint->getPermissionKey();
            }
        }

        return $permissions;
    }

    /**
     * @return string
     */
    public function getMicroserviceName(): string
    {
        return $this->microserviceName;
    }

    /**
     * Returns all entities with permissions
     * @throws ReflectionException
     */
    private function getEntities(): array
    {
        if ($this->entities === null) {
            $this->discoverEntities();
        }

        return $this->entities;
    }

    /**
     * Discovers all entities with permissions
     * @throws ReflectionException
     */
    private function discoverEntities(): void
    {
        $this->entities = [];

        $path = $this->rootDir . '/src/Entity';
        $finder = new Finder();
        $finder->files()->in($path);

        /** @var SplFileInfo $file */
        foreach ($finder as $file) {
            $classPath = 'App\\Entity\\' . $file->getBasename('.php');

            $reflectionClass = new ReflectionClass($classPath);

            /** @var Permission $permissionAnnotation */
            $permissionAnnotation = $this->annotationReader->getClassAnnotation(
                $reflectionClass,
                Permission::class
            );
            if (!$permissionAnnotation) {
                continue;
            }

            $this->entities[] = new EntityWithPermissions(
                $classPath, $this->getEndpointsOfEntity($reflectionClass, $permissionAnnotation)
            );
        }
    }

    /**
     * @param ReflectionClass $reflectionClass
     * @param Permission $permissionAnnotation
     * @return EndpointWithPermission[]
     */
    private function getEndpointsOfEntity(ReflectionClass $reflectionClass, Permission $permissionAnnotation): array
    {
        /** @var ApiResource $apiPlatformAnnotation */
        $apiPlatformAnnotation = $this->annotationReader->getClassAnnotation(
            $reflectionClass,
            ApiResource::class
        );

        $className = self::fromCamelCaseToSnakeCase($reflectionClass->getShortName());

        $endpoints = [];

        $routePrefix = $apiPlatformAnnotation->attributes['route_prefix'] ?? null;

        $endpoints = array_merge(
            $endpoints,
            $this->parseOperationsToEndpoints(
                $routePrefix,
                $className,
                $apiPlatformAnnotation->itemOperations ?? ['get', 'put', 'patch', 'delete'],
                $permissionAnnotation->getItemOperations(),
                true
            )
        );
        $endpoints = array_merge(
            $endpoints,
            $this->parseOperationsToEndpoints(
                $routePrefix,
                $className,
                $apiPlatformAnnotation->collectionOperations ?? ['get', 'post'],
                $permissionAnnotation->getCollectionOperations(),
                false
            )
        );

        return $endpoints;
    }

    /**
     * @param string|null $routePrefix
     * @param string $className
     * @param array $apiPlatformOperations
     * @param array|null $permissionOperations
     * @param bool $isItemOperation
     * @return EndpointWithPermission[]
     */
    public function parseOperationsToEndpoints(
        ?string $routePrefix,
        string $className,
        array $apiPlatformOperations,
        ?array $permissionOperations,
        bool $isItemOperation
    ): array {
        $endpoints = [];

        $permissionOperations = $permissionOperations === null ? null : array_map(
            'strtoupper',
            $permissionOperations
        );

        foreach ($apiPlatformOperations as $operationName => $data) {
            if (is_string($data)) {
                //If there are no further properties defined,
                //then $data contains the name of the operation
                $operationName = $data;
                $data = array();
            }

            if ($permissionOperations !== null
                && !in_array(strtoupper($operationName), $permissionOperations, true)) {
                continue;
            }

            $endpoints[] = $this->getEndpoint($routePrefix, $className, $operationName, $data, $isItemOperation);
        }

        return $endpoints;
    }

    /**
     * @param string|null $routePrefix
     * @param string $className
     * @param string $operationName
     * @param array $data
     * @param bool $isItemOperation
     * @return EndpointWithPermission
     */
    private function getEndpoint(
        ?string $routePrefix,
        string $className,
        string $operationName,
        array $data,
        bool $isItemOperation
    ): EndpointWithPermission {
        $path = '/api';
        if ($routePrefix !== null) {
            $path .= '/' . trim($routePrefix, '/');
        }
        if (isset($data['path'])) {
            $path .= $data['path'];
            $path = rtrim($path, '/');
        } else {
            $path .= "/{$className}s";
            if ($isItemOperation) {
                $path .= '/{id}';
            }
        }

        $regex = str_replace(array('/', '{id}'), array('\\/', '\\d+'), $path);
        $regex = "/^$regex$/";

        $httpMethod = strtoupper($data['method'] ?? $operationName);

        $controllerClass = $data['controller'] ?? PlaceholderAction::class;

        switch (strtoupper($operationName)) {
            case 'POST':
                $action = 'create';
                break;
            case 'GET':
                $action = 'read';
                break;
            case 'DELETE':
                $action = 'delete';
                break;
            case 'PUT':
            case 'PATCH':
                $action = 'update';
                break;
            default:
                $action = $operationName;
        }

        $permissionKey = implode('.', [$this->microserviceName, $className, $action]);

        return new EndpointWithPermission($path, $regex, $httpMethod, $controllerClass, $permissionKey);
    }

    /**
     * @param $input
     * @return string
     */
    private static function fromCamelCaseToSnakeCase($input): string
    {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }
        return implode('_', $ret);
    }
}