<?php

namespace Epubli\PermissionBundle\Traits;

use Epubli\PermissionBundle\Service\JWTMockCreator;
use Symfony\Component\BrowserKit\Cookie;

/**
 * Trait JsonWebTokenMockTrait
 * This trait needs the class in which it is included to extend from Symfony\Bundle\FrameworkBundle\Test\KernelTestCase
 * @package Epubli\PermissionBundle\Traits
 */
trait JWTMockTrait
{
    /** @var JWTMockCreator */
    private static $jwtMockCreator;

    /** @var Cookie */
    private static $cachedCookie;

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
        self::$jwtMockCreator = $kernel->getContainer()->get(
            'epubli_permission.service.jwt_mock_creator'
        );

        //Shutdown Kernel so it can be called by others later
        //Symfony\Bundle\FrameworkBundle\Test\KernelTestCase::ensureKernelShutdown()
        self::ensureKernelShutdown();

        self::$cachedCookie = self::$jwtMockCreator->createBrowserKitCookie(
            self::$jwtMockCreator->createJsonWebTokenForThisMicroservice()
        );
    }
}