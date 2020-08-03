<?php

namespace Epubli\PermissionBundle\Traits;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Trait SelfPermissionTrait
 * This trait is a default implementation of the SelfPermissionInterface
 * @package Epubli\PermissionBundle\Traits
 */
trait SelfPermissionTrait
{
    /**
     * @inheritDoc
     */
    public function getUserIdForPermissionBundle(): ?int
    {
        return $this->getUserId();
    }

    /**
     * @inheritDoc
     */
    public function getFieldNameOfUserIdForPermissionBundle(): string
    {
        return 'user_id';
    }

    /**
     * @inheritDoc
     */
    public function getPrimaryIdsWhichBelongToUser(EntityManagerInterface $entityManager, int $userId): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function hasUserIdProperty(): bool
    {
        return true;
    }
}