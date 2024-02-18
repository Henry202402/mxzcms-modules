<?php

namespace Mxzcms\Modules\cache;

use Illuminate\Support\Facades\Cache;

class CacheKey {

    const ModulesActvite = 'modules-actvite';//所有模块

    const ModulesActive = 'modules-active';//启用的模块

    const PluginsActive = 'plugins-active';//启用的插件

    const ThemesActive = 'themes-active';//启用的主题

}