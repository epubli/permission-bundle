<?php

namespace Epubli\PermissionBundle\Tests\Helpers;

/**
 * @package Epubli\PermissionBundle\Tests\Helpers
 */
class TestEntityWithNothingDefined
{
    /**
     * @var int|null
     */
    private $id;

    /**
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