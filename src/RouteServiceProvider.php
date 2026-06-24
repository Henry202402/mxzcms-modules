<?php

namespace Mxzcms\Modules;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Mxzcms\Modules\cache\CacheKey;

class RouteServiceProvider extends ServiceProvider
{
    public function register()
    {

    }

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     *
     * @return void
     */
    public function boot()
    {
        $this->map();
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $activeModules = $this->getActiveModules();
        foreach ($activeModules as $item) {
            $identification = trim((string) ($item['identification'] ?? ''));
            if ($identification === '') {
                continue;
            }
            $this->mapAdminRoutes($identification);
            if (($item['status'] ?? 0) == 0) {
                continue;
            }
            $this->mapApiRoutes($identification);
            $this->mapWebRoutes($identification);
        }
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes($identification)
    {
        $routePath = $this->modulePath($identification, 'Routes/web.php');
        if (!$routePath || !is_file($routePath)) {
            return;
        }
        Route::middleware('web')
            ->namespace("Modules\\".$identification."\\Http\\Controllers")
            ->group($routePath);

    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes($identification)
    {
        $routePath = $this->modulePath($identification, 'Routes/api.php');
        if (!$routePath || !is_file($routePath)) {
            return;
        }
        $middlewares = ['api'];
        if (class_exists(\Modules\Main\Http\Middleware\Cors::class)) {
            $middlewares[] = \Modules\Main\Http\Middleware\Cors::class;
        }
        Route::prefix('api')
            ->middleware($middlewares)
            ->namespace("Modules\\".$identification."\\Http\\Controllers")
            ->group($routePath);
    }


    protected function mapAdminRoutes($identification) {
        $routePath = $this->modulePath($identification, 'Routes/admin.php');
        if (!$routePath || !is_file($routePath)) {
            return;
        }
        Route::prefix('admin')
            ->middleware('web')
            ->namespace("Modules\\".$identification."\\Http\\Controllers")
            ->group($routePath);
    }

    private function getActiveModules(): array
    {
        $modules = Cache::get(CacheKey::ModulesActive, []);
        $modules = is_array($modules) ? $modules : [];
        if (!array_key_exists('main', $modules) && function_exists('module_path')) {
            $mainHasRoutes = collect([
                module_path('Main', 'Routes/web.php'),
                module_path('Main', 'Routes/admin.php'),
                module_path('Main', 'Routes/api.php'),
            ])->contains(function ($path) {
                return is_string($path) && $path !== '' && is_file($path);
            });
            if ($mainHasRoutes) {
                $modules = array_merge([
                    'main' => ['identification' => 'Main', 'status' => 1],
                ], $modules);
            }
        }
        return $modules;
    }

    private function modulePath(string $identification, string $path = ''): string
    {
        if (!function_exists('module_path')) {
            return '';
        }
        return module_path($identification, $path);
    }

}
