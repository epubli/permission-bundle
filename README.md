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

### Applications that don't use Symfony Flex

#### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require epubli4/permission-bundle
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

### AuthToken

You can use this like a service. It supports autowiring. This gives you access to the properties of the access/refresh token of the user.

You should call `$authToken->isValid()` before any other method on this object to make sure that the token exists and is valid.

__IMPORTANT: At this moment it does NOT support autowiring in the constructor!__

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

TODO

## TODO

TODO