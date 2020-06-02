<?php

namespace Epubli\PermissionBundle\Traits;

use Epubli\PermissionBundle\Service\JsonWebTokenMockCreator;

/**
 * Trait JsonWebTokenMockTrait
 * This trait needs the class in which it is included to extend from Symfony\Bundle\FrameworkBundle\Test\KernelTestCase
 * @package Epubli\PermissionBundle\Traits
 */
trait JsonWebTokenMockTrait
{
    /** @var JsonWebTokenMockCreator */
    private static $jsonWebTokenMockCreator;

    /**
     *  Call this in a test class in the setUpBeforeClass() method.
     */
    protected static function setUpJsonWebTokenMockCreator(): void
    {
        if (static::$booted) {
            $kernel = self::$kernel;
        } else {
            $kernel = self::bootKernel();
        }
        self::$jsonWebTokenMockCreator = $kernel->getContainer()->get(
            'epubli_permission.service.json_web_token_mock_creator'
        );

        //Shutdown Kernel so it can be called by others later
        //Symfony\Bundle\FrameworkBundle\Test\KernelTestCase::ensureKernelShutdown()
        self::ensureKernelShutdown();
    }
}