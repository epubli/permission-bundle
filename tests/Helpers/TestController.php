<?php

namespace Epubli\PermissionBundle\Tests\Helpers;

use Epubli\PermissionBundle\Annotation\Permission;

class TestController
{

    /**
     * @Permission(
     *     key="customPermissionForMethod1",
     *     description="This is a description"
     * )
     */
    public function method1()
    {
    }

    /**
     * @Permission(
     *     key="customPermissionForMethod2",
     *     description="This is a description2"
     * )
     * @Permission(
     *     key="secondCustomPermissionForMethod2",
     *     description="This is a description for the second custom permission"
     * )
     */
    public function method2()
    {
    }
}