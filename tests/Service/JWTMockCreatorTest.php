<?php

namespace Epubli\PermissionBundle\Tests\Service;

use Epubli\PermissionBundle\Service\AccessToken;
use Epubli\PermissionBundle\Service\CustomPermissionDiscovery;
use Epubli\PermissionBundle\Service\JWTMockCreator;
use Epubli\PermissionBundle\Service\PermissionDiscovery;
use GuzzleHttp\Handler\MockHandler;
use PHPUnit\Framework\TestCase;

class JWTMockCreatorTest extends TestCase
{
    /**
     * @param PermissionDiscovery $permissionDiscovery
     * @param CustomPermissionDiscovery $customPermissionDiscovery
     * @return JWTMockCreator
     */
    public static function createJWTMockCreator(
        PermissionDiscovery $permissionDiscovery,
        CustomPermissionDiscovery $customPermissionDiscovery
    ): JWTMockCreator {
        return new JWTMockCreator(
            $permissionDiscovery,
            $customPermissionDiscovery
        );
    }

    public function testGetMockAuthorizationHeader(): void
    {
        $permissionKey = 'permission.perm';

        $jwtMockCreator = self::createJWTMockCreator(
            PermissionDiscoveryTest::createPermissionDiscovery(),
            CustomPermissionDiscoveryTest::createCustomPermissionDiscovery()
        );
        $jwt = $jwtMockCreator->createJsonWebToken([$permissionKey]);

        self::assertNotNull($jwt);
        self::assertNotEmpty($jwt);

        $accessToken = $this->createAccessToken($jwt);

        self::assertTrue($accessToken->exists());
        self::assertTrue($accessToken->hasPermissionKey($permissionKey));
        self::assertEquals(-1, $accessToken->getUserId());
    }

    public function testGetMockAuthorizationHeaderWithSpecificUserId(): void
    {
        $permissionKey = 'permission.perm';

        $jwtMockCreator = self::createJWTMockCreator(
            PermissionDiscoveryTest::createPermissionDiscovery(),
            CustomPermissionDiscoveryTest::createCustomPermissionDiscovery()
        );
        $jwt = $jwtMockCreator->createJsonWebToken([$permissionKey], 52);

        self::assertNotNull($jwt);
        self::assertNotEmpty($jwt);

        $accessToken = $this->createAccessToken($jwt);

        self::assertTrue($accessToken->exists());
        self::assertTrue($accessToken->hasPermissionKey($permissionKey));
        self::assertEquals(52, $accessToken->getUserId());
    }

    /**
     * @param string $jwt
     * @return AccessToken
     */
    private function createAccessToken(string $jwt): AccessToken
    {
        $requestContainer = [];
        return AccessTokenTest::createAccessToken(
            $requestContainer,
            new MockHandler(),
            '',
            '',
            [AccessToken::ACCESS_TOKEN_COOKIE_NAME => $jwt],
            $this->createMock(JWTMockCreator::class)
        );
    }

    public function testGetMockAuthorizationHeaderForThisMicroservice(): void
    {
        $jwtMockCreator = self::createJWTMockCreator(
            PermissionDiscoveryTest::createPermissionDiscovery(),
            CustomPermissionDiscoveryTest::createCustomPermissionDiscovery()
        );
        $jwt = $jwtMockCreator->createJsonWebTokenForThisMicroservice();

        self::assertNotNull($jwt);
        self::assertNotEmpty($jwt);

        $accessToken = $this->createAccessToken($jwt);

        self::assertTrue($accessToken->exists());
        self::assertTrue($accessToken->hasPermissionKey('test.test_entity_with_everything.create'));
        self::assertTrue($accessToken->hasPermissionKey('test.test_entity_with_everything.read'));
        self::assertTrue($accessToken->hasPermissionKey('test.test_entity_with_everything.delete'));
    }

    public function testCreateJsonWebTokenWithAccessToEverything() :void
    {
        $jwtMockCreator = self::createJWTMockCreator(
            PermissionDiscoveryTest::createPermissionDiscovery(),
            CustomPermissionDiscoveryTest::createCustomPermissionDiscovery()
        );
        $jwt = $jwtMockCreator->createJsonWebTokenWithAccessToEverything();

        self::assertNotNull($jwt);
        self::assertNotEmpty($jwt);

        $accessToken = $this->createAccessToken($jwt);
        self::assertTrue($accessToken->exists());
        self::assertTrue($accessToken->hasPermissionKey('test.test_entity.create'));
        self::assertTrue($accessToken->hasPermissionKey('test.test_entity.read'));
        self::assertTrue($accessToken->hasPermissionKey('random.some_random_key'));
    }
}
