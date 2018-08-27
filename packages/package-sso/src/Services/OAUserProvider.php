<?php
/**
 * Created by PhpStorm.
 * User: Fisher
 * Date: 2018/4/1 0001
 * Time: 20:46
 */

namespace Fisher\SSO\Services;


use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use Fisher\SSO\Traits\UserHelper;

class OAUserProvider implements UserProvider
{
    use UserHelper;

    protected function getBaseUri(): string
    {
        return config('sso.host');
    }

    public function retrieveById($identifier)
    {
        $user = $this->get('/api/staff/'.$identifier);

        return $this->getOAUser($user);
    }

    public function retrieveByCredentials(array $credentials)
    {
        $user = $this->setHeader($credentials)->get('/api/current-user');

        return $this->getOAUser($user);
    }

    protected function getOAUser($user)
    {
        if (is_array($user) && array_has($user, 'staff_sn')) {
            return new OAUser($user);
        }
    }

    public function retrieveByToken($identifier, $token)
    {
        // TODO: Implement retrieveByToken() method.
    }

    public function updateRememberToken(Authenticatable $user, $token)
    {
        // TODO: Implement updateRememberToken() method.
    }

    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        // TODO: Implement validateCredentials() method.
    }

}