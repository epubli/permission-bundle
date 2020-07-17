<?php

namespace Epubli\PermissionBundle\Tests\Filter;

use Doctrine\ORM\Mapping\ClassMetadata;
use Epubli\PermissionBundle\Filter\SelfPermissionFilter;
use Epubli\PermissionBundle\Security\PermissionVoter;
use Epubli\PermissionBundle\Tests\Helpers\MockEntityManager;
use Epubli\PermissionBundle\Tests\Helpers\TestEntityWithEverything;
use Epubli\PermissionBundle\Tests\Helpers\TestEntityWithSelfPermissionInterface;
use Epubli\PermissionBundle\Tests\Security\PermissionVoterTest;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;

class SelfPermissionFilterTest extends TestCase
{
    public function testSelfPermissionFilterOnGetSingleEntity(): void
    {
        $voter = PermissionVoterTest::createPermissionVoter(
            [
                'test.test_entity_with_self_permission_interface.read.self',
            ],
            '/api/test_entity_with_self_permission_interfaces/1',
            'GET'
        );

        $testEntity = new TestEntityWithSelfPermissionInterface();

        $filterStr = self::getSelfPermissionFilter($voter, $testEntity);

        $this->assertEquals('t.id = -1', $filterStr);
    }

    /**
     * @param PermissionVoter $permissionVoter
     * @param $entity
     * @return string
     * @throws ReflectionException
     */
    public static function getSelfPermissionFilter(PermissionVoter $permissionVoter, $entity): string
    {
        $selfPermissionFilter = new SelfPermissionFilter(new MockEntityManager());
        $selfPermissionFilter->setPermissionVoter($permissionVoter);

        $cm = new ClassMetadata('');
        $cm->reflClass = new ReflectionClass(get_class($entity));

        return $selfPermissionFilter->addFilterConstraint($cm, 't');
    }

    public function testSelfPermissionFilterOnGetCollection(): void
    {
        $voter = PermissionVoterTest::createPermissionVoter(
            [
                'test.test_entity_with_self_permission_interface.read.self',
            ],
            '/api/test_entity_with_self_permission_interfaces',
            'GET'
        );

        $testEntity = new TestEntityWithSelfPermissionInterface();

        $filterStr = self::getSelfPermissionFilter($voter, $testEntity);

        $this->assertEquals('t.id = -1', $filterStr);
    }

    public function testSelfPermissionFilterWithNoAuthToken(): void
    {
        $voter = PermissionVoterTest::createPermissionVoter(
            [
                'test.test_entity_with_self_permission_interface.read.self',
            ],
            '/api/test_entity_with_self_permission_interfaces/1',
            'GET',
            [],
            false
        );

        $testEntity = new TestEntityWithSelfPermissionInterface();

        $filterStr = self::getSelfPermissionFilter($voter, $testEntity);

        $this->assertEquals('', $filterStr);
    }

    public function testSelfPermissionFilterWithPermissionToReadEverything(): void
    {
        $voter = PermissionVoterTest::createPermissionVoter(
            [
                'test.test_entity_with_self_permission_interface.read',
            ],
            '/api/test_entity_with_self_permission_interfaces/1',
            'GET'
        );

        $testEntity = new TestEntityWithSelfPermissionInterface();

        $filterStr = self::getSelfPermissionFilter($voter, $testEntity);

        $this->assertEquals('', $filterStr);
    }

    public function testSelfPermissionFilterWithoutImplementedInterface(): void
    {
        $voter = PermissionVoterTest::createPermissionVoter(
            [
                'test.test_entity_with_everything.read.self',
            ],
            '/api/test_entity_with_everythings/1',
            'GET'
        );

        $testEntity = new TestEntityWithEverything();

        $filterStr = self::getSelfPermissionFilter($voter, $testEntity);

        $this->assertEquals('', $filterStr);
    }

    public function testSelfPermissionFilterWithoutPermissions(): void
    {
        $voter = PermissionVoterTest::createPermissionVoter(
            [],
            '/api/test_entity_with_self_permission_interfaces/1',
            'GET'
        );

        $testEntity = new TestEntityWithSelfPermissionInterface();

        $filterStr = self::getSelfPermissionFilter($voter, $testEntity);

        $this->assertEquals('', $filterStr);
    }

    public function testSelfPermissionFilterOnPatch(): void
    {
        $voter = PermissionVoterTest::createPermissionVoter(
            [
                'test.test_entity_with_self_permission_interface.read.self',
            ],
            '/api/test_entity_with_self_permission_interfaces/1',
            'PATCH'
        );

        $testEntity = new TestEntityWithSelfPermissionInterface();

        $filterStr = self::getSelfPermissionFilter($voter, $testEntity);

        $this->assertEquals('', $filterStr);
    }

    public function testSelfPermissionFilterOnPut(): void
    {
        $voter = PermissionVoterTest::createPermissionVoter(
            [
                'test.test_entity_with_self_permission_interface.read.self',
            ],
            '/api/test_entity_with_self_permission_interfaces/1',
            'PUT'
        );

        $testEntity = new TestEntityWithSelfPermissionInterface();

        $filterStr = self::getSelfPermissionFilter($voter, $testEntity);

        $this->assertEquals('', $filterStr);
    }


    public function testSelfPermissionFilterOnDelete(): void
    {
        $voter = PermissionVoterTest::createPermissionVoter(
            [
                'test.test_entity_with_self_permission_interface.read.self',
            ],
            '/api/test_entity_with_self_permission_interfaces/1',
            'DELETE'
        );

        $testEntity = new TestEntityWithSelfPermissionInterface();

        $filterStr = self::getSelfPermissionFilter($voter, $testEntity);

        $this->assertEquals('', $filterStr);
    }


    public function testSelfPermissionFilterOnPost(): void
    {
        $voter = PermissionVoterTest::createPermissionVoter(
            [
                'test.test_entity_with_self_permission_interface.read.self',
            ],
            '/api/test_entity_with_self_permission_interfaces',
            'POST'
        );

        $testEntity = new TestEntityWithSelfPermissionInterface();

        $filterStr = self::getSelfPermissionFilter($voter, $testEntity);

        $this->assertEquals('', $filterStr);
    }
}