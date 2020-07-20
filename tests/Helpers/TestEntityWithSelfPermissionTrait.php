<?php

namespace Epubli\PermissionBundle\Tests\Helpers;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Epubli\PermissionBundle\Interfaces\SelfPermissionInterface;
use Epubli\PermissionBundle\Traits\SelfPermissionTrait;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource()
 * @package Epubli\PermissionBundle\Tests\Helpers
 */
class TestEntityWithSelfPermissionTrait implements SelfPermissionInterface
{
    use SelfPermissionTrait;

    /**
     * @Groups({"get"})
     * @var int|null
     * @ApiProperty(writable=false)
     */
    private $id;

    /**
     * @Groups({"all"})
     * @ApiProperty(
     *      attributes={
     *          "openapi_context"={
     *              "type"="string",
     *              "example"="test"
     *          }
     *      }
     * )
     * @Assert\Type(type="string", groups={"all"})
     * @Assert\Length(max=255, groups={"all"})
     * @Assert\NotBlank(groups={"get", "post", "put"})
     * @var string
     */
    private $someString;

    /**
     * @Groups({"all"})
     * @Assert\Type(type="integer", groups={"all"})
     * @Assert\NotBlank(groups={"all"})
     * @var int
     */
    private $userId;

    /**
     * TestEntityWithSelfPermissionTrait constructor.
     * @param int|null $id
     */
    public function __construct(?int $id = null)
    {
        $this->id = $id;
        $this->userId = 7;
    }

    /**
     * @return string
     */
    public function getSomeString(): string
    {
        return $this->someString;
    }

    /**
     * @param string $someString
     */
    public function setSomeString(string $someString): void
    {
        $this->someString = $someString;
    }

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     */
    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}