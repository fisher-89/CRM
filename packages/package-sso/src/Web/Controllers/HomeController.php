<?php

declare(strict_types=1);

namespace Fisher\SSO\Web\Controllers;

class HomeController
{
    public function index()
    {
        return view('package-sso::welcome');
    }
}
