<?php

namespace LasseLehtinen\Assets;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use LasseLehtinen\Assets\Commands\AssetsCommand;

class AssetsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('woodwing-assets')->hasConfigFile();
    }
}
