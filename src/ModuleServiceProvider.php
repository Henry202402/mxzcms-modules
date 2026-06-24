<?php

namespace Mxzcms\Modules;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;
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
        if ($this->isInstalled()) {
            $this->refreshActiveCacheSafely();
        } else {
            Cache::put(CacheKey::ModulesActive, [
                'install' => ['identification' => 'Install', 'status' => 1],
            ], 86400);
        }

        foreach ($this->getActiveModules() as $item) {
            $identification = trim((string) ($item['identification'] ?? ''));
            if ($identification === '') {
                continue;
            }
            $this->registerAppProviders($identification);
            $this->registerTranslations($identification);
            $this->registerConfig($identification);
            $this->registerViews($identification);
            $migrationPath = $this->modulePath($identification, 'Database/Migrations');
            if ($migrationPath && is_dir($migrationPath)) {
                $this->loadMigrationsFrom($migrationPath);
            }
        }
    }



    /**
     * Register config.
     *
     * @return void 暂时不需要这一步
     */
    protected function registerConfig($identification) {
        $configPath = $this->modulePath($identification, 'Config/config.php');
        if (!$configPath || !is_file($configPath)) {
            return;
        }
        //只需要集合软连接
        $this->publishes([
            $configPath => config_path(strtolower($identification) . '.php'),
        ], 'config');
        $this->mergeConfigFrom(
            $configPath, 'modules.' . strtolower($identification)
        );
        $config = $this->app->make('config');
        $links = array_merge(
            (array) $config->get('modules.' . strtolower($identification) . '.links', []),
            (array) $config->get('filesystems.links', [])
        );
        $config->set('filesystems.links', $links);
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews($identification) {
        $viewPath = resource_path('views/modules/' . strtolower($identification));

        $sourcePath = $this->modulePath($identification, 'Resources/views');
        if (!$sourcePath || !is_dir($sourcePath)) {
            return;
        }

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
        $moduleLangPath = $this->modulePath($identification, 'Resources/lang');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, strtolower($identification));
            $this->loadJsonTranslationsFrom($langPath, strtolower($identification));
        } elseif ($moduleLangPath && is_dir($moduleLangPath)) {
            $this->loadTranslationsFrom($moduleLangPath, strtolower($identification));
            $this->loadJsonTranslationsFrom($moduleLangPath, strtolower($identification));
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
        $providerPath = $this->modulePath($identification, 'Providers/AppServiceProvider.php');
        $providerClass = "\\Modules\\" . $identification . "\\Providers\\AppServiceProvider";
        if ($providerPath && is_file($providerPath) && class_exists($providerClass)) {
            $this->app->register($providerClass);
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

    private function isInstalled(): bool
    {
        return class_exists(\Modules\Install\Http\Controllers\InstallController::class)
            ? (bool) \Modules\Install\Http\Controllers\InstallController::checkInstall()
            : true;
    }

    private function getActiveModules(): array
    {
        $modules = Cache::get(CacheKey::ModulesActive, []);
        return is_array($modules) ? $modules : [];
    }

    private function refreshActiveCacheSafely(): void
    {
        if (!class_exists(\Modules\System\Services\ServiceModel::class) || !class_exists(\Modules\Main\Models\Modules::class)) {
            return;
        }

        $pluginsActive = CacheKey::PluginsActive;
        $modulesActive = CacheKey::ModulesActive;
        $moduleList = \Modules\System\Services\ServiceModel::getDataBystatu() ?: [];
        if (!is_array($moduleList)) {
            return;
        }

        $cachedCount = count((array) Cache::get($pluginsActive, [])) + count((array) Cache::get($modulesActive, []));
        if ($cachedCount === count($moduleList) && $cachedCount > 0) {
            return;
        }

        Cache::forget($pluginsActive);
        Cache::forget($modulesActive);

        $pluginCache = [];
        $moduleCache = [];
        foreach ($moduleList as $value) {
            if (!is_array($value) || empty($value['identification'])) {
                continue;
            }
            $key = strtolower((string) $value['identification']);
            if (($value['cloud_type'] ?? null) == \Modules\Main\Models\Modules::Plugin) {
                $pluginCache[$key] = $value;
            } else {
                $moduleCache[$key] = $value;
            }
        }

        Cache::put($pluginsActive, $pluginCache, 86400);
        Cache::put($modulesActive, $moduleCache, 86400);

        if (class_exists(\Modules\Main\Libs\UPDATECMS::class)) {
            try {
                call_user_func([new \Modules\Main\Libs\UPDATECMS(), 'statistic'], ['identification' => 'cms']);
            } catch (\Throwable $exception) {
            }
        }
    }

    private function modulePath(string $identification, string $path = ''): string
    {
        if (!function_exists('module_path')) {
            return '';
        }
        return module_path($identification, $path);
    }
}
