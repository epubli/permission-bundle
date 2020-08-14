<?php

namespace Epubli\PermissionBundle\Tests\Service;

use Epubli\PermissionBundle\Service\AccessToken;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class AccessTokenTest extends TestCase
{
    public function testValidAccessToken(): void
    {
        $accessToken = 'eyJhbGciOiJIUzI1NiIsImtpZCI6InNpbTIifQ.eyJleHAiOjE1OTE2MDE5MjUsImlzcyI6Imh0dHBzO'
            . 'i8vZXB1YmxpLmRlIiwianRpIjoiNTdlNDI0NDgtYmE1ZS0zYWY4LWJlMDEtYjFjODYzNzlkNTE3IiwicGVybWlzc'
            . '2lvbnMiOlsidXNlci51c2VyLnJlYWQiLCJ1c2VyLnVzZXIudXBkYXRlLnNlbGYiXSwicm9sZXMiOlsiYWNjZXNzX'
            . '3Rva2VuIl0sInN1YiI6ImV4bWFwbGtlMUBleGFtcGxlLmNvbSIsInVzZXJfaWQiOjgxfQ.Wr_-EIM-2pc5hTLqjA'
            . 'NpaQzh6nM6qzWRe8qqDh5dhq0';

        $accessToken = new AccessToken(
            $this->createRequestStack([AccessToken::ACCESS_TOKEN_COOKIE_NAME => $accessToken])
        );

        $this->assertTrue($accessToken->exists(), 'AccessToken does not exist');
        $this->assertEquals('57e42448-ba5e-3af8-be01-b1c86379d517', $accessToken->getJTI());
        $this->assertEquals(81, $accessToken->getUserId());
        $this->assertTrue($accessToken->hasPermissionKey('user.user.read'));
        $this->assertTrue($accessToken->hasPermissionKey('user.user.update.self'));
        $this->assertFalse($accessToken->hasPermissionKey('user.user.update'));
    }

    /**
     * @param array $cookies
     * @return RequestStack
     */
    private function createRequestStack(array $cookies): RequestStack
    {
        $requestStack = new RequestStack();
        $request = new Request([], [], [], $cookies, [], []);
        $requestStack->push($request);
        return $requestStack;
    }

    public function testValidRefreshToken(): void
    {
        $refreshToken = 'eyJhbGciOiJIUzI1NiIsImtpZCI6InNpbTIifQ.eyJleHAiOjE1OTE2MDU1MjUsImlzcyI6Imh0dHBzO'
            . 'i8vZXB1YmxpLmRlIiwianRpIjoiNTdlNDI0NDgtYmE1ZS0zYWY4LWJlMDEtYjFjODYzNzlkNTE3Iiwicm9sZXMiOl'
            . 'sicmVmcmVzaF90b2tlbiJdLCJzdWIiOiJleG1hcGxrZTFAZXhhbXBsZS5jb20iLCJ1c2VyX2lkIjo4MX0._Yfe8tj'
            . 'sxulMaNv7G4EIXyCnCT4TJPnXozCmNshAISI';

        $accessToken = new AccessToken(
            $this->createRequestStack([AccessToken::ACCESS_TOKEN_COOKIE_NAME => $refreshToken])
        );

        $this->assertTrue($accessToken->exists(), 'RereshToken does not exist');
        $this->assertEquals('57e42448-ba5e-3af8-be01-b1c86379d517', $accessToken->getJTI());
        $this->assertEquals(81, $accessToken->getUserId());
        $this->assertFalse($accessToken->hasPermissionKey('user.user.read'));
        $this->assertFalse($accessToken->hasPermissionKey('user.user.update.self'));
        $this->assertFalse($accessToken->hasPermissionKey('user.user.update'));
    }

    public function testInvalidToken(): void
    {
        $token = 'fsfesf.fesfsef.sefesfesyJleHAiOjE1';

        $accessToken = new AccessToken($this->createRequestStack([AccessToken::ACCESS_TOKEN_COOKIE_NAME => $token]));

        $this->assertFalse($accessToken->exists());
        $this->assertNull($accessToken->getJTI());
        $this->assertNull($accessToken->getUserId());
        $this->assertFalse($accessToken->hasPermissionKey('user.user.read'));
        $this->assertFalse($accessToken->hasPermissionKey('user.user.update.self'));
        $this->assertFalse($accessToken->hasPermissionKey('user.user.update'));
    }

    public function testEmptyToken(): void
    {
        $token = '';

        $accessToken = new AccessToken($this->createRequestStack([AccessToken::ACCESS_TOKEN_COOKIE_NAME => $token]));

        $this->assertFalse($accessToken->exists());
        $this->assertNull($accessToken->getJTI());
        $this->assertNull($accessToken->getUserId());
        $this->assertFalse($accessToken->hasPermissionKey('user.user.read'));
        $this->assertFalse($accessToken->hasPermissionKey('user.user.update.self'));
        $this->assertFalse($accessToken->hasPermissionKey('user.user.update'));
    }
}