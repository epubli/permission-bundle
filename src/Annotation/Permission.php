<?php

namespace Epubli\PermissionBundle\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target("METHOD")
 * @Attributes(
 *     @Attribute("key", type="string"),
 *     @Attribute("description", type="string"),
 * )
 */
class Permission
{
    /**
     * @Required
     *
     * @var string
     */
    public $key;

    /**
     * @Required
     *
     * @var string
     */
    public $description;

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }
}