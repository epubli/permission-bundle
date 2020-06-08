<?php

namespace Epubli\PermissionBundle\Tests\Helpers;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @method array __serialize()
 * @method void __unserialize(array $data)
 * @method string[] getRoleNames()
 */
class EmptyMockToken implements TokenInterface
{

    public function serialize()
    {
    }

    public function unserialize($serialized)
    {
    }

    public function __toString()
    {
        return '';
    }

    public function getRoles()
    {
    }

    public function getCredentials()
    {
    }

    public function getUser()
    {
    }

    public function setUser($user)
    {
    }

    public function getUsername()
    {
    }

    public function isAuthenticated()
    {
    }

    public function setAuthenticated($isAuthenticated)
    {
    }

    public function eraseCredentials()
    {
    }

    public function getAttributes()
    {
    }

    public function setAttributes(array $attributes)
    {
    }

    public function hasAttribute($name)
    {
    }

    public function getAttribute($name)
    {
    }

    public function setAttribute($name, $value)
    {
    }

    public function __call($name, $arguments)
    {
    }
}