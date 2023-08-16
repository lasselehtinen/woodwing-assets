
[<img src="https://github-ads.s3.eu-central-1.amazonaws.com/support-ukraine.svg?t=1" />](https://supportukrainenow.org)

# API wrapper for WoodWing Assets

[![Latest Version on Packagist](https://img.shields.io/packagist/v/lasselehtinen/woodwing-assets.svg?style=flat-square)](https://packagist.org/packages/lasselehtinen/woodwing-assets)
[![Run tests](https://github.com/lasselehtinen/woodwing-assets/actions/workflows/run-tests.yml/badge.svg?branch=main)](https://github.com/lasselehtinen/woodwing-assets/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/lasselehtinen/woodwing-assets.svg?style=flat-square)](https://packagist.org/packages/lasselehtinen/woodwing-assets)

Package for doing REST API queries against Woodwings Elvis DAM (Digital Asset Management).

## Installation

You can install the package via composer:

```bash
composer require lasselehtinen/woodwing-assets
```
You can publish the config file with:

```bash
php artisan vendor:publish --tag="woodwing-assets-config"
```

This is the contents of the published config file:

```php
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | WoodWing Assets configuration
    |--------------------------------------------------------------------------
    |
    | Note! Remember to include the full API endpoint to the hostname
    |
     */

    'endpoint' => env('WOODWING_ASSETS_ENDPOINT', 'https://assets.example.com/services'),
    'username' => env('WOODWING_ASSETS_USERNAME', 'guest'),
    'password' => env('WOODWING_ASSETS_PASSWORD', 'guest'),
];
```

## Usage

```php
$assets = new \LasseLehtinen\Assets\Assets();
$searchResults = $assets->search(query: 'Jari Tervo', num: 2);
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/spatie/.github/blob/main/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Lasse Lehtinen](https://github.com/lasselehtinen)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
