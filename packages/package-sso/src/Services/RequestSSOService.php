<?php

namespace Fisher\SSO\Services;

use Illuminate\Http\Request;
use Fisher\SSO\Traits\UserHelper;
use Fisher\SSO\Traits\ResourceLibrary;

class RequestSSOService
{
    use UserHelper;
    use ResourceLibrary;

    public function __construct(Request $request)
    {
        $this->setHeader([
            'Accept' => 'application/json',
            'Authorization' => $request->header('Authorization')
        ]);
    }

    /**
     * oauth 客户端授权.
     *
     * @author 28youth
     * @return RequestSSOService
     */
    public function client()
    {
        $response = $this->request('post', '/oauth/token', [
            'headers' => ['Accept' => 'application/json'],
            'form_params' => [
                'grant_type' => 'client_credentials',
                'client_id' => config('sso.client_id'),
                'client_secret' => config('sso.client_secret'),
            ]
        ]);
        $this->setHeader([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $response['access_token']
        ]);

        return $this;
    }

    protected function getBaseUri(): string
    {
        return config('sso.host');
    }

    public function get($endpoint, $query = [], $header = [])
    {
        return $this->request('get', $endpoint, [
            'headers' => array_merge($header, $this->headers),
            'query' => $query,
        ]);
    }

    public function post($endpoint, $params = [], $header = [])
    {
        return $this->request('post', $endpoint, [
            'headers' => array_merge($header, $this->headers),
            'json' => $params,
        ]);
    }

    public function put($endpoint, $params = [], $header = [])
    {
        return $this->request('put', $endpoint, [
            'headers' => array_merge($header, $this->headers),
            'json' => $params,
        ]);
    }

    public function patch($endpoint, $params = [], $header = [])
    {
        return $this->request('patch', $endpoint, [
            'headers' => array_merge($header, $this->headers),
            'json' => $params,
        ]);
    }

    public function delete($endpoint, $params = [], $header = [])
    {
        return $this->request('delete', $endpoint, [
            'headers' => array_merge($header, $this->headers),
            'json' => $params,
        ]);
    }
}