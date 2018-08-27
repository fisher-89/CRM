<?php
/**
 * Created by PhpStorm.
 * User: Fisher
 * Date: 2018/4/4 0004
 * Time: 20:56
 */

namespace Fisher\SSO\Services;

use Illuminate\Contracts\Auth\Authenticatable as UserContract;

class OAUser implements UserContract
{
    protected $attributes;

    public function __construct(array $attributes)
    {
        $this->attributes = $attributes;
    }

    public function getAuthIdentifierName()
    {
        return 'staff_sn';
    }

    public function getAuthIdentifier()
    {
        $name = $this->getAuthIdentifierName();
        return $this->attributes[$name];
    }

    public function getAuthPassword()
    {
        return $this->attributes['password'];
    }

    public function getRememberToken()
    {
        // TODO: Implement getRememberToken() method.
    }

    public function setRememberToken($value)
    {
        // TODO: Implement setRememberToken() method.
    }

    public function getRememberTokenName()
    {
        // TODO: Implement getRememberTokenName() method.
    }

    public function toArray()
    {
        return $this->attributes;
    }

    public function __get($key)
    {
        return $this->attributes[$key];
    }

    public function __set($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    public function __isset($key)
    {
        return isset($this->attributes[$key]);
    }

    public function __unset($key)
    {
        unset($this->attributes[$key]);
    }

    public function __toString()
    {
        return json_encode($this->attributes);
    }
}