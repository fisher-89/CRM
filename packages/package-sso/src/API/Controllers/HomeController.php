<?php

declare(strict_types=1);

namespace Fisher\SSO\API\Controllers;

class HomeController
{
    public function index()
    {
    	dd(app('api')->getStaff('110105'));
        return trans('package-sso::messages.success');
    }
}
