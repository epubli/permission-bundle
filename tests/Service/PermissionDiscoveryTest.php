<?php

namespace Epubli\PermissionBundle\Tests\Service;

use Doctrine\Common\Annotations\AnnotationReader;
use Epubli\PermissionBundle\Service\PermissionDiscovery;
use Epubli\PermissionBundle\Tests\Helpers\TestEntityWithEverything;
use Epubli\PermissionBundle\Tests\Helpers\TestEntityWithSpecificSecurity;
use Generator;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class PermissionDiscoveryTest extends TestCase
{
    public const PERMISSION_KEYS_WITH_DESCRIPTIONS = [
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
            'key' => 'test.test_entity_with_self_permission_interface.update.someString',
            'description' => 'Can \'update\' the property \'someString\' on an entity of type \'test_entity_with_self_permission_interface\' regardless of ownership'
        ],
        [
            'key' => 'test.test_entity_with_self_permission_interface.update.someString.self',
            'description' => 'Can \'update\' the property \'someString\' on an entity of type \'test_entity_with_self_permission_interface\' but only if it belongs to them'
        ],
        [
            'key' => 'test.test_entity_with_self_permission_interface.update.someOtherString',
            'description' => 'Can \'update\' the property \'someOtherString\' on an entity of type \'test_entity_with_self_permission_interface\' regardless of ownership'
        ],
        [
            'key' => 'test.test_entity_with_self_permission_interface.update.someOtherString.self',
            'description' => 'Can \'update\' the property \'someOtherString\' on an entity of type \'test_entity_with_self_permission_interface\' but only if it belongs to them'
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
            'key' => 'test.test_entity_with_everything.update.someString',
            'description' => 'Can \'update\' the property \'someString\' on an entity of type \'test_entity_with_everything\' regardless of ownership'
        ],
        [
            'key' => 'test.test_entity_with_everything.create',
            'description' => 'Can \'create\' an entity of type \'test_entity_with_everything\' regardless of ownership'
        ]
    ];

    /**
     * @param string $pathToEntites
     * @param string $microserviceName
     * @return PermissionDiscovery
     */
    public static function createPermissionDiscovery(
        $pathToEntites = '/tests/Helpers',
        $microserviceName = 'test'
    ): PermissionDiscovery {
        $kernelProjectDir = substr(__DIR__, 0, strlen(__DIR__) - strlen('/tests/Service'));
        return new PermissionDiscovery(
            $microserviceName,
            new ParameterBag(['kernel.project_dir' => $kernelProjectDir]),
            new AnnotationReader(),
            $pathToEntites,
            'Epubli\\PermissionBundle\\Tests\\Helpers\\'
        );
    }

    /**
     * @dataProvider provider
     * @param object $entity
     * @param string $httpMethod
     * @param string $requestPath
     * @param string[] $permissionKey
     * @param string|null $requestContent
     * @throws ReflectionException
     */
    public function testPermissionKeys(
        $entity,
        string $httpMethod,
        string $requestPath,
        array $permissionKey,
        ?string $requestContent
    ): void {
        $permissionDiscovery = self::createPermissionDiscovery();

        $this->assertEqualsCanonicalizing(
            $permissionKey,
            $permissionDiscovery->getPermissionKeys(
                $entity,
                $httpMethod,
                $requestPath,
                $requestContent
            )
        );
    }

    public function provider(): ?Generator
    {
        yield [
            new TestEntityWithEverything(),
            'GET',
            '/api/test_entity_with_everythings',
            ['test.test_entity_with_everything.read'],
            null,
        ];
        yield [
            new TestEntityWithEverything(),
            'GET',
            '/api/test_entity_with_everythings/1',
            ['test.test_entity_with_everything.read'],
            null,
        ];
        yield [
            new TestEntityWithEverything(),
            'DELETE',
            '/api/test_entity_with_everythings/1',
            ['test.test_entity_with_everything.delete'],
            null,
        ];
        yield [
            new TestEntityWithEverything(),
            'PUT',
            '/api/test_entity_with_everythings/1',
            ['test.test_entity_with_everything.update.someString'],
            '{"someString":"hallo"}',
        ];
        yield [
            new TestEntityWithEverything(),
            'PATCH',
            '/api/test_entity_with_everythings/1',
            ['test.test_entity_with_everything.update.someString'],
            '{"someString":"hallo"}',
        ];
        yield [
            new TestEntityWithEverything(),
            'PUT',
            '/api/test_entity_with_everythings/1',
            [],
            null,
        ];
        yield [
            new TestEntityWithEverything(),
            'PATCH',
            '/api/test_entity_with_everythings/1',
            [],
            null,
        ];
        yield [
            new TestEntityWithEverything(),
            'POST',
            '/api/test_entity_with_everythings',
            ['test.test_entity_with_everything.create'],
            null,
        ];
        yield [
            new TestEntityWithSpecificSecurity(),
            'GET',
            '/api/test_entity_with_specific_securitys',
            ['test.test_entity_with_specific_security.read'],
            null,
        ];
        yield [
            new TestEntityWithSpecificSecurity(),
            'POST',
            '/api/test_entity_with_specific_securitys/special_route',
            ['test.test_entity_with_specific_security.special_route'],
            null,
        ];
    }

    public function testPermissionDiscovery(): void
    {
        $permissionDiscovery = self::createPermissionDiscovery();

        $this->assertEquals('test', $permissionDiscovery->getMicroserviceName());

        $permissionKeys = array_column(self::PERMISSION_KEYS_WITH_DESCRIPTIONS, 'key');
        $this->assertEqualsCanonicalizing($permissionKeys, $permissionDiscovery->getAllPermissionKeys());

        $this->assertEqualsCanonicalizing(
            self::PERMISSION_KEYS_WITH_DESCRIPTIONS,
            $permissionDiscovery->getAllPermissionKeysWithDescriptions()
        );
    }
}
