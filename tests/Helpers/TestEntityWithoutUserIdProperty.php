<?php

namespace Epubli\PermissionBundle\Tests\Helpers;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\EntityManagerInterface;
use Epubli\PermissionBundle\Interfaces\SelfPermissionInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     collectionOperations={
 *          "get"={
 *              "validation_groups"={"all", "get"},
 *              "normalization_context"={"groups"={"all", "get"}},
 *              "security"="is_granted(null, _api_resource_class)",
 *          },
 *          "post"={
 *              "validation_groups"={"all", "post"},
 *              "normalization_context"={"groups"={"all", "get"}},
 *              "denormalization_context"={"groups"={"all", "post"}, "disable_type_enforcement"=true},
 *              "security_post_denormalize"="is_granted(null, object)",
 *          },
 *     },
 *     itemOperations={
 *          "get"={
 *              "validation_groups"={"all", "get"},
 *              "normalization_context"={"groups"={"all", "get"}},
 *              "security"="is_granted(null, object)",
 *          },
 *          "delete"={
 *              "security"="is_granted(null, object)",
 *          },
 *          "put"={
 *              "validation_groups"={"all", "put"},
 *              "normalization_context"={"groups"={"all", "get"}},
 *              "denormalization_context"={"groups"={"all", "put"}, "disable_type_enforcement"=true},
 *              "security"="is_granted(null, object)",
 *          },
 *          "patch"={
 *              "validation_groups"={"all", "patch"},
 *              "normalization_context"={"groups"={"all", "get"}},
 *              "denormalization_context"={"groups"={"all", "patch"}, "disable_type_enforcement"=true},
 *              "security"="is_granted(null, object)",
 *          },
 *     }
 * )
 * @package Epubli\PermissionBundle\Tests\Helpers
 */
class TestEntityWithoutUserIdProperty implements SelfPermissionInterface
{
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
    private $someOtherString;

    /**
     * TestEntityWithSelfPermissionInterface constructor.
     * @param int|null $id
     */
    public function __construct(?int $id = null)
    {
        $this->id = $id;
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
     * @return string
     */
    public function getSomeOtherString(): string
    {
        return $this->someOtherString;
    }

    /**
     * @param string $someOtherString
     */
    public function setSomeOtherString(string $someOtherString): void
    {
        $this->someOtherString = $someOtherString;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @inheritDoc
     */
    public function getUserIdForPermissionBundle(): ?int
    {
        return 5;
    }

    /**
     * @inheritDoc
     */
    public function getFieldNameOfUserIdForPermissionBundle(): string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function getPrimaryIdsWhichBelongToUser(EntityManagerInterface $entityManager, int $userId): array
    {
        return [2, 3];
    }

    /**
     * @inheritDoc
     */
    public function hasUserIdProperty(): bool
    {
        return false;
    }
}