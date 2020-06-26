<?php

namespace Epubli\PermissionBundle\Interfaces;

interface SelfPermissionInterface
{
    /**
     * Returns the user_id of the owner of this entity or null if it can not be determined.
     * Most times this will be: <code>return $this->getUserId();</code>
     * @return int|null
     */
    public function getUserIdForPermissionBundle(): ?int;

    /**
     * Returns the id-field of the owner of this entity.
     * Most times this will be: <code>return 'user_id';</code>
     * @return string
     */
    public function getFieldNameOfUserIdForPermissionBundle(): string;
}