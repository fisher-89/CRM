<?php

declare(strict_types=1);

namespace Fisher\SSO\Admin\Controllers;

class HomeController
{
    public function index()
    {
        return trans('package-sso::messages.success');
    }
}
