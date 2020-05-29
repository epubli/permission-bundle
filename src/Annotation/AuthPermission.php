<?php

namespace Epubli\PermissionBundle\Annotation;

/**
 * Class AuthPermission
 * @package Epubli\PermissionBundle\Annotation
 *
 * @Annotation
 * @Target("CLASS")
 */
class AuthPermission
{
    /**
     * @var string[]|null
     */
    public $collectionOperations;

    /**
     * @var string[]|null
     */
    public $itemOperations;

    /**
     * @var string[]|null
     */
    public $subresourceOperations;

    /**
     * @return string[]|null
     */
    public function getCollectionOperations(): ?array
    {
        return $this->collectionOperations;
    }

    /**
     * @return string[]|null
     */
    public function getItemOperations(): ?array
    {
        return $this->itemOperations;
    }

    /**
     * @return string[]|null
     */
    public function getSubresourceOperations(): ?array
    {
        return $this->subresourceOperations;
    }
}