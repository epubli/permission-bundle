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
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class JWTMockCreatorTest extends TestCase
{
    /**
     * @param $requestContainer
     * @param MockHandler $mockHandler
     * @param PermissionDiscovery $permissionDiscovery
     * @param CustomPermissionDiscovery $customPermissionDiscovery
     * @param bool $isTestEnvironment
     * @return JWTMockCreator
     */
    public static function createJWTMockCreator(
        &$requestContainer,
        MockHandler $mockHandler,
        PermissionDiscovery $permissionDiscovery,
        CustomPermissionDiscovery $customPermissionDiscovery,
        $isTestEnvironment = false
    ): JWTMockCreator {
        $handlerStack = HandlerStack::create($mockHandler);

        $history = Middleware::history($requestContainer);

        $handlerStack->push($history);

        $client = new Client(['handler' => $handlerStack, 'base_uri' => 'http://user']);

        return new JWTMockCreator(
            $client,
            $isTestEnvironment,
            '/api/permissions?page=1',
            'user.permission.read',
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

        $requestContainer = [];
        $jwtMockCreator = self::createJWTMockCreator(
            $requestContainer,
            new MockHandler(),
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
        $requestContainer = [];
        $jwtMockCreator = self::createJWTMockCreator(
            $requestContainer,
            new MockHandler(),
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

    public function testCreateJsonWebTokenForAllPermissions()
    {
        $requestContainer = [];

        $mockHandler = new MockHandler(
            [
                new Response(
                    200,
                    [],
                    json_encode(
                        [
                            'hydra:member' => [
                                ['key' => 'test.test_entity.create']
                            ],
                            'hydra:view' => [
                                'hydra:next' => '/nextPage'
                            ]
                        ]
                    )
                ),
                new Response(
                    200,
                    [],
                    json_encode(
                        [
                            'hydra:member' => [
                                ['key' => 'test.test_entity.read']
                            ]
                        ]
                    )
                ),
            ]
        );

        $jwtMockCreator = self::createJWTMockCreator(
            $requestContainer,
            $mockHandler,
            PermissionDiscoveryTest::createPermissionDiscovery(),
            CustomPermissionDiscoveryTest::createCustomPermissionDiscovery()
        );
        $jwt = $jwtMockCreator->createJsonWebTokenForAllPermissions();

        self::assertCount(2, $requestContainer);

        /** @var Request $request */
        $request = $requestContainer[0]['request'];
        self::assertEquals('GET', $request->getMethod());
        self::assertEquals('/api/permissions', $request->getUri()->getPath());
        self::assertEquals('page=1', $request->getUri()->getQuery());
        self::assertEquals('user', $request->getUri()->getHost());

        /** @var Request $request */
        $request = $requestContainer[1]['request'];
        self::assertEquals('GET', $request->getMethod());
        self::assertEquals('/nextPage', $request->getUri()->getPath());

        self::assertNotNull($jwt);
        self::assertNotEmpty($jwt);

        $accessToken = $this->createAccessToken($jwt);
        self::assertTrue($accessToken->exists());
        self::assertTrue($accessToken->hasPermissionKey('test.test_entity.create'));
        self::assertTrue($accessToken->hasPermissionKey('test.test_entity.read'));
    }

    public function testCreateJsonWebTokenForAllPermissionsInTestingEnvironment()
    {
        $requestContainer = [];
        $jwtMockCreator = self::createJWTMockCreator(
            $requestContainer,
            new MockHandler(),
            PermissionDiscoveryTest::createPermissionDiscovery(),
            CustomPermissionDiscoveryTest::createCustomPermissionDiscovery(),
            true
        );
        $jwt = $jwtMockCreator->createJsonWebTokenForAllPermissions();

        self::assertCount(0, $requestContainer);

        self::assertNotNull($jwt);
        self::assertNotEmpty($jwt);

        $accessToken = $this->createAccessToken($jwt);
        self::assertTrue($accessToken->exists());
        self::assertFalse($accessToken->hasPermissionKey('test.test_entity.create'));
        self::assertFalse($accessToken->hasPermissionKey('test.test_entity.read'));
    }
}
