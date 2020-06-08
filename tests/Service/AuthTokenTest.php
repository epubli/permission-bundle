<?php

namespace Epubli\PermissionBundle\Tests\Service;

use Epubli\PermissionBundle\Service\AuthToken;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class AuthTokenTest extends TestCase
{
    public function testValidAccessToken(): void
    {
        $accessToken = 'eyJhbGciOiJIUzI1NiIsImtpZCI6InNpbTIifQ.eyJleHAiOjE1OTE2MDE5MjUsImlzcyI6Imh0dHBzO'
            . 'i8vZXB1YmxpLmRlIiwianRpIjoiNTdlNDI0NDgtYmE1ZS0zYWY4LWJlMDEtYjFjODYzNzlkNTE3IiwicGVybWlzc'
            . '2lvbnMiOlsidXNlci51c2VyLnJlYWQiLCJ1c2VyLnVzZXIudXBkYXRlLnNlbGYiXSwicm9sZXMiOlsiYWNjZXNzX'
            . '3Rva2VuIl0sInN1YiI6ImV4bWFwbGtlMUBleGFtcGxlLmNvbSIsInVzZXJfaWQiOjgxfQ.Wr_-EIM-2pc5hTLqjA'
            . 'NpaQzh6nM6qzWRe8qqDh5dhq0';

        $authToken = new AuthToken($this->createRequestStack($accessToken));

        $this->assertTrue($authToken->isValid(), 'AccessToken is not valid');
        $this->assertEquals('57e42448-ba5e-3af8-be01-b1c86379d517', $authToken->getJTI());
        $this->assertFalse($authToken->isRefreshToken());
        $this->assertTrue($authToken->isAccessToken());
        $this->assertEquals(81, $authToken->getUserId());
        $this->assertTrue($authToken->hasPermissionKey('user.user.read'));
        $this->assertTrue($authToken->hasPermissionKey('user.user.update.self'));
        $this->assertFalse($authToken->hasPermissionKey('user.user.update'));
    }

    /**
     * @param $token
     * @return RequestStack
     */
    private function createRequestStack($token): RequestStack
    {
        $requestStack = new RequestStack();
        $request = new Request([], [], [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);
        $requestStack->push($request);
        return $requestStack;
    }

    public function testValidRefreshToken(): void
    {
        $refreshToken = 'eyJhbGciOiJIUzI1NiIsImtpZCI6InNpbTIifQ.eyJleHAiOjE1OTE2MDU1MjUsImlzcyI6Imh0dHBzO'
            . 'i8vZXB1YmxpLmRlIiwianRpIjoiNTdlNDI0NDgtYmE1ZS0zYWY4LWJlMDEtYjFjODYzNzlkNTE3Iiwicm9sZXMiOl'
            . 'sicmVmcmVzaF90b2tlbiJdLCJzdWIiOiJleG1hcGxrZTFAZXhhbXBsZS5jb20iLCJ1c2VyX2lkIjo4MX0._Yfe8tj'
            . 'sxulMaNv7G4EIXyCnCT4TJPnXozCmNshAISI';

        $authToken = new AuthToken($this->createRequestStack($refreshToken));

        $this->assertTrue($authToken->isValid(), 'RereshToken is not valid');
        $this->assertEquals('57e42448-ba5e-3af8-be01-b1c86379d517', $authToken->getJTI());
        $this->assertFalse($authToken->isAccessToken());
        $this->assertTrue($authToken->isRefreshToken());
        $this->assertEquals(81, $authToken->getUserId());
        $this->assertFalse($authToken->hasPermissionKey('user.user.read'));
        $this->assertFalse($authToken->hasPermissionKey('user.user.update.self'));
        $this->assertFalse($authToken->hasPermissionKey('user.user.update'));
    }

    public function testInvalidToken(): void
    {
        $token = 'fsfesf.fesfsef.sefesfesyJleHAiOjE1';

        $authToken = new AuthToken($this->createRequestStack($token));

        $this->assertFalse($authToken->isValid());
        $this->assertNull($authToken->getJTI());
        $this->assertFalse($authToken->isAccessToken());
        $this->assertFalse($authToken->isRefreshToken());
        $this->assertNull($authToken->getUserId());
        $this->assertFalse($authToken->hasPermissionKey('user.user.read'));
        $this->assertFalse($authToken->hasPermissionKey('user.user.update.self'));
        $this->assertFalse($authToken->hasPermissionKey('user.user.update'));
    }

    public function testEmptyToken(): void
    {
        $token = '';

        $authToken = new AuthToken($this->createRequestStack($token));

        $this->assertFalse($authToken->isValid());
        $this->assertNull($authToken->getJTI());
        $this->assertFalse($authToken->isAccessToken());
        $this->assertFalse($authToken->isRefreshToken());
        $this->assertNull($authToken->getUserId());
        $this->assertFalse($authToken->hasPermissionKey('user.user.read'));
        $this->assertFalse($authToken->hasPermissionKey('user.user.update.self'));
        $this->assertFalse($authToken->hasPermissionKey('user.user.update'));
    }
}
