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
            return $this->authRedirect($request);
        }

        $user = $request->user();
        $routeName = Route::currentRouteName();
        $permissions = TalpaCMS::getPermissions($user->talpa_cms_openid);
        if (false === $permissions) {
            return $this->authRedirect($request);
        }

        if($permissions && in_array($routeName, $permissions)) {
            return $next($request);
        }

        return response('您没有权限执行此操作', 403);
    }

    private function authRedirect(Request $request)
    {
        $key = config('talpa-cms.url-header-key', 'TALPA-CMS-URL');

        if (($realUrl = $request->headers->get($key))) {
            $redirectUrl = TalpaCMS::getCodeUrl($realUrl);
            return response($redirectUrl, 401);
        }

        $redirectUrl = TalpaCMS::getCodeUrl($request->url());
        return redirect($redirectUrl);
    }
}