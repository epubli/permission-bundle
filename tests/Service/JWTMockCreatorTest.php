<?php

namespace Epubli\PermissionBundle\Tests\Service;

use Epubli\PermissionBundle\Service\AuthToken;
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
        $header = $jwtMockCreator->getMockAuthorizationHeader([$permissionKey]);

        $this->assertNotNull($header);
        $this->assertNotEmpty($header);

        $authToken = $this->createAuthToken($header);

        $this->assertTrue($authToken->isValid());
        $this->assertTrue($authToken->hasPermissionKey($permissionKey));
        $this->assertEquals(-1, $authToken->getUserId());
    }

    public function testGetMockAuthorizationHeaderWithSpecificUserId(): void
    {
        $permissionKey = 'permission.perm';

        $jwtMockCreator = new JWTMockCreator(
            PermissionDiscoveryTest::createPermissionDiscovery(),
            CustomPermissionDiscoveryTest::createCustomPermissionDiscovery()
        );
        $header = $jwtMockCreator->getMockAuthorizationHeader([$permissionKey], 52);

        $this->assertNotNull($header);
        $this->assertNotEmpty($header);

        $authToken = $this->createAuthToken($header);

        $this->assertTrue($authToken->isValid());
        $this->assertTrue($authToken->hasPermissionKey($permissionKey));
        $this->assertEquals(52, $authToken->getUserId());
    }

    private function createAuthToken(string $header): AuthToken
    {
        $requestStack = new RequestStack();
        $request = new Request([], [], [], [], [], ['HTTP_AUTHORIZATION' => $header]);
        $requestStack->push($request);
        return new AuthToken($requestStack);
    }

    public function testGetMockAuthorizationHeaderForThisMicroservice(): void
    {
        $jwtMockCreator = new JWTMockCreator(
            PermissionDiscoveryTest::createPermissionDiscovery(),
            CustomPermissionDiscoveryTest::createCustomPermissionDiscovery()
        );
        $header = $jwtMockCreator->getMockAuthorizationHeaderForThisMicroservice();

        $this->assertNotNull($header);
        $this->assertNotEmpty($header);

        $authToken = $this->createAuthToken($header);

        $this->assertTrue($authToken->isValid());
        $this->assertTrue($authToken->hasPermissionKey('test.test_entity_with_everything.create'));
        $this->assertTrue($authToken->hasPermissionKey('test.test_entity_with_everything.read'));
        $this->assertTrue($authToken->hasPermissionKey('test.test_entity_with_everything.delete'));
    }
}
