<?php
namespace Epubli\PermissionBundle\Traits;

use Epubli\PermissionBundle\Service\AccessToken;
use InvalidArgumentException;

trait ORMCreatorTrait
{
    /**
     * @Groups({"createdBy"})
     * @ORM\Column(name="created_by", type="integer", nullable=true)
     */
    private ?int $createdBy = null;

    public function setCreatedBy(AccessToken $accessToken): void {
        if($this->createdBy === null) {
            $this->createdBy = $accessToken->getUserId();
            return;
        }
        throw new InvalidArgumentException('Created by must not be changed!');
    }

    public function getCreatedBy(): ?int
    {
        return $this->createdBy;
    }
}