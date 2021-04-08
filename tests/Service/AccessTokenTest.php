<?php

namespace Epubli\PermissionBundle\Tests\Service;

use Epubli\PermissionBundle\Service\AccessToken;
use Epubli\PermissionBundle\Service\JWTMockCreator;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class AccessTokenTest extends TestCase
{
    /**
     * @param array $requestContainer
     * @param MockHandler $mockHandler
     * @param string $path
     * @param string $permissionKey
     * @param array $cookies
     * @param JWTMockCreator $jwtMockCreator
     * @param bool $isTestEnvironment
     * @return AccessToken
     */
    public static function createAccessToken(
        array &$requestContainer,
        MockHandler $mockHandler,
        string $path,
        string $permissionKey,
        array $cookies,
        JWTMockCreator $jwtMockCreator,
        bool $isTestEnvironment = true
    ): AccessToken {
        $handlerStack = HandlerStack::create($mockHandler);
        $history = Middleware::history($requestContainer);
        $handlerStack->push($history);
        $client = new Client(['handler' => $handlerStack, 'base_uri' => 'http://user']);

        $requestStack = new RequestStack();
        $request = new Request([], [], [], $cookies, [], []);
        $requestStack->push($request);

        return new AccessToken(
            $client,
            $path,
            $permissionKey,
            $isTestEnvironment,
            $requestStack,
            $jwtMockCreator
        );
    }

    public function testValidAccessToken(): void
    {
        $token = 'eyJhbGciOiJIUzI1NiIsImtpZCI6InNpbTIifQ.eyJleHAiOjE1OTE2MDE5MjUsImlzcyI6Imh0dHBzO'
            . 'i8vZXB1YmxpLmRlIiwianRpIjoiNTdlNDI0NDgtYmE1ZS0zYWY4LWJlMDEtYjFjODYzNzlkNTE3IiwicGVybWlzc'
            . '2lvbnMiOlsidXNlci51c2VyLnJlYWQiLCJ1c2VyLnVzZXIudXBkYXRlLnNlbGYiXSwicm9sZXMiOlsiYWNjZXNzX'
            . '3Rva2VuIl0sInN1YiI6ImV4bWFwbGtlMUBleGFtcGxlLmNvbSIsInVzZXJfaWQiOjgxfQ.Wr_-EIM-2pc5hTLqjA'
            . 'NpaQzh6nM6qzWRe8qqDh5dhq0';

        $requestContainer = [];
        $accessToken = self::createAccessToken(
            $requestContainer,
            new MockHandler(),
            '',
            '',
            [AccessToken::ACCESS_TOKEN_COOKIE_NAME => $token],
            $this->createMock(JWTMockCreator::class)
        );

        self::assertTrue($accessToken->exists(), 'AccessToken does not exist');
        self::assertEquals('57e42448-ba5e-3af8-be01-b1c86379d517', $accessToken->getJTI());
        self::assertEquals(81, $accessToken->getUserId());
        self::assertEmpty($accessToken->getRoleIds());
        self::assertTrue($accessToken->hasPermissionKey('user.user.read'));
        self::assertTrue($accessToken->hasPermissionKey('user.user.update.self'));
        self::assertFalse($accessToken->hasPermissionKey('user.user.update'));
        self::assertCount(0, $requestContainer);
    }

    public function testValidRefreshToken(): void
    {
        $refreshToken = 'eyJhbGciOiJIUzI1NiIsImtpZCI6InNpbTIifQ.eyJleHAiOjE1OTE2MDU1MjUsImlzcyI6Imh0dHBzO'
            . 'i8vZXB1YmxpLmRlIiwianRpIjoiNTdlNDI0NDgtYmE1ZS0zYWY4LWJlMDEtYjFjODYzNzlkNTE3Iiwicm9sZXMiOl'
            . 'sicmVmcmVzaF90b2tlbiJdLCJzdWIiOiJleG1hcGxrZTFAZXhhbXBsZS5jb20iLCJ1c2VyX2lkIjo4MX0._Yfe8tj'
            . 'sxulMaNv7G4EIXyCnCT4TJPnXozCmNshAISI';

        $requestContainer = [];
        $accessToken = self::createAccessToken(
            $requestContainer,
            new MockHandler(),
            '',
            '',
            [AccessToken::ACCESS_TOKEN_COOKIE_NAME => $refreshToken],
            $this->createMock(JWTMockCreator::class)
        );

        self::assertTrue($accessToken->exists(), 'RereshToken does not exist');
        self::assertEquals('57e42448-ba5e-3af8-be01-b1c86379d517', $accessToken->getJTI());
        self::assertEquals(81, $accessToken->getUserId());
        self::assertEmpty($accessToken->getRoleIds());
        self::assertFalse($accessToken->hasPermissionKey('user.user.read'));
        self::assertFalse($accessToken->hasPermissionKey('user.user.update.self'));
        self::assertFalse($accessToken->hasPermissionKey('user.user.update'));
        self::assertCount(0, $requestContainer);
    }

    public function testInvalidToken(): void
    {
        $token = 'fsfesf.fesfsef.sefesfesyJleHAiOjE1';

        $requestContainer = [];
        $accessToken = self::createAccessToken(
            $requestContainer,
            new MockHandler(),
            '',
            '',
            [AccessToken::ACCESS_TOKEN_COOKIE_NAME => $token],
            $this->createMock(JWTMockCreator::class)
        );

        self::assertFalse($accessToken->exists());
        self::assertNull($accessToken->getJTI());
        self::assertNull($accessToken->getUserId());
        self::assertEmpty($accessToken->getRoleIds());
        self::assertFalse($accessToken->hasPermissionKey('user.user.read'));
        self::assertFalse($accessToken->hasPermissionKey('user.user.update.self'));
        self::assertFalse($accessToken->hasPermissionKey('user.user.update'));
        self::assertCount(0, $requestContainer);
    }

    public function testEmptyToken(): void
    {
        $token = '';

        $requestContainer = [];
        $accessToken = self::createAccessToken(
            $requestContainer,
            new MockHandler(),
            '',
            '',
            [AccessToken::ACCESS_TOKEN_COOKIE_NAME => $token],
            $this->createMock(JWTMockCreator::class)
        );

        self::assertFalse($accessToken->exists());
        self::assertNull($accessToken->getJTI());
        self::assertNull($accessToken->getUserId());
        self::assertEmpty($accessToken->getRoleIds());
        self::assertFalse($accessToken->hasPermissionKey('user.user.read'));
        self::assertFalse($accessToken->hasPermissionKey('user.user.update.self'));
        self::assertFalse($accessToken->hasPermissionKey('user.user.update'));
        self::assertCount(0, $requestContainer);
    }

    public function testGetPermissionsOfUser(): void
    {
        $token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiIsImtpZCI6InNpbTIifQ.eyJpc3MiOiJodHRwczpcL1wvZXB1Y'
            . 'mxpLmRlIiwic3ViIjoicy56aWNrQGVwdWJsaS5jb20iLCJ1c2VyX2lkIjo3LCJqdGkiOiI5MDlhMGM4OS0xZGYwLTRkYm'
            . 'QtOTIzZC1iZjljNjYxYTIzMmQiLCJleHAiOjE2MDQzMTg3NDQsInR5cGUiOiJhY2Nlc3NfdG9rZW4iLCJyb2xlX2lkIjo'
            . 'zfQ._3FUdHqY6N6BSA-atg5sltHHmx3PfB9bwwaljkkP_R0';

        $requestContainer = [];
        $jwtMockCreator = $this->createMock(JWTMockCreator::class);
        $jwtMockCreator->expects(self::once())
            ->method('createJsonWebToken')
            ->with(['user.user.user_get_aggregated_permissions'])
            ->willReturn('jsonWebToken');
        $jwtMockCreator->expects(self::once())
            ->method('createCookieJar')
            ->with('jsonWebToken', 'http://user')
            ->willReturn(new CookieJar());

        $accessToken = self::createAccessToken(
            $requestContainer,
            new MockHandler(
                [
                    new Response(200, [], json_encode(["user.user.read"])),
                ]
            ),
            'api/users/{user_id}/aggregated-permissions',
            'user.user.user_get_aggregated_permissions',
            [AccessToken::ACCESS_TOKEN_COOKIE_NAME => $token],
            $jwtMockCreator,
            false
        );

        self::assertTrue($accessToken->exists(), 'AccessToken does not exist');
        self::assertEquals('909a0c89-1df0-4dbd-923d-bf9c661a232d', $accessToken->getJTI());
        self::assertEquals(7, $accessToken->getUserId());
        self::assertTrue($accessToken->hasPermissionKey('user.user.read'));
        self::assertFalse($accessToken->hasPermissionKey('user.user.update.self'));
        self::assertFalse($accessToken->hasPermissionKey('user.user.update'));

        self::assertCount(1, $requestContainer);
        /** @var \GuzzleHttp\Psr7\Request $request */
        $request = $requestContainer[0]['request'];
        self::assertEquals('GET', $request->getMethod());
        self::assertEquals('http://user/api/users/7/aggregated-permissions', $request->getUri());
    }

    public function testGetPermissionsOfUserWithoutReplacementOfUserId(): void
    {
        $token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiIsImtpZCI6InNpbTIifQ.eyJpc3MiOiJodHRwczpcL1wvZXB1Y'
            . 'mxpLmRlIiwic3ViIjoicy56aWNrQGVwdWJsaS5jb20iLCJ1c2VyX2lkIjo3LCJqdGkiOiI5MDlhMGM4OS0xZGYwLTRkYm'
            . 'QtOTIzZC1iZjljNjYxYTIzMmQiLCJleHAiOjE2MDQzMTg3NDQsInR5cGUiOiJhY2Nlc3NfdG9rZW4iLCJyb2xlX2lkIjo'
            . 'zfQ._3FUdHqY6N6BSA-atg5sltHHmx3PfB9bwwaljkkP_R0';

        $requestContainer = [];
        $jwtMockCreator = $this->createMock(JWTMockCreator::class);
        $jwtMockCreator->expects(self::once())
            ->method('createCookieJar')
            ->willReturn(new CookieJar());

        $accessToken = self::createAccessToken(
            $requestContainer,
            new MockHandler(
                [
                    new Response(200, [], json_encode(["user.user.read"])),
                ]
            ),
            'api/users/aggregated-permissions',
            'user.user.user_get_aggregated_permissions',
            [AccessToken::ACCESS_TOKEN_COOKIE_NAME => $token],
            $jwtMockCreator,
            false
        );

        self::assertTrue($accessToken->exists(), 'AccessToken does not exist');
        self::assertTrue($accessToken->hasPermissionKey('user.user.read'));

        self::assertCount(1, $requestContainer);
        /** @var \GuzzleHttp\Psr7\Request $request */
        $request = $requestContainer[0]['request'];
        self::assertEquals('GET', $request->getMethod());
        self::assertEquals('http://user/api/users/aggregated-permissions', $request->getUri());
    }

    public function testGetPermissionsOfUserWithNegativeUserId(): void
    {
        $token = 'ImVtcHR5Ig==.eyJpc3MiOiJodHRwczpcL1wvZXB1YmxpLmRlIiwic3ViIjoiLTEiLCJ1c2VyX2lkIjotMSwia'
            . 'nRpIjoiLTEiLCJleHAiOjE2MTc4NzIyNTgsInJvbGVzIjpbImFjY2Vzc190b2tlbiJdLCJwZXJtaXNzaW9ucyI6Wy'
            . 'JwZXJtaXNzaW9uLnBlcm0iXX0=.ImVtcHR5Ig==';

        $requestContainer = [];
        $accessToken = self::createAccessToken(
            $requestContainer,
            new MockHandler(),
            'api/users/aggregated-permissions',
            'user.user.user_get_aggregated_permissions',
            [AccessToken::ACCESS_TOKEN_COOKIE_NAME => $token],
            $this->createMock(JWTMockCreator::class),
            false
        );

        self::assertTrue($accessToken->exists(), 'AccessToken does not exist');

        self::assertCount(0, $requestContainer);
    }

    /**
     * @dataProvider provideExceptions
     * @param MockHandler $mockHandler
     * @param string $expectedError
     */
    public function testGetPermissionsOfUserOnError(MockHandler $mockHandler, string $expectedError): void
    {
        $token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiIsImtpZCI6InNpbTIifQ.eyJpc3MiOiJodHRwczpcL1wvZXB1Y'
            . 'mxpLmRlIiwic3ViIjoicy56aWNrQGVwdWJsaS5jb20iLCJ1c2VyX2lkIjo3LCJqdGkiOiI5MDlhMGM4OS0xZGYwLTRkYm'
            . 'QtOTIzZC1iZjljNjYxYTIzMmQiLCJleHAiOjE2MDQzMTg3NDQsInR5cGUiOiJhY2Nlc3NfdG9rZW4iLCJyb2xlX2lkIjo'
            . 'zfQ._3FUdHqY6N6BSA-atg5sltHHmx3PfB9bwwaljkkP_R0';

        $requestContainer = [];
        $jwtMockCreator = $this->createMock(JWTMockCreator::class);
        $jwtMockCreator->expects(self::once())
            ->method('createCookieJar')
            ->willReturn(new CookieJar());

        $accessToken = self::createAccessToken(
            $requestContainer,
            $mockHandler,
            '',
            '',
            [AccessToken::ACCESS_TOKEN_COOKIE_NAME => $token],
            $jwtMockCreator,
            false
        );

        try {
            $accessToken->exists();
            self::assertTrue(false);
        } catch (RuntimeException $e) {
            self::assertEquals($expectedError, $e->getMessage());
        }
    }

    public function provideExceptions(): array
    {
        return [
            'invalid status code' => [
                new MockHandler(
                    [
                        new Response(204, [], ''),
                    ]
                ),
                'Expected status code 200. Received instead: 204'
            ],
            'server exception' => [
                new MockHandler(
                    [
                        new ServerException(
                            'Error Communicating with Server',
                            new \GuzzleHttp\Psr7\Request('GET', ''),
                            new Response(500, [], 'body')
                        ),
                    ]
                ),
                'Status Code: 500\nBody: body'
            ],
            'client exception' => [
                new MockHandler(
                    [
                        new ClientException(
                            'Error Communicating with Server',
                            new \GuzzleHttp\Psr7\Request('GET', ''),
                            new Response(400, [], 'body')
                        ),
                    ]
                ),
                'Status Code: 400\nBody: body'
            ],
        ];
    }
}
