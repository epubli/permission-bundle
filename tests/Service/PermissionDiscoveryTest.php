<?php

namespace Epubli\PermissionBundle\Tests\Service;

use Doctrine\Common\Annotations\AnnotationReader;
use Epubli\PermissionBundle\Service\PermissionDiscovery;
use Epubli\PermissionBundle\Tests\Helpers\TestEntityWithDifferentShortName;
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
            'key' => 'test.test_entity_in_sub_directory.read',
            'description' => 'Can \'read\' an entity of type \'test_entity_in_sub_directory\' regardless of ownership'
        ],
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
            'key' => 'test.test_entity_without_user_id_property.read',
            'description' => 'Can \'read\' an entity of type \'test_entity_without_user_id_property\' regardless of ownership'
        ],
        [
            'key' => 'test.test_entity_without_user_id_property.read.self',
            'description' => 'Can \'read\' an entity of type \'test_entity_without_user_id_property\' but only if it belongs to them'
        ],
        [
            'key' => 'test.test_entity_without_user_id_property.delete',
            'description' => 'Can \'delete\' an entity of type \'test_entity_without_user_id_property\' regardless of ownership'
        ],
        [
            'key' => 'test.test_entity_without_user_id_property.delete.self',
            'description' => 'Can \'delete\' an entity of type \'test_entity_without_user_id_property\' but only if it belongs to them'
        ],
        [
            'key' => 'test.test_entity_without_user_id_property.update.someString',
            'description' => 'Can \'update\' the property \'someString\' on an entity of type \'test_entity_without_user_id_property\' regardless of ownership'
        ],
        [
            'key' => 'test.test_entity_without_user_id_property.update.someString.self',
            'description' => 'Can \'update\' the property \'someString\' on an entity of type \'test_entity_without_user_id_property\' but only if it belongs to them'
        ],
        [
            'key' => 'test.test_entity_without_user_id_property.update.someOtherString',
            'description' => 'Can \'update\' the property \'someOtherString\' on an entity of type \'test_entity_without_user_id_property\' regardless of ownership'
        ],
        [
            'key' => 'test.test_entity_without_user_id_property.update.someOtherString.self',
            'description' => 'Can \'update\' the property \'someOtherString\' on an entity of type \'test_entity_without_user_id_property\' but only if it belongs to them'
        ],
        [
            'key' => 'test.test_entity_without_user_id_property.create',
            'description' => 'Can \'create\' an entity of type \'test_entity_without_user_id_property\' regardless of ownership'
        ],
        [
            'key' => 'test.test_entity_without_user_id_property.create.self',
            'description' => 'Can \'create\' an entity of type \'test_entity_without_user_id_property\' but only if it belongs to them'
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
        ],
        [
            'key' => 'test.test_entity_with_different_short_name.create',
            'description' => 'Can \'create\' an entity of type \'test_entity_with_different_short_name\' regardless of ownership'
        ],
        [
            'key' => 'test.test_entity_with_different_short_name.read',
            'description' => 'Can \'read\' an entity of type \'test_entity_with_different_short_name\' regardless of ownership'
        ]
    ];

    /**
     * @param string $pathToEntities
     * @param string $microserviceName
     * @return PermissionDiscovery
     */
    public static function createPermissionDiscovery(
        $pathToEntities = '/tests/Helpers',
        $microserviceName = 'test'
    ): PermissionDiscovery {
        $kernelProjectDir = substr(__DIR__, 0, strlen(__DIR__) - strlen('/tests/Service'));
        return new PermissionDiscovery(
            $microserviceName,
            new ParameterBag(['kernel.project_dir' => $kernelProjectDir]),
            new AnnotationReader(),
            $pathToEntities,
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
        object $entity,
        string $httpMethod,
        string $requestPath,
        array $permissionKey,
        ?string $requestContent
    ): void {
        $permissionDiscovery = self::createPermissionDiscovery();

        self::assertEqualsCanonicalizing(
            $permissionKey,
            $permissionDiscovery->getPermissionKeys(
                $entity,
                $httpMethod,
                $requestPath,
                '',
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
            '/api/test_entity_with_specific_securities',
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
        yield [
            new TestEntityWithDifferentShortName(),
            'POST',
            '/api/completelydifferent',
            ['test.test_entity_with_different_short_name.create'],
            null,
        ];
        yield [
            new TestEntityWithDifferentShortName(),
            'GET',
            '/api/veryshortname',
            ['test.test_entity_with_different_short_name.read'],
            null,
        ];
    }

    public function testPermissionDiscovery(): void
    {
        $permissionDiscovery = self::createPermissionDiscovery();

        self::assertEquals('test', $permissionDiscovery->getMicroserviceName());

        $permissionKeys = array_column(self::PERMISSION_KEYS_WITH_DESCRIPTIONS, 'key');
        self::assertEqualsCanonicalizing($permissionKeys, $permissionDiscovery->getAllPermissionKeys());

        self::assertEqualsCanonicalizing(
            self::PERMISSION_KEYS_WITH_DESCRIPTIONS,
            $permissionDiscovery->getAllPermissionKeysWithDescriptions()
        );
    }
}
