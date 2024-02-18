<?php

namespace Mxzcms\Modules\session;

use Illuminate\Support\Facades\Cache;

class SessionKey {

    const AdminInfo = 'admin_info';
    const HomeInfo = 'home_info';

    const allSession = [
        self::AdminInfo => '后台储存用户登录信息',
        self::HomeInfo => '前台储存用户登录信息',
    ];
}