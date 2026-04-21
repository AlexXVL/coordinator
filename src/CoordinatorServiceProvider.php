<?php

namespace BinaryCats\Coordinator;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\LaravelPackageTools\Commands\InstallCommand;

class CoordinatorServiceProvider extends PackageServiceProvider
{
    /**
     * Configure Coordinator Package.
     *
     * @param  \Spatie\LaravelPackageTools\Package  $package
     * @return void
     */
    public function configurePackage(Package $package): void
    {
        $package
            ->name('coordinator')
            ->hasConfigFile()
            ->hasMigrations([
                'create_bookings_table',
                '2026_04_21 170300_alter_bookings_table_add_quantity_field',
            ])
            ->hasInstallCommand(function(InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations();
            });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return string[]
     */
    public function provides()
    {
        return [
        ];
    }
}
