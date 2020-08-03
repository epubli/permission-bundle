<?php

namespace Epubli\PermissionBundle\Filter;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;
use Epubli\PermissionBundle\Interfaces\SelfPermissionInterface;
use Epubli\PermissionBundle\Security\PermissionVoter;
use ReflectionException;
use RuntimeException;

/**
 * Class SelfPermissionFilter
 * @package Epubli\PermissionBundle\Filter
 */
final class SelfPermissionFilter extends SQLFilter
{
    /** @var PermissionVoter */
    private $permissionVoter;

    /** @var EntityManagerInterface */
    private $entityManager;

    /**
     * @param PermissionVoter $permissionVoter
     */
    public function setPermissionVoter(PermissionVoter $permissionVoter): void
    {
        $this->permissionVoter = $permissionVoter;
    }

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function setEntityManager(EntityManagerInterface $entityManager): void
    {
        $this->entityManager = $entityManager;
    }

    /**
     * This will be called before PermissionVoter::voteOnAttribute(...)
     * This method should only add filters on GET requests for entities for which the user has restricted access.
     * (e.g. The user can only see entities which belong to them.)
     * @param ClassMetadata $targetEntity
     * @param string $targetTableAlias
     * @return string
     * @throws ReflectionException
     */
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias): string
    {
        if (!$targetEntity->getReflectionClass()->implementsInterface(SelfPermissionInterface::class)) {
            return '';
        }

        /** @var SelfPermissionInterface $entity */
        $entity = $targetEntity->getReflectionClass()->newInstance();

        if (!$this->permissionVoter->needsFilter($entity)) {
            return '';
        }

        $userId = $this->permissionVoter->getAuthTokenUserId() ?? -1;

        if ($entity->hasUserIdProperty()) {
            $fieldName = $entity->getFieldNameOfUserIdForPermissionBundle();
            if ($fieldName === null || empty($fieldName)) {
                throw new RuntimeException(
                    'Make sure that getFieldNameOfUserIdForPermissionBundle '
                    . 'returns a non empty string for class ' . get_class($entity)
                );
            }

            return sprintf('%s.%s = %s', $targetTableAlias, $fieldName, $userId);
        }

        //Disable filters otherwise a select in the method getPrimaryIdsWhichBelongToUser(...) would result in an endless loop
        $this->entityManager->getFilters()->disable('epubli_permission_bundle_self_permission_filter');
        $ids = $entity->getPrimaryIdsWhichBelongToUser($this->entityManager, $userId);
        if (empty($ids)) {
            return sprintf('%s.id is null', $targetTableAlias);
        }
        return sprintf('%s.id IN (%s)', $targetTableAlias, implode(',', $ids));
    }
}