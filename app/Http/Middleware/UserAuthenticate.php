<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserAuthenticate
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     * @throws AuthenticationException
     */
    public function handle($request, Closure $next)
    {
        $user = auth('api')->user();

        if (empty($user)) {
            throw new AuthenticationException(trans('auth.errors.unauthenticated'));
        }

        Auth::setUser($user);
        return $next($request);
    }
}
