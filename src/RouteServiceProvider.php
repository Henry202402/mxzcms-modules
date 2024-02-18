<?php

namespace Mxzcms\Modules;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Modules\Main\Http\Middleware\Cors;
use Modules\System\Models\Modules;
use Mxzcms\Modules\cache\CacheKey;
use Modules\Install\Http\Controllers\InstallController;

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
        foreach (Cache::get(CacheKey::ModulesActive) as $item) {
            $this->mapAdminRoutes($item['identification']);
            if ($item['status'] == 0) continue;
            $this->mapApiRoutes($item['identification']);
            $this->mapWebRoutes($item['identification']);
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
        if(!file_exists(module_path($identification, 'Routes/web.php'))){return;}
        Route::middleware('web')
            ->namespace("Modules\\".$identification."\\Http\\Controllers")
            ->group(module_path($identification, 'Routes/web.php'));

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
        if(!file_exists(module_path($identification, 'Routes/api.php'))){return;}
        Route::prefix('api')
            ->middleware(['api',Cors::class])
            ->namespace("Modules\\".$identification."\\Http\\Controllers")
            ->group(module_path($identification, 'Routes/api.php'));
    }


    protected function mapAdminRoutes($identification) {
        if(!file_exists(module_path($identification, 'Routes/admin.php'))){return;}
        Route::prefix('admin')
            ->middleware('web')
            ->namespace("Modules\\".$identification."\\Http\\Controllers")
            ->group(module_path($identification, 'Routes/admin.php'));
    }

}
