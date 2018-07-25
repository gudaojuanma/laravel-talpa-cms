<?php

namespace Gudaojuanma\TalpaCMS\Controllers;

use Auth;
use TalpaCMS;
use ReflectionClass;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class LoginController extends Controller
{

    public function login(Request $request)
    {
        $code = $request->input('code');
        if (empty($code)) {
            return response('code 不可以为空');
        }

        if (($data = TalpaCMS::authorize($code))) {
            $openid = $data['openid'];
            $modelClass = config('talpa-cms.model', \App\User::class);
            $user = call_user_func([$modelClass, 'where'], 'talpa_cms_openid', $openid)->first();
            if (!$user) {
                $modelReflectionClass = new ReflectionClass($modelClass);
                $user = $modelReflectionClass->newInstance([
                    'name' => $data['name'] ?? '',
                    'email' => $data['email'] ?? '',
                    'password' => ''
                ]);
                $user->talpa_cms_openid = $openid;
                $user->save();
            }

            Auth::login($user, true);
            return redirect($request->input('state', '/'));
        }

        return response('认证失败！该情况及其罕见，请联系开发人员', 403);
    }
}
