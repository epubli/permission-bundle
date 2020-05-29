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

TODO

## TODO

TODO