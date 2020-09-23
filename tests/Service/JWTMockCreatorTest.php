<?php

namespace Epubli\PermissionBundle\Tests\Service;

use Epubli\PermissionBundle\Service\AccessToken;
use Epubli\PermissionBundle\Service\CustomPermissionDiscovery;
use Epubli\PermissionBundle\Service\JWTMockCreator;
use Epubli\PermissionBundle\Service\PermissionDiscovery;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class JWTMockCreatorTest extends TestCase
{
    /**
     * @param $requestContainer
     * @param MockHandler $mockHandler
     * @param PermissionDiscovery $permissionDiscovery
     * @param CustomPermissionDiscovery $customPermissionDiscovery
     * @return JWTMockCreator
     */
    public static function createJWTMockCreator(
        &$requestContainer,
        MockHandler $mockHandler,
        PermissionDiscovery $permissionDiscovery,
        CustomPermissionDiscovery $customPermissionDiscovery
    ): JWTMockCreator {
        $handlerStack = HandlerStack::create($mockHandler);

        $history = Middleware::history($requestContainer);

        $handlerStack->push($history);

        $client = new Client(['handler' => $handlerStack]);

        return new JWTMockCreator(
            $client,
            $permissionDiscovery,
            $customPermissionDiscovery
        );
    }

    public function testGetMockAuthorizationHeader(): void
    {
        $permissionKey = 'permission.perm';

        $requestContainer = [];
        $jwtMockCreator = self::createJWTMockCreator(
            $requestContainer,
            new MockHandler(),
            PermissionDiscoveryTest::createPermissionDiscovery(),
            CustomPermissionDiscoveryTest::createCustomPermissionDiscovery()
        );
        $jwt = $jwtMockCreator->createJsonWebToken([$permissionKey]);

        $this->assertNotNull($jwt);
        $this->assertNotEmpty($jwt);

        $accessToken = $this->createAccessToken($jwt);

        $this->assertTrue($accessToken->exists());
        $this->assertTrue($accessToken->hasPermissionKey($permissionKey));
        $this->assertEquals(-1, $accessToken->getUserId());
    }

    public function testGetMockAuthorizationHeaderWithSpecificUserId(): void
    {
        $permissionKey = 'permission.perm';

        $requestContainer = [];
        $jwtMockCreator = self::createJWTMockCreator(
            $requestContainer,
            new MockHandler(),
            PermissionDiscoveryTest::createPermissionDiscovery(),
            CustomPermissionDiscoveryTest::createCustomPermissionDiscovery()
        );
        $jwt = $jwtMockCreator->createJsonWebToken([$permissionKey], 52);

        $this->assertNotNull($jwt);
        $this->assertNotEmpty($jwt);

        $accessToken = $this->createAccessToken($jwt);

        $this->assertTrue($accessToken->exists());
        $this->assertTrue($accessToken->hasPermissionKey($permissionKey));
        $this->assertEquals(52, $accessToken->getUserId());
    }

    /**
     * @param string $jwt
     * @return AccessToken
     */
    private function createAccessToken(string $jwt): AccessToken
    {
        $requestStack = new RequestStack();
        $request = new Request([], [], [], [AccessToken::ACCESS_TOKEN_COOKIE_NAME => $jwt], [], []);
        $requestStack->push($request);
        return new AccessToken($requestStack);
    }

    public function testGetMockAuthorizationHeaderForThisMicroservice(): void
    {
        $requestContainer = [];
        $jwtMockCreator = self::createJWTMockCreator(
            $requestContainer,
            new MockHandler(),
            PermissionDiscoveryTest::createPermissionDiscovery(),
            CustomPermissionDiscoveryTest::createCustomPermissionDiscovery()
        );
        $jwt = $jwtMockCreator->createJsonWebTokenForThisMicroservice();

        $this->assertNotNull($jwt);
        $this->assertNotEmpty($jwt);

        $accessToken = $this->createAccessToken($jwt);

        $this->assertTrue($accessToken->exists());
        $this->assertTrue($accessToken->hasPermissionKey('test.test_entity_with_everything.create'));
        $this->assertTrue($accessToken->hasPermissionKey('test.test_entity_with_everything.read'));
        $this->assertTrue($accessToken->hasPermissionKey('test.test_entity_with_everything.delete'));
    }
}
