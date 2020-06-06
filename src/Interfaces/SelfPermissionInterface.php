<?php

namespace Epubli\PermissionBundle\Interfaces;

interface SelfPermissionInterface
{
    /**
     * Returns the user_id of the owner of this entity or null if it can not be determined
     * @return int|null
     */
    public function getUserIdForPermissionBundle(): ?int;
}