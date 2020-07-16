<?php

namespace Epubli\PermissionBundle\Service;

use Doctrine\Common\Annotations\Reader;
use Epubli\PermissionBundle\Annotation\Permission;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Class CustomPermissionDiscovery
 * @package Epubli\PermissionBundle\Service
 */
class CustomPermissionDiscovery
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
     * @var array
     */
    private $permissions;

    /**
     * @var string
     */
    private $microserviceName;

    /**
     * @var string
     */
    private $pathToControllers;

    /**
     * @var string
     */
    private $pathToServices;

    /**
     * @var string
     */
    private $namespaceToControllers;

    /**
     * @var string
     */
    private $namespaceToServices;

    /**
     * @param string $microserviceName
     * @param ParameterBagInterface $parameterBag
     * @param Reader $annotationReader
     * @param string $pathToControllers
     * @param string $pathToServices
     * @param string $namespaceToControllers
     * @param string $namespaceToServices
     */
    public function __construct(
        string $microserviceName,
        ParameterBagInterface $parameterBag,
        Reader $annotationReader,
        string $pathToControllers = '/src/Controller',
        string $pathToServices = '/src/Service',
        string $namespaceToControllers = 'App\\Controller\\',
        string $namespaceToServices = 'App\\Service\\'
    ) {
        $this->microserviceName = strtolower($microserviceName);
        $this->annotationReader = $annotationReader;
        $this->rootDir = $parameterBag->get('kernel.project_dir');
        $this->pathToControllers = $pathToControllers;
        $this->pathToServices = $pathToServices;
        $this->namespaceToControllers = $namespaceToControllers;
        $this->namespaceToServices = $namespaceToServices;
    }

    /**
     * @return array
     * @throws ReflectionException
     */
    public function getAllPermissionKeysWithDescriptions(): array
    {
        return $this->getPermissions();
    }

    /**
     * Returns all entities with permissions
     * @throws ReflectionException
     */
    private function getPermissions(): array
    {
        if ($this->permissions === null) {
            $this->discoverPermissions();
        }

        return $this->permissions;
    }

    /**
     * Discovers all custom permissions
     * @throws ReflectionException
     */
    private function discoverPermissions(): void
    {
        $this->permissions = [];

        $this->fillPermissions(
            $this->permissions,
            $this->rootDir . $this->pathToControllers,
            $this->namespaceToControllers
        );
        $this->fillPermissions(
            $this->permissions,
            $this->rootDir . $this->pathToServices,
            $this->namespaceToServices
        );

        $this->permissions = array_unique($this->permissions, SORT_REGULAR);
    }

    /**
     * @param array $permissions
     * @param string $path
     * @param string $namespace
     * @return void
     * @throws ReflectionException
     */
    private function fillPermissions(array &$permissions, string $path, string $namespace): void
    {
        $finder = new Finder();
        $finder->files()->in($path);

        /** @var SplFileInfo $file */
        foreach ($finder as $file) {
            $classPath = $namespace . $file->getBasename('.php');

            $reflectionClass = new ReflectionClass($classPath);

            foreach ($reflectionClass->getMethods() as $reflectionMethod) {
                $annotations = $this->annotationReader->getMethodAnnotations($reflectionMethod);

                foreach ($annotations as $annotation) {
                    if (!($annotation instanceof Permission)) {
                        continue;
                    }
                    /** @var Permission $permissionAnnotation */
                    $permissionAnnotation = $annotation;

                    $permissions[] = [
                        'key' => $this->microserviceName . '.' . $permissionAnnotation->getKey(),
                        'description' => $permissionAnnotation->getDescription()
                    ];
                }
            }
        }
    }

    /**
     * @return string[]
     * @throws ReflectionException
     */
    public function getAllPermissionKeys(): array
    {
        return array_map(
            static function (array $permission) {
                return $permission['key'];
            },
            $this->getPermissions()
        );
    }

    /**
     * @return string
     */
    public function getMicroserviceName(): string
    {
        return $this->microserviceName;
    }
}