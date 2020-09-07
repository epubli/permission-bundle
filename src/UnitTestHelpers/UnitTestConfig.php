<?php


namespace Epubli\PermissionBundle\UnitTestHelpers;


class UnitTestConfig
{
    /** @var bool */
    public $implementsSelfPermissionInterface = true;

    /** @var bool */
    public $hasDeleteRoute = true;
    /** @var bool */
    public $hasSecurityOnDeleteRoute = true;

    /** @var bool */
    public $hasPutRoute = true;
    /** @var bool */
    public $hasSecurityOnPutRoute = true;

    /** @var bool */
    public $hasPatchRoute = true;
    /** @var bool */
    public $hasSecurityOnPatchRoute = true;

    /** @var bool */
    public $hasGetItemRoute = true;
    /** @var bool */
    public $hasSecurityOnGetItemRoute = true;

    /** @var bool */
    public $hasGetCollectionRoute = true;
    /** @var bool */
    public $hasSecurityOnGetCollectionRoute = true;

    /** @var bool */
    public $hasPostRoute = true;
    /** @var bool */
    public $hasSecurityOnPostRoute = true;

    public function hasSelfDelete(): bool
    {
        return $this->hasDeleteRoute && $this->hasSecurityOnDeleteRoute && $this->implementsSelfPermissionInterface;
    }

    public function hasSelfPut(): bool
    {
        return $this->hasPutRoute && $this->hasSecurityOnPutRoute && $this->implementsSelfPermissionInterface;
    }

    public function hasSelfPatch(): bool
    {
        return $this->hasPatchRoute && $this->hasSecurityOnPatchRoute && $this->implementsSelfPermissionInterface;
    }

    public function hasSelfPost(): bool
    {
        return $this->hasPostRoute && $this->hasSecurityOnPostRoute && $this->implementsSelfPermissionInterface;
    }

    public function hasSelfGetItem(): bool
    {
        return $this->hasGetItemRoute && $this->hasSecurityOnGetItemRoute && $this->implementsSelfPermissionInterface;
    }

    public function hasSelfGetCollection(): bool
    {
        return $this->hasGetCollectionRoute && $this->hasSecurityOnGetCollectionRoute && $this->implementsSelfPermissionInterface;
    }
}