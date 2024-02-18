<?php

namespace Mxzcms\Modules;


use Illuminate\Support\ServiceProvider;

class ModulesServiceProvider extends ServiceProvider
{
    /**
     * Booting the package.
     */
    public function boot()
    {

    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->app->register(ModuleServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
    }
}
