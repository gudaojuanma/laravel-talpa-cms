<?php

namespace Gudaojuanma\TalpaCMS;

use Log;
use Cache;
use App\User;
use Carbon\Carbon;

class Handler
{
    const ACCESS_TOKEN_CACHE_KEY = 'talpa_cms_access_token';

    const REFRESH_TOKEN_CACHE_KEY = 'talpa_cms_refresh_token';

    const USER_CACHE_TAG = 'talpa_cms_user';

    const MENUS_CACHE_TAG = 'talpa_cms_menus';

    const PERMISSIONS_CACHE_TAG = 'talpa_cms_permissions';

    private $key;

    private $secret;

    private $host;

    public function __construct($key, $secret, $host)
    {
        $this->key = $key;
        $this->secret = $secret;
        $this->host = $host;
    }

    public static function clearUserCache()
    {
        return Cache::tags(self::USER_CACHE_TAG)->flush();
    }

    public static function clearMenusCache()
    {
        return Cache::tags(self::MENUS_CACHE_TAG)->flush();
    }

    public static function clearPermissionsCache()
    {
        return Cache::tags(self::PERMISSIONS_CACHE_TAG)->flush();
    }

    public static function clearAccessToken()
    {
        return Cache::forget(self::ACCESS_TOKEN_CACHE_KEY);
    }

    public static function clearRefreshToken()
    {
        return Cache::forget(self::REFRESH_TOKEN_CACHE_KEY);
    }

    /**
     * 获取认证码
     * @param string $state
     * @return string
     */
    public function getCodeUrl($state = '')
    {
        $query = http_build_query([
            'key' => $this->key,
            'state' => $state
        ]);

        return sprintf('%s/oauth2/code?%s', $this->host, $query);
    }

    /**
     * 登录认证
     * @param string $code
     * @return mixed
     */
    public function authorize($code)
    {
        if (empty($code)) {
            return false;
        }

        $url = sprintf('%s/oauth2/token', $this->host);
        $params = [
            'code' => $code,
            'secret' => $this->secret
        ];
        if (!($data = $this->query($url, $params))) {
            return false;
        }

        $required = ['openid', 'accessToken', 'accessTokenExpiresAt', 'refreshToken', 'refreshTokenExpiresAt'];
        list($success, $field) = $this->validateRequired($data, $required);
        if (!$success) {
            Log::error(sprintf('data[%s] is required', $field));
            return false;
        }

        // 每次新获取了token后都重新缓存
        $this->cacheTokens($data);

        return $data;
    }

    /**
     * 获取用户信息（用户名称）
     * @param string $openid 
     * @return mixed array|bool|Response
     */
    public function getUser($openid)
    {
        return $this->fetchPersonal(self::USER_CACHE_TAG, $openid);
    }

    /**
     * 获取用户菜单（包含二级菜单）
     * @param string $openid 
     * @return mixed array|bool|Response
     */
    public function getMenus($openid)
    {
        return $this->fetchPersonal(self::MENUS_CACHE_TAG, $openid);
    }

    /**
     * 获取用户权限列表
     * @param string $openid 
     * @return mixed array|bool|Response
     */
    public function getPermissions($openid)
    {
        return $this->fetchPersonal(self::PERMISSIONS_CACHE_TAG, $openid);
    }

    protected function fetchPersonal($tag, $openid)
    {
        $routes = [
            self::USER_CACHE_TAG => 'user',
            self::MENUS_CACHE_TAG => 'menu',
            self::PERMISSIONS_CACHE_TAG => 'permission'
        ];

        if (!isset($routes[$tag])) {
            return false;
        }

        if (!($data = Cache::tags($tag)->get($openid))) {
            if (false === ($accessToken = $this->getAccessToken())) {
                return false;
            }
            $params = [
                'openid' => $openid, 
                'access_token' => $accessToken
            ];
            $url = sprintf('%s/oauth2/%s', $this->host, $routes[$tag]);
            $data = $this->query($url, $params) ?: [];
            Cache::tags($tag)->put($openid, $data, config('talpa-cms.ttl'));
        }

        return $data;
    }

    protected function getAccessToken()
    {
        if (!($accessToken = Cache::get(self::ACCESS_TOKEN_CACHE_KEY))) {
            $accessToken =  $this->refreshToken();
        }

        return $accessToken;
    }

    protected function refreshToken()
    {
        if(($refreshToken = Cache::get(self::REFRESH_TOKEN_CACHE_KEY))) {
            $url = sprintf('%s/oauth2/refresh', $this->host);
            if (!($data = $this->post($url, ['refresh_token' => $refreshToken]))) {
                return false;
            }

            $required = ['accessToken', 'accessTokenExpiresAt', 'refreshToken', 'refreshTokenExpiresAt'];
            list($success, $field) = $this->validateRequired($data, $required);
            if (!$success) {
                Log::error(sprintf('data[%s] is required', $field));
                return false;
            }

            // 每次新获取了token后都重新缓存
            $this->cacheTokens($data);

            return $data['accessToken'];
        }

        return false;
    }

    protected function cacheTokens($data)
    {
        $accessToken = $data['accessToken'];
        $accessTokenExpiresAt = Carbon::createFromTimestamp($data['accessTokenExpiresAt'] - 300); //提前五分钟过期，预留缓冲时间
        $refreshToken = $data['refreshToken'];
        $refreshTokenExpiresAt = Carbon::createFromTimestamp($data['refreshTokenExpiresAt'] - 3600); //提前一小时过期，预留缓冲时间
        Cache::put(self::ACCESS_TOKEN_CACHE_KEY, $accessToken, $accessTokenExpiresAt);
        Cache::put(self::REFRESH_TOKEN_CACHE_KEY, $refreshToken, $refreshTokenExpiresAt);
    }

    protected function post($url, $data)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $content = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if ($errno > 0) {
            Log::error($error);
            return false;
        }
        return $this->parseResponse($content);
    }

    protected function query($url, $params = [])
    {
        $query = http_build_query($params);
        $joiner = strpos($url, '?') === false ? '?' : '&';
        $ch = curl_init($url . $joiner . $query);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $content = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        Log::debug($content);
        curl_close($ch);
        if ($errno > 0) {
            Log::error($error);
            return false;
        }
        return $this->parseResponse($content);
    }

    protected function parseResponse($content)
    {
        if (!($result = json_decode($content, true))) {
            Log::error($content);
            return false;
        }

        list($success, $field) = $this->validateRequired($result, ['error', 'message']);
        if (!$success) {
            Log::error(sprintf('%s is required', $field));
            return false;
        }

        if ($result['error'] > 0) {
            Log::error($result['message']);
            return false;
        }

        if (!isset($result['data'])) {
            Log::error('data is required');
            return false;
        }

        if (!is_array($result['data'])) {
            Log::error('data must be an array');
            return false;
        }

        return $result['data'];
    }

    protected function validateRequired($data, $fields)
    {
        foreach ($fields as $field) {
            if (!isset($data[$field])) {
                return [false, $field];
            }
        }
        return [true, null];
    }
}
