<?php

namespace Epubli\PermissionBundle\Traits;

use Epubli\PermissionBundle\Service\AccessToken;

trait ORMUpdaterTrait
{
    /**
     * @Groups({"updatedBy"})
     * @ORM\Column(name="updated_by", type="integer", nullable=true)
     */
    private ?int $updatedBy = null;

    public function setUpdatedBy(AccessToken $accessToken): void
    {
        $this->updatedBy = $accessToken->getUserId();
    }

    public function getUpdatedBy(): ?int
    {
        return $this->updatedBy;
    }
}