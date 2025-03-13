<?php

namespace Mxzcms\Modules;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;
use Modules\Install\Http\Controllers\InstallController;
use Modules\Main\Libs\UPDATECMS;
use Mxzcms\Modules\cache\CacheKey;

class ModuleServiceProvider extends ServiceProvider {
    public function register() {
    }

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     *
     * @return void
     */
    public function boot() {
        //获取启用的模块
        $modulesActive = CacheKey::ModulesActive;
        $pluginsActive = CacheKey::PluginsActive;
        if (InstallController::checkInstall()) {
            $dataList = \Modules\System\Services\ServiceModel::getDataBystatu() ?: [];
            if (count($dataList) != (count(Cache::get($pluginsActive) ?: []) + count(Cache::get($modulesActive) ?: []))) {
                Cache::delete($pluginsActive);
                Cache::delete($modulesActive);
                call_user_func(array( new UPDATECMS(), "statistic"),array('identification' => 'cms'));
                foreach ($dataList as $value) {
                    if ($value['cloud_type'] == \Modules\Main\Models\Modules::Plugin) {
                        Cache::put($pluginsActive, array_merge(Cache::get($pluginsActive) ?: [], array(strtolower($value['identification']) => $value)),86400);
                    } else {
                        Cache::put($modulesActive, array_merge(Cache::get($modulesActive) ?: [], array(strtolower($value['identification']) => $value)),86400);
                    }
                }
            }
        } else {
            Cache::put($modulesActive, array_merge([], array('install' => ['identification' => "Install", 'status' => 1])),86400);
        }

        $modulesActvite = Cache::get(CacheKey::ModulesActive) ?: [];
        foreach ($modulesActvite as $item) {
            $this->registerAppProviders($item['identification']);
            $this->registerTranslations($item['identification']);
            $this->registerConfig($item['identification']);
            $this->registerViews($item['identification']);
            $this->loadMigrationsFrom(module_path($item['identification'], 'Database/Migrations'));

        }


    }


    /**
     * Register config.
     *
     * @return void 暂时不需要这一步
     */
    protected function registerConfig($identification) {
        if(!file_exists(module_path($identification, 'Config/config.php'))){return;}
        //只需要集合软连接
        $this->publishes([
            module_path($identification, 'Config/config.php') => config_path(strtolower($identification) . '.php'),
        ], 'config');
        $this->mergeConfigFrom(
            module_path($identification, 'Config/config.php'), 'modules.' . strtolower($identification)
        );
        $config = $this->app->make('config');
        $config->set('filesystems.links', array_merge(
            $config->get('modules.' . strtolower($identification) . '.links', []), $config->get('filesystems.links', [])
        ));
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews($identification) {
        $viewPath = resource_path('views/modules/' . strtolower($identification));

        $sourcePath = module_path($identification, 'Resources/views');

        $this->publishes([
            $sourcePath => $viewPath
        ], ['views', strtolower($identification) . '-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(strtolower($identification)), [$sourcePath]), strtolower($identification));

    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations($identification) {
        $langPath = resource_path('lang/modules/' . strtolower($identification));

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, strtolower($identification));
            $this->loadJsonTranslationsFrom($langPath, strtolower($identification));
        } else {
            $this->loadTranslationsFrom(module_path($identification, 'Resources/lang'), strtolower($identification));
            $this->loadJsonTranslationsFrom(module_path($identification, 'Resources/lang'), strtolower($identification));
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides() {
        return [];
    }

    public function registerAppProviders($identification) {
        if (file_exists(module_path($identification, "Providers/AppServiceProvider.php"))) {
            $this->app->register("\Modules\\" . $identification . "\\Providers\\AppServiceProvider");
        }
    }

    private function getPublishableViewPaths($moduleNameLower): array {
        $paths = [];
        foreach (\Config::get('view.paths') as $path) {
            if (is_dir($path . '/modules/' . $moduleNameLower)) {
                $paths[] = $path . '/modules/' . $moduleNameLower;
            }
        }
        return $paths;

    }
}
