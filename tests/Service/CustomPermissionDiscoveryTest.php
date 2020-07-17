<?php

namespace Epubli\PermissionBundle\Tests\Service;

use Doctrine\Common\Annotations\AnnotationReader;
use Epubli\PermissionBundle\Service\CustomPermissionDiscovery;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class CustomPermissionDiscoveryTest extends TestCase
{
    private const PERMISSIONS = [
        [
            'key' => 'test.customPermissionForMethod1',
            'description' => 'This is a description',
        ],
        [
            'key' => 'test.customPermissionForMethod2',
            'description' => 'This is a description2',
        ],
        [
            'key' => 'test.secondCustomPermissionForMethod2',
            'description' => 'This is a description for the second custom permission',
        ],
    ];

    public function testMicroserviceName(): void
    {
        $customPermissionDiscovery = self::createCustomPermissionDiscovery();

        $this->assertEquals('test', $customPermissionDiscovery->getMicroserviceName());
    }

    /**
     * @return CustomPermissionDiscovery
     */
    public static function createCustomPermissionDiscovery(): CustomPermissionDiscovery
    {
        $kernelProjectDir = substr(__DIR__, 0, strlen(__DIR__) - strlen('/tests/Service'));
        return new CustomPermissionDiscovery(
            'test',
            new ParameterBag(['kernel.project_dir' => $kernelProjectDir]),
            new AnnotationReader(),
            '/tests/Helpers',
            '/tests/Helpers',
            'Epubli\\PermissionBundle\\Tests\\Helpers\\',
            'Epubli\\PermissionBundle\\Tests\\Helpers\\'
        );
    }

    public function testPermissionKeys(): void
    {
        $customPermissionDiscovery = self::createCustomPermissionDiscovery();

        $this->assertEqualsCanonicalizing(
            array_column(self::PERMISSIONS, 'key'),
            $customPermissionDiscovery->getAllPermissionKeys()
        );
    }

    public function testPermissionKeysAndDescriptions(): void
    {
        $customPermissionDiscovery = self::createCustomPermissionDiscovery();

        $this->assertEqualsCanonicalizing(
            self::PERMISSIONS,
            $customPermissionDiscovery->getAllPermissionKeysWithDescriptions()
        );
    }
}
