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
  microservice_name: CHANGE_ME_TO_THE_NAME_OF_YOUR_MICROSERVICE

  # where the permissions of this microservice should be send to
  permission_export_route:
    base_uri: http://user
    path: /api/permissions/import
    permission: user.permission.create_permissions

  # where to get all permissions for a specific role
  aggregated_permissions_route:
    base_uri: http://user
    # {role_id} will be dynamically replaced
    path: /api/roles/{role_id}/aggregated-permissions
    permission: user.role.role_get_aggregated_permissions

  # where to get all possible permissions
  all_permissions_route:
    base_uri: http://user
    path: /api/permissions?page=1
    permission: user.permission.read
```
Activate the doctrine filter in `config/packages/doctrine.yaml`:
```yaml
// config/packages/doctrine.yaml

doctrine:
  orm:
    filters:
      epubli_permission_bundle_self_permission_filter:
        class: Epubli\PermissionBundle\Filter\SelfPermissionFilter
```

## Usage

### Generally

You need to specify the `security` key to enable this bundle for this endpoint.
```php
use ApiPlatform\Core\Annotation\ApiResource;

/**
 * @ApiResource(
 *     collectionOperations={
 *          "get"={
 *              "security"="is_granted(null, _api_resource_class)",
 *          },
 *          "post"={
 *              "security_post_denormalize"="is_granted(null, object)",
 *          },
 *     },
 *     itemOperations={
 *          "get"={
 *              "security"="is_granted(null, object)",
 *          },
 *          "delete"={
 *              "security"="is_granted(null, object)",
 *          },
 *          "put"={
 *              "security"="is_granted(null, object)",
 *          },
 *          "patch"={
 *              "security"="is_granted(null, object)",
 *          },
 *     }
 * )
 */
class ExampleEntity
{

}
```

If you want the bundle to differentiate between users who own an entity of this class or not,
then you need to implement the `SelfPermissionInterface`.
```php
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\EntityManagerInterface;
use Epubli\PermissionBundle\Interfaces\SelfPermissionInterface;

class ExampleEntity implements SelfPermissionInterface
{
    /**
     * @ORM\Column(type="integer")
     */
    private $user_id;

    public function getUserId(): ?int
    {
        return $this->user_id;
    }

    /**
     * @inheritDoc
     */
    public function getUserIdForPermissionBundle(): ?int
    {
        return $this->getUserId();
    }

    /**
     * @inheritDoc
     */
    public function getFieldNameOfUserIdForPermissionBundle(): string
    {
        return 'user_id';
    }

    /**
     * @inheritDoc
     */
    public function hasUserIdProperty(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getPrimaryIdsWhichBelongToUser(EntityManagerInterface $entityManager, int $userId): array
    {
        return [];
    }
}
```
Or use the `SelfPermissionTrait` for the default implementation of the `SelfPermissionInterface`:
```php
use Doctrine\ORM\Mapping as ORM;
use Epubli\PermissionBundle\Interfaces\SelfPermissionInterface;
use Epubli\PermissionBundle\Traits\SelfPermissionTrait;

class ExampleEntity implements SelfPermissionInterface
{
    use SelfPermissionTrait;

    /**
     * @ORM\Column(type="integer")
     */
    private $user_id;

    public function getUserId(): ?int
    {
        return $this->user_id;
    }
}
```
If you have an entity without an userId but with a relationship to another entity with an userId, you need to implement the methods of `SelfPermissionInterface` yourself.
```php
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Epubli\PermissionBundle\Interfaces\SelfPermissionInterface;

class ExampleEntity implements SelfPermissionInterface
{
    /**
     * @ORM\OneToOne(targetEntity=OtherEntity::class, inversedBy="exampleEntity", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $otherEntity;

    public function getOtherEntity(): ?OtherEntity
    {
        return $this->otherEntity;
    }

    public function getPrimaryIdsWhichBelongToUser(EntityManagerInterface $entityManager, int $userId): array
    {
        /** @var Query $query */
        $query = $entityManager->getRepository(__CLASS__)
            ->createQueryBuilder('c')
            ->select('c.id')
            ->join('c.otherEntity', 'u')
            ->where('u.userId = :userId')
            ->setParameter('userId', $userId)
            ->getQuery();

        return array_column($query->getArrayResult(), 'id');
    }

    public function getUserIdForPermissionBundle(): ?int
    {
        return $this->getOtherEntity()->getUserId();
    }

    public function getFieldNameOfUserIdForPermissionBundle(): string
    {
        return '';
    }

    public function hasUserIdProperty(): bool
    {
        return false;
    }
}
```

### AccessToken

You can use this like a service. It supports autowiring. This gives you access to the properties of the access token of the user.

```php
namespace App\Controller;

use Epubli\PermissionBundle\Service\AccessToken;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class TestAction extends AbstractController
{
    public function __invoke(AccessToken $accessToken)
    {
        var_dump('Is the token present and valid: ' . $accessToken->exists());
        var_dump('This is the unique json token identifier: ' . $accessToken->getJTI());
        var_dump('The id of the user: ' . $accessToken->getUserId());
        var_dump('Checking for permissions: ' . $accessToken->hasPermissionKey('user.user.delete'));
    }
}
```

### Custom permissions

For custom permissions to work you need to add an annotation to the method you are using it in.

Example:
```php
namespace App\Controller;

use Epubli\PermissionBundle\Annotation\Permission;
use Epubli\PermissionBundle\Service\AccessToken;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class TestController extends AbstractController
{
    /**
     * @Permission(
     *     key="customPermission1",
     *     description="This is a description"
     * )
     * @Permission(
     *     key="customPermission2",
     *     description="This is a description"
     * )
     */
    public function postTest(AccessToken $accessToken)
    {
        if (!$accessToken->exists()){
            throw new UnauthorizedHttpException('Bearer', 'Access-Token is invalid.');
        }

        if (!$accessToken->hasPermissionKey('test.customPermission1')){
            throw new AccessDeniedHttpException('Missing permission key: test.customPermission1');
        }

        //User is now authenticated and authorized for customPermission1

        if (!$accessToken->hasPermissionKey('test.customPermission2')){
            throw new AccessDeniedHttpException('Missing permission key:  test.customPermission2');
        }

        //User is now authenticated and authorized for customPermission2
    }
}
```
The name of your microservice will be prepended automatically to the permission key.

### Tests

To test your application with this bundle you need some way to send JsonWebTokens to it, otherwise testing the endpoints would be impossible, your requests would be denied.

You will need at least version v0.1.21 of https://github.com/epubli/api-platform-test

The easiest way is to just include the following into your test cases. That way every request will have the access rights to every endpoint.
```php
use Epubli\ApiPlatform\TestBundle\OrmApiPlatformTestCase;
use Epubli\PermissionBundle\Traits\JWTMockTrait;

class JsonWebTokenTest extends OrmApiPlatformTestCase
{
    use JWTMockTrait;

    public static function setUpBeforeClass(): void
    {
        self::setUpJsonWebTokenMockCreator();
    }

    public function setUp(): void
    {
        parent::setUp();
        self::$kernelBrowser->getCookieJar()->set(self::$cachedCookie);
    }
}
```
If you want more control and don't want every request to have a token:
```php
use Epubli\ApiPlatform\TestBundle\OrmApiPlatformTestCase;
use Epubli\PermissionBundle\Traits\JWTMockTrait;

class JsonWebTokenTest extends OrmApiPlatformTestCase
{
    use JWTMockTrait;

    public static function setUpBeforeClass(): void
    {
        self::setUpJsonWebTokenMockCreator();
    }

    public function testRetrieveTheResourceList(): void
    {
        self::$kernelBrowser->getCookieJar()->set(self::$cachedCookie);
        $this->request(
            '/api/json_web_tokens',
            'GET'
        );
    }
}
```

The trait `UnitTestTrait` exists to help you write unit tests for the common use cases.
This trait has a config (`self::$unitTestConfig`) in which you describe your entity.
This trait executes/generates unit tests for you.
It requires you to implement methods which return the data used in the unit tests.
Here is an example on how to use it for an entity which supports any operation:
```php
    
use Epubli\ApiPlatform\TestBundle\OrmApiPlatformTestCase;
use Epubli\PermissionBundle\Traits\JWTMockTrait;
use Epubli\PermissionBundle\Traits\UnitTestTrait;
use Epubli\PermissionBundle\UnitTestHelpers\UnitTestConfig;
use Epubli\PermissionBundle\UnitTestHelpers\UnitTestDeleteData;
use Epubli\PermissionBundle\UnitTestHelpers\UnitTestGetCollectionData;
use Epubli\PermissionBundle\UnitTestHelpers\UnitTestGetItemData;
use Epubli\PermissionBundle\UnitTestHelpers\UnitTestPostData;
use Epubli\PermissionBundle\UnitTestHelpers\UnitTestUpdateData;

class CompanyDataTest extends OrmApiPlatformTestCase
{
    use JWTMockTrait;
    use UnitTestTrait;

    public const RESOURCE_URI = '/api/company_datas/';

    public static function setUpBeforeClass(): void
    {
        self::setUpJsonWebTokenMockCreator();
        self::$unitTestConfig = new UnitTestConfig();
    }

    public function setUp(): void
    {
        parent::setUp();
        self::$kernelBrowser->getCookieJar()->set(self::$cachedCookie);
    }

    protected function getDemoEntity(): CompanyData
    {
        $userProfileTestDummy = (new UserProfileTest())->getDemoEntity();
        $this->persistAndFlush($userProfileTestDummy);

        $companyData = new CompanyData();
        $companyData->setCompanyName(self::$faker->company);
        $companyData->setValueAddedTaxNumber((string)self::$faker->randomNumber());
        $companyData->setUserProfile($userProfileTestDummy);
        $companyData->setCreatedAt(self::$faker->dateTimeBetween('-200 days', 'now'));
        $companyData->setUpdatedAt(self::$faker->dateTimeBetween($companyData->getCreatedAt(), 'now'));
        return $companyData;
    }

    public function getDeleteDataForPermissionBundle(): ?UnitTestDeleteData
    {
        /** @var CompanyData $companyData */
        $companyData = $this->findOne(CompanyData::class);
        $userId = $companyData->getUserProfile()->getUserId();

        return new UnitTestDeleteData(
            self::RESOURCE_URI . $companyData->getId(),
            'user-profile.company_data.delete',
            $userId
        );
    }

    public function getUpdateDataForPermissionBundle(): ?UnitTestUpdateData
    {
        /** @var CompanyData $companyData */
        $companyData = $this->findOne(CompanyData::class);
        $userId = $companyData->getUserProfile()->getUserId();

        return new UnitTestUpdateData(
            self::RESOURCE_URI . $companyData->getId(),
            'user-profile.company_data.update.companyName',
            $userId,
            json_encode(
                [
                    'companyName' => 'new Company Name',
                ]
            ),
            'companyName',
            'new Company Name'
        );
    }

    public function getPostDataForPermissionBundle(): ?UnitTestPostData
    {
        $companyData = $this->getDemoEntity();
        $userId = $companyData->getUserProfile()->getUserId();

        return new UnitTestPostData(
            self::RESOURCE_URI,
            'user-profile.company_data.create',
            $userId,
            json_encode(
                [
                    'companyName' => $companyData->getCompanyName(),
                    'valueAddedTaxNumber' => $companyData->getValueAddedTaxNumber(),
                    'userProfile' => '/api/user_profiles/' . $companyData->getUserProfile()->getId(),
                ]
            )
        );
    }

    public function getGetItemDataForPermissionBundle(): ?UnitTestGetItemData
    {
        /** @var CompanyData $companyData */
        $companyData = $this->findOne(CompanyData::class);
        $userId = $companyData->getUserProfile()->getUserId();

        return new UnitTestGetItemData(
            self::RESOURCE_URI . $companyData->getId(),
            'user-profile.company_data.read',
            $userId
        );
    }

    public function getGetCollectionDataForPermissionBundle(): ?UnitTestGetCollectionData
    {
        /** @var CompanyData $companyData */
        $companyData = $this->findOne(CompanyData::class);
        $userId = $companyData->getUserProfile()->getUserId();

        return new UnitTestGetCollectionData(
            self::RESOURCE_URI,
            'user-profile.company_data.read',
            $userId,
            1
        );
    }
}
```

If your entity does not support every operation, you need to adjust the config:
```php
use Epubli\ApiPlatform\TestBundle\OrmApiPlatformTestCase;
use Epubli\PermissionBundle\Traits\UnitTestTrait;
use Epubli\PermissionBundle\UnitTestHelpers\UnitTestConfig;

class ExampleTest extends OrmApiPlatformTestCase
{
    use UnitTestTrait;

    public static function setUpBeforeClass(): void
    {
        self::$unitTestConfig = new UnitTestConfig();

        // If you implemented the SelfPermissionInterface in your entity
        // then set this to true (defaults to true):
        self::$unitTestConfig->implementsSelfPermissionInterface = true;
        
        // If you do not have a DELETE route for your entity
        // then set this to false (defaults to true):
        self::$unitTestConfig->hasDeleteRoute = true;

        // If your DELETE route requires no acccess control
        // then set this to false (defaults to true):
        self::$unitTestConfig->hasSecurityOnDeleteRoute = true;

        //The config has booleans for every standard HTTP operation
    }
}
```

## Export Command
To export the permissions of your microservice to the user microservice
you need to execute the following __in the docker container__:
```console
$ php bin/console epubli:export-permissions
```

## Testing
Execute the following:
```console
$ make unit_test
```
or
```console
$ ./vendor/bin/simple-phpunit
```

## How to change/add code to this bundle
The easiest way to further develop this bundle is to copy the `src` folder oder to another project (e.g. user microservice).

Create a folder named `permission-bundle` in the project and copy the `src`  folder into it.

Then look for this in `composer.json`:
```
  "autoload": {
    "psr-4": {
      "App\\": "src/"
    }
  },
```
and replace it with:
```
  "autoload": {
    "psr-4": {
      "App\\": "src/",
      "Epubli\\PermissionBundle\\": "permission-bundle/src"
    }
  },
```
Delete the original `permission-bundle` in the `vendor` folder.

Execute:
```console
$ composer dump-autoload
```
You may need to delete a few things in `var/cache/dev`.

## Problems
When requesting multiple entities through a GET-Request `hydra:totalItems` can be incorrect when using the `SelfPermissionInterface`.

Because the paginator gets called before any filters are applied to the query the count of items/entities will be wrong.
`hydra:totalItems` does not equal the number of items/entities returned.

The solution in this thread did not work:
https://github.com/api-platform/core/issues/1185

## Things which need to be done

- ApiPlatform Subresources
- Permissions from the anonymous role need to be applied if no token exists