<?php

namespace Epubli\PermissionBundle\Filter;

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

    /**
     * @param PermissionVoter $permissionVoter
     */
    public function setPermissionVoter(PermissionVoter $permissionVoter): void
    {
        $this->permissionVoter = $permissionVoter;
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

        $fieldName = $entity->getFieldNameOfUserIdForPermissionBundle();
        if ($fieldName === null || empty($fieldName)) {
            throw new RuntimeException(
                'Make sure that getFieldNameOfUserIdForPermissionBundle '
                . 'returns a non empty string for class ' . get_class($entity)
            );
        }

        $userId = $this->permissionVoter->getAuthTokenUserId() ?? -1;

        return sprintf('%s.%s = %s', $targetTableAlias, $fieldName, $userId);
    }
}