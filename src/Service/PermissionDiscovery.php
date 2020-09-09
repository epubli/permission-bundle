<?php

namespace Epubli\PermissionBundle\Service;

use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Operation\UnderscorePathSegmentNameGenerator;
use Doctrine\Common\Annotations\Reader;
use Epubli\PermissionBundle\EndpointWithPermission;
use Epubli\PermissionBundle\EntityWithPermissions;
use Epubli\PermissionBundle\Interfaces\SelfPermissionInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Class PermissionDiscovery
 * @package Epubli\PermissionBundle\Service
 */
class PermissionDiscovery
{
    private const UPDATE_METHODS = ['PUT', 'PATCH'];

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
     * @var string
     */
    private $pathToEntities;

    /**
     * @var string
     */
    private $namespace;

    /**
     * @param string $microserviceName
     * @param ParameterBagInterface $parameterBag
     * @param Reader $annotationReader
     * @param string $pathToEntities
     * @param string $namespace
     */
    public function __construct(
        string $microserviceName,
        ParameterBagInterface $parameterBag,
        Reader $annotationReader,
        string $pathToEntities = '/src/Entity',
        string $namespace = 'App\\Entity\\'
    ) {
        $this->microserviceName = strtolower($microserviceName);
        $this->annotationReader = $annotationReader;
        $this->rootDir = $parameterBag->get('kernel.project_dir');
        $this->pathToEntities = $pathToEntities;
        $this->namespace = $namespace;
    }

    /**
     * @param object $entity
     * @param string $httpMethod
     * @param string $requestPath
     * @param string $routeName
     * @param string|null $requestContent
     * @return string[]
     * @throws ReflectionException
     */
    public function getPermissionKeys(
        object $entity,
        string $httpMethod,
        string $requestPath,
        string $routeName,
        ?string $requestContent
    ): array {
        //Looks for a path which ends with a slash followed by a number and removes it
        $requestPath = preg_replace('/\/\d+$/', '', $requestPath, -1, $numberOfReplacements);
        $isItemOperation = $numberOfReplacements > 0;

        $reflectionClass = new ReflectionClass($entity);
        /** @var ApiResource $apiPlatformAnnotation */
        $apiPlatformAnnotation = $this->annotationReader->getClassAnnotation(
            $reflectionClass,
            ApiResource::class
        );

        $underscorePathSegmentNameGenerator = new UnderscorePathSegmentNameGenerator();
        $className = $underscorePathSegmentNameGenerator->getSegmentName($reflectionClass->getShortName(), false);

        $relevantPath = self::getEntitySpecificPath($requestPath, $apiPlatformAnnotation);

        if ($isItemOperation) {
            $apiPlatformOperations = $apiPlatformAnnotation->itemOperations ?? ['get', 'put', 'patch', 'delete'];
        } else {
            $apiPlatformOperations = $apiPlatformAnnotation->collectionOperations ?? ['get', 'post'];
            //Check for special routes in the itemOperations because these do not need to end with a number
            $apiPlatformOperations = array_merge(
                $apiPlatformOperations,
                self::getSpecialRouteNameOperations(
                    $apiPlatformAnnotation->itemOperations ?? []
                )
            );
        }
        foreach ($apiPlatformOperations as $annotatedOperationName => $data) {
            if (is_string($data)) {
                //If there are no further properties defined,
                //then $data contains the name of the operation
                $annotatedOperationName = $data;
                $data = array();
            }

            $annotatedHttpMethod = strtoupper($data['method'] ?? $annotatedOperationName);
            if ($annotatedHttpMethod !== $httpMethod) {
                continue;
            }

            if (isset($data['route_name'])) {
                if ($routeName === $data['route_name']) {
                    $validOperationPath = $relevantPath;
                } else {
                    $validOperationPath = null;
                }
            } else {
                if (isset($data['path'])) {
                    $validOperationPath = rtrim($data['path'], '/');
                } elseif ($apiPlatformAnnotation->shortName !== null) {
                    $validOperationPath = "/{$apiPlatformAnnotation->shortName}";
                } else {
                    $validOperationPath = '/' . $underscorePathSegmentNameGenerator->getSegmentName(
                            $reflectionClass->getShortName(),
                            true
                        );
                }
            }

            if ($relevantPath !== $validOperationPath) {
                continue;
            }

            if (!self::isUpdateHttpMethod($annotatedHttpMethod)) {
                return [$this->generatePermissionKey($className, $annotatedOperationName)];
            }

            $propertyNames = $this->getRelevantPropertyNames($reflectionClass, $data);
            $json = json_decode($requestContent, true);
            if (is_array($json)) {
                $propertyNames = array_filter(
                    $propertyNames,
                    static function (string $propertyName) use ($json) {
                        return array_key_exists($propertyName, $json);
                    }
                );
            } else {
                $propertyNames = [];
            }

            return array_map(
                function (string $propertyName) use ($className, $annotatedOperationName) {
                    return $this->generatePermissionKey($className, $annotatedOperationName, $propertyName);
                },
                $propertyNames
            );
        }
        return [];
    }

    /**
     * @param string $requestPath
     * @param ApiResource $apiPlatformAnnotation
     * @return string
     */
    private static function getEntitySpecificPath(string $requestPath, ApiResource $apiPlatformAnnotation): string
    {
        $routePrefix = $apiPlatformAnnotation->attributes['route_prefix'] ?? null;

        $path = '/api';
        if ($routePrefix !== null) {
            $path .= '/' . trim($routePrefix, '/');
        }

        return substr($requestPath, strlen($path));
    }

    /**
     * Returns all operations which have the "route_name" property
     * @param array $itemOperations
     * @return array
     */
    private static function getSpecialRouteNameOperations(array $itemOperations): array
    {
        return array_filter(
            $itemOperations,
            static function ($item) {
                return isset($item['route_name']);
            }
        );
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
                $entry = [
                    'key' => $endpoint->getPermissionKey(),
                    'description' => $endpoint->getDescription()
                ];
                if (!in_array($entry, $permissions, true)) {
                    $permissions[] = $entry;
                }
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
                if (!in_array($endpoint->getPermissionKey(), $permissions, true)) {
                    $permissions[] = $endpoint->getPermissionKey();
                }
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

        $path = $this->rootDir . $this->pathToEntities;
        $finder = new Finder();
        try {
            $finder->files()->in($path);
        } catch (DirectoryNotFoundException $ex) {
            return;
        }

        /** @var SplFileInfo $file */
        foreach ($finder as $file) {
            $classPath = $this->namespace . $file->getBasename('.php');

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
            $underscorePathSegmentNameGenerator = new UnderscorePathSegmentNameGenerator();
            $className = $underscorePathSegmentNameGenerator->getSegmentName($reflectionClass->getShortName(), false);

            $this->entities[] = new EntityWithPermissions(
                $this->getEndpointsOfEntity($reflectionClass, $className, $apiPlatformAnnotation, $needsSelfPermission)
            );
        }
    }

    /**
     * @param ReflectionClass $reflectionClass
     * @param string $className
     * @param ApiResource $apiPlatformAnnotation
     * @param bool $needsSelfPermission
     * @return EndpointWithPermission[]
     */
    private function getEndpointsOfEntity(
        ReflectionClass $reflectionClass,
        string $className,
        ApiResource $apiPlatformAnnotation,
        bool $needsSelfPermission
    ): array {
        $endpoints = $this->parseOperationsToEndpoints(
            $reflectionClass,
            $className,
            $needsSelfPermission,
            $apiPlatformAnnotation->itemOperations ?? ['get', 'put', 'patch', 'delete']
        );

        $endpoints = array_merge(
            $endpoints,
            $this->parseOperationsToEndpoints(
                $reflectionClass,
                $className,
                $needsSelfPermission,
                $apiPlatformAnnotation->collectionOperations ?? ['get', 'post']
            )
        );

        return $endpoints;
    }

    /**
     * @param ReflectionClass $reflectionClass
     * @param string $className
     * @param bool $needsSelfPermission
     * @param array $apiPlatformOperations
     * @return EndpointWithPermission[]
     */
    private function parseOperationsToEndpoints(
        ReflectionClass $reflectionClass,
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
            if ($security === null) {
                continue;
            }
            if ($security !== 'is_granted(null, object)'
                && $security !== 'is_granted(null, _api_resource_class)') {
                continue;
            }

            $propertyNames = [];
            if (self::isUpdateHttpMethod(strtoupper($data['method'] ?? $operationName))) {
                $propertyNames = $this->getRelevantPropertyNames($reflectionClass, $data);
            } else {
                //Add a dummy item, so permissions for endpoints, which aren't updates, will be generated
                $propertyNames[] = null;
            }

            foreach ($propertyNames as $propertyName) {
                $endpoints[] = $this->getEndpoint(
                    $className,
                    $operationName,
                    false,
                    $propertyName
                );
                if ($needsSelfPermission && self::isSelfPermissionPossible($operationName)) {
                    $endpoints[] = $this->getEndpoint(
                        $className,
                        $operationName,
                        true,
                        $propertyName
                    );
                }
            }
        }

        return $endpoints;
    }

    /**
     * @param string $className
     * @param string $operationName
     * @param bool $isSelfPermission
     * @param string|null $propertyName
     * @return EndpointWithPermission
     */
    private function getEndpoint(
        string $className,
        string $operationName,
        bool $isSelfPermission,
        ?string $propertyName = null
    ): EndpointWithPermission {
        $permissionKey = $this->generatePermissionKey($className, $operationName, $propertyName);

        $action = self::transformOperationNameToAction($operationName);
        $description = "Can '$action'";
        if ($propertyName !== null) {
            $description .= " the property '$propertyName' on";
        }
        $description .= " an entity of type '$className'";

        if ($isSelfPermission) {
            $permissionKey .= EndpointWithPermission::SELF_PERMISSION;
            $description .= " but only if it belongs to them";
        } else {
            $description .= " regardless of ownership";
        }

        return new EndpointWithPermission($permissionKey, $description);
    }

    /**
     * Returns all property names for this class which belong to the validation groups defined in the API-Platfrom-Resource.
     * If no validation groups are defined then all properties of this class are returned
     * @param ReflectionClass $reflectionClass
     * @param array $apiPlatformData
     * @return string[]
     */
    private function getRelevantPropertyNames(ReflectionClass $reflectionClass, array $apiPlatformData): array
    {
        $validationGroups = $apiPlatformData['validation_groups'] ?? null;

        if ($validationGroups === null) {
            //If no group could be found than all properties need to have permissions
            return array_map(
                static function (ReflectionProperty $reflectionProperty) {
                    return $reflectionProperty->getName();
                },
                $reflectionClass->getProperties()
            );
        }
        //Otherwise only add permissions for properties which belong to these validation groups

        $propertyNames = [];
        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            /** @var Groups $groupsAnnotation */
            $groupsAnnotation = $this->annotationReader->getPropertyAnnotation(
                $reflectionProperty,
                Groups::class
            );
            if ($groupsAnnotation === null
                || empty(array_intersect($groupsAnnotation->getGroups(), $validationGroups))) {
                continue;
            }
            $propertyNames[] = $reflectionProperty->getName();
        }

        return $propertyNames;
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
            case 'GET':
                return true;
            default:
                return false;
        }
    }

    /**
     * @param string $className
     * @param string $operationName
     * @param string|null $propertyName
     * @return string
     */
    private function generatePermissionKey(
        string $className,
        string $operationName,
        ?string $propertyName = null
    ): string {
        $action = self::transformOperationNameToAction($operationName);
        $components = [$this->microserviceName, $className, $action];
        if ($propertyName !== null) {
            $components[] = $propertyName;
        }
        return implode('.', $components);
    }

    /**
     * @param string $httpMethod
     * @return bool
     */
    private static function isUpdateHttpMethod(string $httpMethod): bool
    {
        return in_array(strtoupper($httpMethod), self::UPDATE_METHODS, true);
    }
}