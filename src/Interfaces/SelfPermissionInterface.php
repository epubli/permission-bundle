<?php

namespace Epubli\PermissionBundle\Interfaces;

use Doctrine\ORM\EntityManagerInterface;

interface SelfPermissionInterface
{
    /**
     * Returns the user_id of the owner of this entity or null if it can not be determined.
     * Most times this will be: <code>return $this->getUserId();</code>
     * @return int|null
     */
    public function getUserIdForPermissionBundle(): ?int;

    /**
     * Only needs to be implemented if hasUserIdProperty() is true.
     * Returns the id-field of the owner of this entity.
     * Most times this will be: <code>return 'user_id';</code>
     * @return string
     */
    public function getFieldNameOfUserIdForPermissionBundle(): string;

    /**
     * Only needs to be implemented if <code>hasUserIdProperty()</code> is false.
     * When implementing this you can not rely on the current instance of this entity
     * to be properly initialized.
     * This method needs to return all valid primary keys which belong to this user.
     * @param EntityManagerInterface $entityManager
     * @param int $userId
     * @return int[]
     */
    public function getPrimaryIdsWhichBelongToUser(EntityManagerInterface $entityManager, int $userId): array;

    /**
     * If true then <code>getFieldNameOfUserIdForPermissionBundle()</code> will be used.
     * If false then <code>getValidIds(...)</code> will be used
     * in the filter
     * @return bool
     */
    public function hasUserIdProperty(): bool;
}