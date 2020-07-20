<?php

namespace Epubli\PermissionBundle\Traits;

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
}