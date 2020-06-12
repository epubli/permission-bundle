<?php

namespace Epubli\PermissionBundle\Tests\Helpers;

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
 *          "post"={
 *              "validation_groups"={"all", "post"},
 *              "normalization_context"={"groups"={"all", "get"}},
 *              "denormalization_context"={"groups"={"all", "post"}, "disable_type_enforcement"=true},
 *          },
 *          "special_route"={
 *              "path"="/test_entity_with_specific_securitys/special_route",
 *              "status"=200,
 *              "method"="POST",
 *              "validation_groups"={"refresh_token"},
 *              "denormalization_context"={"groups"={"refresh_token"}, "disable_type_enforcement"=true},
 *              "security_post_denormalize"="is_granted(null, object)",
 *          },
 *     },
 *     itemOperations={
 *          "get"={
 *              "validation_groups"={"all", "get"},
 *              "normalization_context"={"groups"={"all", "get"}},
 *              "security"="is_granted(null, object)",
 *          },
 *          "delete",
 *          "put"={
 *              "validation_groups"={"all", "put"},
 *              "controller"=WriteUserAction::class,
 *              "normalization_context"={"groups"={"all", "get"}},
 *              "denormalization_context"={"groups"={"all", "put"}, "disable_type_enforcement"=true},
 *          },
 *          "patch"={
 *              "validation_groups"={"all", "patch"},
 *              "controller"=WriteUserAction::class,
 *              "normalization_context"={"groups"={"all", "get"}},
 *              "denormalization_context"={"groups"={"all", "patch"}, "disable_type_enforcement"=true},
 *          },
 *     }
 * )
 * @package Epubli\PermissionBundle\Tests\Helpers
 */
class TestEntityWithSpecificSecurity
{
    /**
     * @Groups({"get"})
     * @var int|null
     * @ApiPlatform\ApiProperty(writable=false)
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