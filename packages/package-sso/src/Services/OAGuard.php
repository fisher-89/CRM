<?php
/**
 * Created by PhpStorm.
 * User: Fisher
 * Date: 2018/4/1 0001
 * Time: 22:19
 */

namespace Fisher\SSO\Services;


use Illuminate\Http\Request;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

class OAGuard implements Guard
{
    use GuardHelpers;

    protected $request;

    protected $inputKey = 'Authorization';

    protected $storageKey = 'Authorization';

    public function __construct(UserProvider $provider, Request $request)
    {
        $this->request = $request;
        $this->provider = $provider;
    }

    public function user()
    {
        if (! is_null($this->user) ) {
            return $this->user;
        }
        
        $token = $this->getTokenFromHeader();
        if (! empty($token) ) {
            $user = $this->provider->retrieveByCredentials([
                $this->storageKey => $token
            ]);
        }

        return $this->user = $user ?? null;
    }

    protected function getTokenFromHeader()
    {
        return $this->request->header($this->inputKey);
    }

    public function validate(array $credentials = [])
    {
        return true;
    }
}