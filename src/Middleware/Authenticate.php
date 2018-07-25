<?php

namespace Gudaojuanma\TalpaCMS\Middleware;

use Auth;
use Route;
use Closure;
use TalpaCMS;
use Illuminate\Http\Request;

class Authenticate
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::guest()) {
            return TalpaCMS::getCode($request->url());
        }

        $user = $request->user();
        $routeName = Route::currentRouteName();
        $permissions = TalpaCMS::getPermissions($user->talpa_cms_openid);
        if (false === $permissions) {
            return TalpaCMS::getCode($request->url());
        }

        if($permissions && in_array($routeName, $permissions)) {
            return $next($request);
        }

        return response('您没有权限执行此操作', 403);
    }
}