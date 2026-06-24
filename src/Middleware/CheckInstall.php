<?php

namespace Mxzcms\Modules\Middleware;


use Closure;
use Modules\Install\Http\Controllers\InstallController;

class CheckInstall
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (app()->runningInConsole()) {
            return $next($request);
        }
        if ($request->is('install') || $request->is('install/*')) {
            return $next($request);
        }
        if (!class_exists(InstallController::class)) {
            return $next($request);
        }
        if (!InstallController::checkInstall()) {
            return redirect('/install');
        }
        return $next($request);
    }
}
