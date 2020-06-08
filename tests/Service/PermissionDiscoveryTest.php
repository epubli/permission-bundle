<?php

namespace Epubli\PermissionBundle\Tests\Service;

use Doctrine\Common\Annotations\AnnotationReader;
use Epubli\PermissionBundle\Service\PermissionDiscovery;
use Epubli\PermissionBundle\Tests\Helpers\TestEntityWithEverything;
use Epubli\PermissionBundle\Tests\Helpers\TestEntityWithSpecificSecurity;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class PermissionDiscoveryTest extends TestCase
{
    /**
     * @dataProvider provider
     * @param object $entity
     * @param string $httpMethod
     * @param string $requestPath
     * @param string $permissionKey
     * @throws \ReflectionException
     */
    public function testPermissionKeys($entity, string $httpMethod, string $requestPath, string $permissionKey): void
    {
        $permissionDiscovery = self::createPermissionDiscovery();

        $this->assertEquals($permissionKey, $permissionDiscovery->getPermissionKey($entity, $httpMethod, $requestPath));
    }

    /**
     * @return PermissionDiscovery
     */
    public static function createPermissionDiscovery(): PermissionDiscovery
    {
        $kernelProjectDir = substr(__DIR__, 0, strlen(__DIR__) - strlen('/tests/Service'));
        return new PermissionDiscovery(
            'test',
            new ParameterBag(['kernel.project_dir' => $kernelProjectDir]),
            new AnnotationReader(),
            '/tests/Helpers',
            'Epubli\\PermissionBundle\\Tests\\Helpers\\'
        );
    }

    public function provider()
    {
        yield [
            new TestEntityWithEverything(),
            'GET',
            '/api/test_entity_with_everythings',
            'test.test_entity_with_everything.read'
        ];
        yield [
            new TestEntityWithEverything(),
            'GET',
            '/api/test_entity_with_everythings/1',
            'test.test_entity_with_everything.read'
        ];
        yield [
            new TestEntityWithEverything(),
            'DELETE',
            '/api/test_entity_with_everythings/1',
            'test.test_entity_with_everything.delete'
        ];
        yield [
            new TestEntityWithEverything(),
            'PUT',
            '/api/test_entity_with_everythings/1',
            'test.test_entity_with_everything.update'
        ];
        yield [
            new TestEntityWithEverything(),
            'PATCH',
            '/api/test_entity_with_everythings/1',
            'test.test_entity_with_everything.update'
        ];
        yield [
            new TestEntityWithEverything(),
            'POST',
            '/api/test_entity_with_everythings',
            'test.test_entity_with_everything.create'
        ];
        yield [
            new TestEntityWithSpecificSecurity(),
            'GET',
            '/api/test_entity_with_specific_securitys',
            'test.test_entity_with_specific_security.read'
        ];
        yield [
            new TestEntityWithSpecificSecurity(),
            'POST',
            '/api/test_entity_with_specific_securitys/special_route',
            'test.test_entity_with_specific_security.special_route'
        ];
    }

    public function testPermissionDiscovery(): void
    {
        $permissionDiscovery = self::createPermissionDiscovery();

        $this->assertEquals('test', $permissionDiscovery->getMicroserviceName());
        $permissionKeys = [
            'test.test_entity_with_self_permission_interface.read',
            'test.test_entity_with_self_permission_interface.read.self',
            'test.test_entity_with_self_permission_interface.delete',
            'test.test_entity_with_self_permission_interface.delete.self',
            'test.test_entity_with_self_permission_interface.update',
            'test.test_entity_with_self_permission_interface.update.self',
            'test.test_entity_with_self_permission_interface.create',
            'test.test_entity_with_self_permission_interface.create.self',
            'test.test_entity_with_specific_security.read',
            'test.test_entity_with_specific_security.special_route',
            'test.test_entity_with_everything.read',
            'test.test_entity_with_everything.delete',
            'test.test_entity_with_everything.update',
            'test.test_entity_with_everything.create'
        ];
        $this->assertEqualsCanonicalizing($permissionKeys, $permissionDiscovery->getAllPermissionKeys());

        $permissionKeysWithDescriptions = [
            [
                'key' => 'test.test_entity_with_self_permission_interface.read',
                'description' => 'Can \'read\' an entity of type \'test_entity_with_self_permission_interface\' regardless of ownership'
            ],
            [
                'key' => 'test.test_entity_with_self_permission_interface.read.self',
                'description' => 'Can \'read\' an entity of type \'test_entity_with_self_permission_interface\' but only if it belongs to them'
            ],
            [
                'key' => 'test.test_entity_with_self_permission_interface.delete',
                'description' => 'Can \'delete\' an entity of type \'test_entity_with_self_permission_interface\' regardless of ownership'
            ],
            [
                'key' => 'test.test_entity_with_self_permission_interface.delete.self',
                'description' => 'Can \'delete\' an entity of type \'test_entity_with_self_permission_interface\' but only if it belongs to them'
            ],
            [
                'key' => 'test.test_entity_with_self_permission_interface.update',
                'description' => 'Can \'update\' an entity of type \'test_entity_with_self_permission_interface\' regardless of ownership'
            ],
            [
                'key' => 'test.test_entity_with_self_permission_interface.update.self',
                'description' => 'Can \'update\' an entity of type \'test_entity_with_self_permission_interface\' but only if it belongs to them'
            ],
            [
                'key' => 'test.test_entity_with_self_permission_interface.create',
                'description' => 'Can \'create\' an entity of type \'test_entity_with_self_permission_interface\' regardless of ownership'
            ],
            [
                'key' => 'test.test_entity_with_self_permission_interface.create.self',
                'description' => 'Can \'create\' an entity of type \'test_entity_with_self_permission_interface\' but only if it belongs to them'
            ],
            [
                'key' => 'test.test_entity_with_specific_security.read',
                'description' => 'Can \'read\' an entity of type \'test_entity_with_specific_security\' regardless of ownership'
            ],
            [
                'key' => 'test.test_entity_with_specific_security.special_route',
                'description' => 'Can \'special_route\' an entity of type \'test_entity_with_specific_security\' regardless of ownership'
            ],
            [
                'key' => 'test.test_entity_with_everything.read',
                'description' => 'Can \'read\' an entity of type \'test_entity_with_everything\' regardless of ownership'
            ],
            [
                'key' => 'test.test_entity_with_everything.delete',
                'description' => 'Can \'delete\' an entity of type \'test_entity_with_everything\' regardless of ownership'
            ],
            [
                'key' => 'test.test_entity_with_everything.update',
                'description' => 'Can \'update\' an entity of type \'test_entity_with_everything\' regardless of ownership'
            ],
            [
                'key' => 'test.test_entity_with_everything.create',
                'description' => 'Can \'create\' an entity of type \'test_entity_with_everything\' regardless of ownership'
            ]
        ];
        $this->assertEqualsCanonicalizing(
            $permissionKeysWithDescriptions,
            $permissionDiscovery->getAllPermissionKeysWithDescriptions()
        );
    }
}
