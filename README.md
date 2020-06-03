# API Platform Permissions

Package to ease the use of permissions for microservices in e4 which use api-platform.

## Installation

Make sure Composer is installed globally, as explained in the
[installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

### Applications that use Symfony Flex

Open a command console, enter your project directory and execute:

```console
$ composer require epubli4/permission-bundle
```
Recommended for unit tests:
```console
$ composer require l0wskilled/api-platform-test >=0.1.21
```

### Applications that don't use Symfony Flex

#### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require epubli4/permission-bundle
```
Recommended for unit tests:
```console
$ composer require l0wskilled/api-platform-test >=0.1.21
```

#### Step 2: Enable the Bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `config/bundles.php` file of your project:

```php
// config/bundles.php

return [
    // ...
    Epubli\PermissionBundle\EpubliPermissionBundle::class => ['all' => true],
];
```

## Configuration

Make sure to insert the name of your microservice in `config/packages/epubli_permission.yaml` (create this file if it doesn't already exist)
Example:
```yaml
// config/packages/epubli_permission.yaml

epubli_permission:
  microservice_name: user
```

## Usage

### Generally

If you don't have anything specified in your @ApiResource you don't need to specify anything in your @Permission.
This is the bare minimum and will create permissions for every endpoint.
```php
use ApiPlatform\Core\Annotation\ApiResource;
use Epubli\PermissionBundle\Annotation\Permission;

/**
 * @ApiResource()
 * @Permission()
 */
class ExampleEntity
{

}
```

If you have something specified in your @ApiResource then this bundle will generate permissions only for the endpoints which exists.
In this example only permissions for "get", "post", "delete" will be generated:
```php
use ApiPlatform\Core\Annotation\ApiResource;
use Epubli\PermissionBundle\Annotation\Permission;

/**
 * @ApiResource(
 *      collectionOperations={
 *          'get',
 *          'post',
 *      },
 *      itemOperations={
 *          'get',
 *          'delete',
 *      }
 * )
 * @Permission()
 */
class ExampleEntity
{

}
```

If you don't want to have permissions for specific routes you need to specify the routes you want to have explicitly in @Permission.
In this example only permissions for "get" will be generated:
```php
use ApiPlatform\Core\Annotation\ApiResource;
use Epubli\PermissionBundle\Annotation\Permission;

/**
 * @ApiResource(
 *      collectionOperations={
 *          'get',
 *          'post',
 *      },
 *      itemOperations={
 *          'get',
 *          'delete',
 *      }
 * )
 * @Permission(
 *      collectionOperations={
 *          'get',
 *      },
 *      itemOperations={
 *          'get',
 *      }
 * )
 */
class ExampleEntity
{

}
```

### AuthToken

You can use this like a service. It supports autowiring. This gives you access to the properties of the access/refresh token of the user.

You should call `$authToken->isValid()` before any other method on this object to make sure that the token exists and is valid.

```php
namespace App\Controller;

use Epubli\PermissionBundle\Service\AuthToken;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class TestAction extends AbstractController
{
    public function __invoke(AuthToken $authToken)
    {
        var_dump('Is the token present and valid: ' . $authToken->isValid());
        var_dump('Is it an access token: ' . $authToken->isAccessToken());
        var_dump('or an refresh token: ' . $authToken->isRefreshToken());
        var_dump('This is the unique json token identifier: ' . $authToken->getJTI());
        var_dump('The id of the user: ' . $authToken->getUserId());
        var_dump('Checking for permissions: ' . $authToken->hasPermissionKey('user.user.delete'));
    }
}
```

### Tests

To test your application with this bundle you need some way to send JsonWebTokens to it, otherwise testing the endpoints would be impossible, your requests would be denied.

You will need at least version v0.1.21 of https://github.com/epubli/api-platform-test

The easiest way is to just include the following into your test cases. That way every request will have the access rights to every endpoint.
```php
use Epubli\ApiPlatform\TestBundle\OrmApiPlatformTestCase;
use Epubli\PermissionBundle\Traits\JsonWebTokenMockTrait;

class JsonWebTokenTest extends OrmApiPlatformTestCase
{
    use JsonWebTokenMockTrait;

    public static function setUpBeforeClass(): void
    {
        self::setUpJsonWebTokenMockCreator();
        self::$headers = ['HTTP_AUTHORIZATION' => self::$jsonWebTokenMockCreator->getMockAuthorizationHeaderForThisMicroservice()];
    }
}
```
If you want more control and don't want every request to have a token:
```php
use Epubli\ApiPlatform\TestBundle\OrmApiPlatformTestCase;
use Epubli\PermissionBundle\Traits\JsonWebTokenMockTrait;

class JsonWebTokenTest extends OrmApiPlatformTestCase
{
    use JsonWebTokenMockTrait;

    public static function setUpBeforeClass(): void
    {
        self::setUpJsonWebTokenMockCreator();
    }

    public function testRetrieveTheResourceList(): void
    {
        $this->request(
            '/api/json_web_tokens',
            'GET',
            null,
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => self::$jsonWebTokenMockCreator->getMockAuthorizationHeaderForThisMicroservice()
            ]
        );
    }
}
```

### Export Command
To export the permissions of your microservice to the user microservice
you need to execute the following __in the docker container__:
```console
$ php bin/console epubli:export-permissions
```

## Things which need to be done

- ApiPlatform Subresources
- Permissions for properties instead of whole entities
- Performance needs to be increased (Caching?)
- Permissions from the anonymous role need to be applied if no token exists