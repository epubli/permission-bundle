<?php

namespace Epubli\PermissionBundle\Tests\Service;

use Epubli\PermissionBundle\Service\AccessToken;
use Epubli\PermissionBundle\Service\JWTMockCreator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class JWTMockCreatorTest extends TestCase
{
    public function testGetMockAuthorizationHeader(): void
    {
        $permissionKey = 'permission.perm';

        $jwtMockCreator = new JWTMockCreator(
            PermissionDiscoveryTest::createPermissionDiscovery(),
            CustomPermissionDiscoveryTest::createCustomPermissionDiscovery()
        );
        $jwt = $jwtMockCreator->createJsonWebToken([$permissionKey]);

        $this->assertNotNull($jwt);
        $this->assertNotEmpty($jwt);

        $accessToken = $this->createAccessToken($jwtMockCreator->createCookieArray($jwt));

        $this->assertTrue($accessToken->exists());
        $this->assertTrue($accessToken->hasPermissionKey($permissionKey));
        $this->assertEquals(-1, $accessToken->getUserId());
    }

    public function testGetMockAuthorizationHeaderWithSpecificUserId(): void
    {
        $permissionKey = 'permission.perm';

        $jwtMockCreator = new JWTMockCreator(
            PermissionDiscoveryTest::createPermissionDiscovery(),
            CustomPermissionDiscoveryTest::createCustomPermissionDiscovery()
        );
        $jwt = $jwtMockCreator->createJsonWebToken([$permissionKey], 52);

        $this->assertNotNull($jwt);
        $this->assertNotEmpty($jwt);

        $accessToken = $this->createAccessToken($jwtMockCreator->createCookieArray($jwt));

        $this->assertTrue($accessToken->exists());
        $this->assertTrue($accessToken->hasPermissionKey($permissionKey));
        $this->assertEquals(52, $accessToken->getUserId());
    }

    /**
     * @param array $cookies
     * @return AccessToken
     */
    private function createAccessToken(array $cookies): AccessToken
    {
        $requestStack = new RequestStack();
        $request = new Request([], [], [], $cookies, [], []);
        $requestStack->push($request);
        return new AccessToken($requestStack);
    }

    public function testGetMockAuthorizationHeaderForThisMicroservice(): void
    {
        $jwtMockCreator = new JWTMockCreator(
            PermissionDiscoveryTest::createPermissionDiscovery(),
            CustomPermissionDiscoveryTest::createCustomPermissionDiscovery()
        );
        $jwt = $jwtMockCreator->createJsonWebTokenForThisMicroservice();

        $this->assertNotNull($jwt);
        $this->assertNotEmpty($jwt);

        $accessToken = $this->createAccessToken($jwtMockCreator->createCookieArray($jwt));

        $this->assertTrue($accessToken->exists());
        $this->assertTrue($accessToken->hasPermissionKey('test.test_entity_with_everything.create'));
        $this->assertTrue($accessToken->hasPermissionKey('test.test_entity_with_everything.read'));
        $this->assertTrue($accessToken->hasPermissionKey('test.test_entity_with_everything.delete'));
    }
}
