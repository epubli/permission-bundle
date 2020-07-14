<?php

namespace Epubli\PermissionBundle\Tests\Security;

use Doctrine\Common\Annotations\AnnotationReader;
use Epubli\PermissionBundle\Security\PermissionVoter;
use Epubli\PermissionBundle\Service\AuthToken;
use Epubli\PermissionBundle\Service\JWTMockCreator;
use Epubli\PermissionBundle\Tests\Helpers\EmptyMockToken;
use Epubli\PermissionBundle\Tests\Helpers\TestEntityWithSelfPermissionInterface;
use Epubli\PermissionBundle\Tests\Service\PermissionDiscoveryTest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class PermissionVoterTest extends TestCase
{
    public function testAccessGrantedOnDelete(): void
    {
        $voter = $this->createPermissionVoter(
            [
                'test.test_entity_with_self_permission_interface.delete.self',
            ],
            '/api/test_entity_with_self_permission_interfaces/1',
            'DELETE'
        );

        $entity = new TestEntityWithSelfPermissionInterface(-1);

        $this->assertEquals(PermissionVoter::ACCESS_GRANTED, $voter->vote(new EmptyMockToken(), $entity, [null]));
    }

    public function testAccessGrantedOnUpdate(): void
    {
        $voter = $this->createPermissionVoter(
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

        $this->assertEquals(PermissionVoter::ACCESS_GRANTED, $voter->vote(new EmptyMockToken(), $entity, [null]));
    }

    public function testAccessGrantedOnUpdateWithoutChanges(): void
    {
        $voter = $this->createPermissionVoter(
            [],
            '/api/test_entity_with_self_permission_interfaces/1',
            'PATCH'
        );

        $entity = new TestEntityWithSelfPermissionInterface(-1);

        $this->assertEquals(PermissionVoter::ACCESS_GRANTED, $voter->vote(new EmptyMockToken(), $entity, [null]));
    }

    public function testAccessGrantedOnUpdateWithMultipleChanges(): void
    {
        $voter = $this->createPermissionVoter(
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

        $this->assertEquals(PermissionVoter::ACCESS_GRANTED, $voter->vote(new EmptyMockToken(), $entity, [null]));
    }

    public function testAccessDeniedExceptionOnDelete(): void
    {
        $voter = $this->createPermissionVoter(
            [
                'test.test_entity_with_self_permission_interface.delete.self',
            ],
            '/api/test_entity_with_self_permission_interfaces/1',
            'DELETE'
        );

        $entity = new TestEntityWithSelfPermissionInterface(3);

        $this->expectException(AccessDeniedHttpException::class);
        $this->assertEquals(PermissionVoter::ACCESS_DENIED, $voter->vote(new EmptyMockToken(), $entity, [null]));
    }

    public function testAccessDeniedExceptionOnUpdate(): void
    {
        $voter = $this->createPermissionVoter(
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
        $this->assertEquals(PermissionVoter::ACCESS_DENIED, $voter->vote(new EmptyMockToken(), $entity, [null]));
    }

    public function testAccessDeniedExceptionOnUpdateWithIncompletePermissions(): void
    {
        $voter = $this->createPermissionVoter(
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
        $this->assertEquals(PermissionVoter::ACCESS_DENIED, $voter->vote(new EmptyMockToken(), $entity, [null]));
    }

    public function testAccessDeniedOnDelete(): void
    {
        $voter = $this->createPermissionVoter(
            [],
            '/api/test_entity_with_self_permission_interfaces/1',
            'DELETE',
            [],
            false
        );

        $entity = new TestEntityWithSelfPermissionInterface(3);

        $this->assertEquals(PermissionVoter::ACCESS_DENIED, $voter->vote(new EmptyMockToken(), $entity, [null]));
    }

    public function testAccessDeniedOnUpdate(): void
    {
        $voter = $this->createPermissionVoter(
            [],
            '/api/test_entity_with_self_permission_interfaces/1',
            'PATCH',
            [
                'someString' => 'hallo'
            ],
            false
        );

        $entity = new TestEntityWithSelfPermissionInterface(3);

        $this->assertEquals(PermissionVoter::ACCESS_DENIED, $voter->vote(new EmptyMockToken(), $entity, [null]));
    }

    /**
     * @param string[] $permissionKeys
     * @param string $requestUri
     * @param string $requestMethod
     * @param array $requestJson
     * @param bool $includeAuthToken
     * @return PermissionVoter
     */
    private function createPermissionVoter(
        array $permissionKeys,
        string $requestUri,
        string $requestMethod,
        array $requestJson = [],
        bool $includeAuthToken = true
    ): PermissionVoter {
        $permissionDiscovery = PermissionDiscoveryTest::createPermissionDiscovery();
        $jwtMockCreator = new JWTMockCreator($permissionDiscovery);

        $serverOptions =
            [
                'REQUEST_URI' => $requestUri,
                'REQUEST_METHOD' => $requestMethod
            ];
        if ($includeAuthToken) {
            $serverOptions['HTTP_AUTHORIZATION'] = $jwtMockCreator->getMockAuthorizationHeader($permissionKeys);
        }

        $requestStack = new RequestStack();
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            $serverOptions,
            json_encode($requestJson)
        );
        $requestStack->push($request);

        return new PermissionVoter(
            new AnnotationReader(),
            new AuthToken($requestStack),
            $requestStack,
            $permissionDiscovery
        );
    }
}
