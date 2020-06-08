<?php

namespace Epubli\PermissionBundle\Service;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Annotations\Reader;
use Epubli\PermissionBundle\EndpointWithPermission;
use Epubli\PermissionBundle\EntityWithPermissions;
use Epubli\PermissionBundle\Interfaces\SelfPermissionInterface;
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
     * @param object $entity
     * @param string $httpMethod
     * @param string $requestPath
     * @return string|null
     * @throws ReflectionException
     */
    public function getPermissionKey($entity, string $httpMethod, string $requestPath): ?string
    {
        $isItemOperation = preg_match('/\d+$/', $requestPath);

        $reflectionClass = new ReflectionClass($entity);
        /** @var ApiResource $apiPlatformAnnotation */
        $apiPlatformAnnotation = $this->annotationReader->getClassAnnotation(
            $reflectionClass,
            ApiResource::class
        );

        $className = self::fromCamelCaseToSnakeCase($reflectionClass->getShortName());

        $relevantPath = $this->getRelevantPath($requestPath, $apiPlatformAnnotation, $isItemOperation);

        if ($isItemOperation) {
            $apiPlatformOperations = $apiPlatformAnnotation->itemOperations ?? ['get', 'put', 'patch', 'delete'];
        } else {
            $apiPlatformOperations = $apiPlatformAnnotation->collectionOperations ?? ['get', 'post'];
        }
        foreach ($apiPlatformOperations as $operationName => $data) {
            if (is_string($data)) {
                //If there are no further properties defined,
                //then $data contains the name of the operation
                $operationName = $data;
                $data = array();
            }

            if (strtoupper($operationName) === $httpMethod) {
                return $this->generatePermissionKey($className, $operationName);
            }

            $operationHttpMethod = strtoupper($data['method'] ?? $operationName);
            if ($operationHttpMethod === $httpMethod) {
                if (isset($data['path'])) {
                    $operationPath = rtrim($data['path'], '/');
                } else {
                    $operationPath = "/{$className}s";
                }

                if ($operationPath === $relevantPath) {
                    return $this->generatePermissionKey($className, $operationName);
                }
            }
        }
        return null;
    }

    /**
     * @param string $requestPath
     * @param ApiResource $apiPlatformAnnotation
     * @param bool $isItemOperation
     * @return string
     */
    private function getRelevantPath(
        string $requestPath,
        ApiResource $apiPlatformAnnotation,
        bool $isItemOperation
    ): string {
        $routePrefix = $apiPlatformAnnotation->attributes['route_prefix'] ?? null;

        $path = '/api';
        if ($routePrefix !== null) {
            $path .= '/' . trim($routePrefix, '/');
        }

        $path = substr($requestPath, strlen($path));
        if ($isItemOperation) {
            //remove the number and the slash at the end
            $path = preg_replace('/\/\d+$/', '', $path);
        }

        return $path;
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
                    'description' => $endpoint->getDescription()
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

            /** @var ApiResource $apiPlatformAnnotation */
            $apiPlatformAnnotation = $this->annotationReader->getClassAnnotation(
                $reflectionClass,
                ApiResource::class
            );
            if (!$apiPlatformAnnotation) {
                continue;
            }

            $needsSelfPermission = $reflectionClass->implementsInterface(SelfPermissionInterface::class);
            $className = self::fromCamelCaseToSnakeCase($reflectionClass->getShortName());

            $this->entities[] = new EntityWithPermissions(
                $classPath, $this->getEndpointsOfEntity($className, $apiPlatformAnnotation, $needsSelfPermission)
            );
        }
    }

    /**
     * @param string $className
     * @param ApiResource $apiPlatformAnnotation
     * @param bool $needsSelfPermission
     * @return EndpointWithPermission[]
     */
    private function getEndpointsOfEntity(
        string $className,
        ApiResource $apiPlatformAnnotation,
        bool $needsSelfPermission
    ): array {
        $endpoints = $this->parseOperationsToEndpoints(
            $className,
            $needsSelfPermission,
            $apiPlatformAnnotation->itemOperations ?? ['get', 'put', 'patch', 'delete']
        );

        $endpoints = array_merge(
            $endpoints,
            $this->parseOperationsToEndpoints(
                $className,
                $needsSelfPermission,
                $apiPlatformAnnotation->collectionOperations ?? ['get', 'post']
            )
        );

        return $endpoints;
    }

    /**
     * @param string $className
     * @param bool $needsSelfPermission
     * @param array $apiPlatformOperations
     * @return EndpointWithPermission[]
     */
    public function parseOperationsToEndpoints(
        string $className,
        bool $needsSelfPermission,
        array $apiPlatformOperations
    ): array {
        $endpoints = [];

        foreach ($apiPlatformOperations as $operationName => $data) {
            if (is_string($data)) {
                //If there are no further properties defined,
                //then $data contains the name of the operation
                $operationName = $data;
                $data = array();
            }

            $security = $data['security'] ?? $data['security_post_denormalize'] ?? null;
            if ($security == null) {
                continue;
            }
            if ($security !== 'is_granted(null, object)'
                && $security !== 'is_granted(null, _api_resource_class)') {
                continue;
            }

            $endpoints[] = $this->getEndpoint(
                $className,
                $operationName,
                false
            );
            if ($needsSelfPermission && self::isSelfPermissionPossible($operationName)) {
                $endpoints[] = $this->getEndpoint(
                    $className,
                    $operationName,
                    true
                );
            }
        }

        return $endpoints;
    }

    /**
     * @param string $className
     * @param string $operationName
     * @param bool $isSelfPermission
     * @return EndpointWithPermission
     */
    private function getEndpoint(
        string $className,
        string $operationName,
        bool $isSelfPermission
    ): EndpointWithPermission {
        $permissionKey = $this->generatePermissionKey($className, $operationName);

        $action = self::transformOperationNameToAction($operationName);
        $description = "Can '$action' an entity of type '$className'";

        if ($isSelfPermission) {
            $permissionKey .= EndpointWithPermission::SELF_PERMISSION;
            $description .= " but only if it belongs to them";
        } else {
            $description .= " regardless of ownership";
        }

        return new EndpointWithPermission($permissionKey, $description);
    }

    /**
     * Transforms the operations name from api platform into an action description for the permission key.
     * @param string $operationName
     * @return string
     */
    private static function transformOperationNameToAction(string $operationName): string
    {
        switch (strtoupper($operationName)) {
            case 'POST':
                return 'create';
            case 'GET':
                return 'read';
            case 'DELETE':
                return 'delete';
            case 'PUT':
            case 'PATCH':
                return 'update';
            default:
                return $operationName;
        }
    }

    /**
     * @param string $operationName
     * @return bool
     */
    private static function isSelfPermissionPossible(string $operationName): bool
    {
        switch (strtoupper($operationName)) {
            case 'DELETE':
            case 'POST':
            case 'PUT':
            case 'PATCH':
                return true;
            case 'GET':
            default:
                return false;
        }
    }

    /**
     * @param string $className
     * @param string $operationName
     * @return string
     */
    private function generatePermissionKey(string $className, string $operationName): string
    {
        $action = self::transformOperationNameToAction($operationName);
        return implode('.', [$this->microserviceName, $className, $action]);
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