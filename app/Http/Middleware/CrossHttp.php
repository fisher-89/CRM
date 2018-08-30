<?php

namespace App\Http\Middleware;

use Closure;

class CrossHttp
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response=$next($request);
        $response->header('Access-Control-Allow-Credentials',true);
        $response->header('Access-Control-Allow-Headers','Origin, Content-Type, Authorization, Cookie, Accept, multipart/form-data, application/json');
        $response->header('Access-Control-Allow-Methods','GET, POST, PATCH, PUT, OPTIONS');
        $response->header('Access-Control-Allow-Origin','*');
        return $response;
    }
}
