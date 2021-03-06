<?php

namespace Epubli\PermissionBundle\Tests\Security;

use Doctrine\Common\Annotations\AnnotationReader;
use Epubli\PermissionBundle\Security\PermissionVoter;
use Epubli\PermissionBundle\Service\AccessToken;
use Epubli\PermissionBundle\Service\JWTMockCreator;
use Epubli\PermissionBundle\Tests\Helpers\TestEntityWithSelfPermissionInterface;
use Epubli\PermissionBundle\Tests\Service\CustomPermissionDiscoveryTest;
use Epubli\PermissionBundle\Tests\Service\JWTMockCreatorTest;
use Epubli\PermissionBundle\Tests\Service\PermissionDiscoveryTest;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class PermissionVoterTest extends TestCase
{
    /**
     * @param string[] $permissionKeys
     * @param string $requestUri
     * @param string $requestMethod
     * @param array $requestJson
     * @param bool $includeAccessToken
     * @return PermissionVoter
     */
    public static function createPermissionVoter(
        array $permissionKeys,
        string $requestUri,
        string $requestMethod,
        array $requestJson = [],
        bool $includeAccessToken = true
    ): PermissionVoter {
        $permissionDiscovery = PermissionDiscoveryTest::createPermissionDiscovery();
        $customPermissionDiscovery = CustomPermissionDiscoveryTest::createCustomPermissionDiscovery();

        $jwtMockCreator = JWTMockCreatorTest::createJWTMockCreator(
            $permissionDiscovery,
            $customPermissionDiscovery
        );

        $serverOptions =
            [
                'REQUEST_URI' => $requestUri,
                'REQUEST_METHOD' => $requestMethod
            ];

        if ($includeAccessToken) {
            $cookies = [AccessToken::ACCESS_TOKEN_COOKIE_NAME => $jwtMockCreator->createJsonWebToken($permissionKeys)];
        } else {
            $cookies = [];
        }

        $requestStack = new RequestStack();
        $request = new Request(
            [],
            [],
            [],
            $cookies,
            [],
            $serverOptions,
            json_encode($requestJson)
        );
        $requestStack->push($request);

        return new PermissionVoter(
            new AnnotationReader(),
            self::createAccessToken($requestStack),
            $requestStack,
            $permissionDiscovery
        );
    }

    /**
     * @param RequestStack $requestStack
     * @return AccessToken
     */
    private static function createAccessToken(RequestStack $requestStack):AccessToken
    {
        $requestContainer = [];
        $handlerStack = HandlerStack::create(new MockHandler());
        $history = Middleware::history($requestContainer);
        $handlerStack->push($history);

        return new AccessToken(
            new Client(['handler' => $handlerStack, 'base_uri' => 'http://user']),
            '',
            '',
            true,
            $requestStack,
            (new self)->createMock(JWTMockCreator::class)
        );
    }

    public function testAccessGrantedOnDelete(): void
    {
        $voter = self::createPermissionVoter(
            [
                'test.test_entity_with_self_permission_interface.delete.self',
            ],
            '/api/test_entity_with_self_permission_interfaces/1',
            'DELETE'
        );

        $entity = new TestEntityWithSelfPermissionInterface(-1);

        self::assertEquals(
            PermissionVoter::ACCESS_GRANTED,
            $voter->vote($this->createMock(TokenInterface::class), $entity, [null])
        );
    }

    public function testAccessGrantedOnUpdate(): void
    {
        $voter = self::createPermissionVoter(
            [
                'test.test_entity_with_self_permission_interface.update.someString.self',
            ],
            '/api/test_entity_with_self_permission_interfaces/1',
            'PATCH',
            [
                'someString' => 'hallo'
            ]
        );

        $entity = new TestEntityWithSelfPermissionInterface(-1);

        self::assertEquals(
            PermissionVoter::ACCESS_GRANTED,
            $voter->vote($this->createMock(TokenInterface::class), $entity, [null])
        );
    }

    public function testAccessGrantedOnUpdateWithoutChanges(): void
    {
        $voter = self::createPermissionVoter(
            [],
            '/api/test_entity_with_self_permission_interfaces/1',
            'PATCH'
        );

        $entity = new TestEntityWithSelfPermissionInterface(-1);

        self::assertEquals(
            PermissionVoter::ACCESS_GRANTED,
            $voter->vote($this->createMock(TokenInterface::class), $entity, [null])
        );
    }

    public function testAccessGrantedOnUpdateWithMultipleChanges(): void
    {
        $voter = self::createPermissionVoter(
            [
                'test.test_entity_with_self_permission_interface.update.someString.self',
                'test.test_entity_with_self_permission_interface.update.someOtherString.self',
            ],
            '/api/test_entity_with_self_permission_interfaces/1',
            'PATCH',
            [
                'someString' => 'hallo',
                'someOtherString' => 'hallo'
            ]
        );

        $entity = new TestEntityWithSelfPermissionInterface(-1);

        self::assertEquals(
            PermissionVoter::ACCESS_GRANTED,
            $voter->vote($this->createMock(TokenInterface::class), $entity, [null])
        );
    }

    public function testAccessGrantedOnUpdateWithMultipleChangesButDifferentPermissionTypes(): void
    {
        $voter = self::createPermissionVoter(
            [
                'test.test_entity_with_self_permission_interface.update.someString',
                'test.test_entity_with_self_permission_interface.update.someOtherString.self',
            ],
            '/api/test_entity_with_self_permission_interfaces/1',
            'PATCH',
            [
                'someString' => 'hallo',
                'someOtherString' => 'hallo'
            ]
        );

        $entity = new TestEntityWithSelfPermissionInterface(-1);

        self::assertEquals(
            PermissionVoter::ACCESS_GRANTED,
            $voter->vote($this->createMock(TokenInterface::class), $entity, [null])
        );
    }

    public function testAccessDeniedExceptionOnDelete(): void
    {
        $voter = self::createPermissionVoter(
            [
                'test.test_entity_with_self_permission_interface.delete.self',
            ],
            '/api/test_entity_with_self_permission_interfaces/1',
            'DELETE'
        );

        $entity = new TestEntityWithSelfPermissionInterface(3);

        $this->expectException(AccessDeniedHttpException::class);
        self::assertEquals(
            PermissionVoter::ACCESS_DENIED,
            $voter->vote($this->createMock(TokenInterface::class), $entity, [null])
        );
    }

    public function testAccessDeniedExceptionOnUpdate(): void
    {
        $voter = self::createPermissionVoter(
            [
                'test.test_entity_with_self_permission_interface.update.someString.self',
            ],
            '/api/test_entity_with_self_permission_interfaces/1',
            'PATCH',
            [
                'someString' => 'hallo'
            ]
        );

        $entity = new TestEntityWithSelfPermissionInterface(3);

        $this->expectException(AccessDeniedHttpException::class);
        self::assertEquals(
            PermissionVoter::ACCESS_DENIED,
            $voter->vote($this->createMock(TokenInterface::class), $entity, [null])
        );
    }

    public function testAccessDeniedExceptionOnUpdateWithIncompletePermissions(): void
    {
        $voter = self::createPermissionVoter(
            [
                'test.test_entity_with_self_permission_interface.update.someString.self',
            ],
            '/api/test_entity_with_self_permission_interfaces/1',
            'PATCH',
            [
                'someString' => 'hallo',
                'someOtherString' => 'hallo'
            ]
        );

        $entity = new TestEntityWithSelfPermissionInterface(-1);

        $this->expectException(AccessDeniedHttpException::class);
        self::assertEquals(
            PermissionVoter::ACCESS_DENIED,
            $voter->vote($this->createMock(TokenInterface::class), $entity, [null])
        );
    }

    public function testAccessDeniedOnDelete(): void
    {
        $voter = self::createPermissionVoter(
            [],
            '/api/test_entity_with_self_permission_interfaces/1',
            'DELETE',
            [],
            false
        );

        $entity = new TestEntityWithSelfPermissionInterface(3);

        self::assertEquals(
            PermissionVoter::ACCESS_DENIED,
            $voter->vote($this->createMock(TokenInterface::class), $entity, [null])
        );
    }

    public function testAccessDeniedOnUpdate(): void
    {
        $voter = self::createPermissionVoter(
            [],
            '/api/test_entity_with_self_permission_interfaces/1',
            'PATCH',
            [
                'someString' => 'hallo'
            ],
            false
        );

        $entity = new TestEntityWithSelfPermissionInterface(3);

        self::assertEquals(
            PermissionVoter::ACCESS_DENIED,
            $voter->vote($this->createMock(TokenInterface::class), $entity, [null])
        );
    }
}
