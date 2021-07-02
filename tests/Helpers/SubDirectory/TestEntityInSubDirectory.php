<?php

namespace Epubli\PermissionBundle\Tests\Helpers\SubDirectory;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
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
 *     },
 *     itemOperations={
 *          "get"={
 *              "validation_groups"={"all", "get"},
 *              "normalization_context"={"groups"={"all", "get"}},
 *              "security"="is_granted(null, object)",
 *          },
 *     }
 * )
 * @package Epubli\PermissionBundle\Tests\Helpers
 */
class TestEntityInSubDirectory
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

    public function getId(): ?int
    {
        return $this->id;
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
}