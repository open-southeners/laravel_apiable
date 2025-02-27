<?php

namespace OpenSoutheners\LaravelApiable\Http\Middleware;

use Closure;

class SerializesResponses
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // TODO: Set Apiable responses

        return $next($request);
    }
}
