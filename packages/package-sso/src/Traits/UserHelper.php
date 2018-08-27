<?php

namespace Fisher\SSO\Traits;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

trait UserHelper
{

    protected $headers;
    protected $withRealException = false;

    /**
     * Set headers.
     *
     * @param  array $headers
     */
    public function setHeader($headers = [])
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * Make a get request.
     *
     * @param string $endpoint
     * @param array $query
     * @param array $headers
     *
     * @return array
     */
    protected function get($endpoint, $query = [], $headers = [])
    {
        return $this->request('get', $endpoint, [
            'headers' => array_merge($headers, $this->headers),
            'query' => $query,
        ]);
    }

    /**
     * Make a post request.
     *
     * @param string $endpoint
     * @param array $params
     * @param array $headers
     *
     * @return array
     */
    protected function post($endpoint, $params = [], $headers = [])
    {
        return $this->request('post', $endpoint, [
            'headers' => array_merge($headers, $this->headers),
            'json' => $params,
        ]);
    }

    /**
     * Make a http request.
     *
     * @param string $method
     * @param string $endpoint
     * @param array $options http://docs.guzzlephp.org/en/latest/request-options.html
     *
     * @return array
     */
    protected function request($method, $endpoint, $options = [])
    {
        try {
            return $this->unwrapResponse($this->getHttpClient($this->getBaseOptions())->{$method}($endpoint, $options));
        } catch (ClientException $exception) {
            if ($this->withRealException) {
                throw $this->getApiException($exception);
            }
            throw $exception;
        }
    }

    /**
     * Return base Guzzle options.
     *
     * @return array
     */
    protected function getBaseOptions()
    {
        $options = [
            'base_uri' => method_exists($this, 'getBaseUri') ? $this->getBaseUri() : '',
            'timeout' => property_exists($this, 'timeout') ? $this->timeout : 5.0,
        ];

        return $options;
    }

    /**
     * Return http client.
     *
     * @param array $options
     *
     * @return \GuzzleHttp\Client
     *
     */
    protected function getHttpClient(array $options = [])
    {
        return new Client($options);
    }

    /**
     * Convert response contents to json.
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     *
     * @return array
     */
    protected function unwrapResponse(ResponseInterface $response)
    {
        $contentType = $response->getHeaderLine('Content-Type');
        $contents = $response->getBody()->getContents();

        if (false !== stripos($contentType, 'json') || stripos($contentType, 'javascript')) {
            return json_decode($contents, true);
        } elseif (false !== stripos($contentType, 'xml')) {
            return json_decode(json_encode(simplexml_load_string($contents)), true);
        }

        return $contents;
    }

    public function withRealException()
    {
        $this->withRealException = true;
        return $this;
    }

    public function getApiException(ClientException $exception): \Exception
    {
        $statusCode = $exception->getCode();
        $body = json_decode($exception->getResponse()->getBody()->getContents(), true);
        switch ($statusCode) {
            case 422:
                return ValidationException::withMessages($body['errors']);
                break;
            default:
                return new HttpException($statusCode, $body['message']);
                break;
        }
    }
}