<?php

namespace Gudaojuanma\TalpaCMS;

use Gudaojuanma\TalpaCMS\Commands\Clear;
use Gudaojuanma\TalpaCMS\Commands\ClearUserCache;
use Gudaojuanma\TalpaCMS\Commands\ClearMenusCache;
use Gudaojuanma\TalpaCMS\Commands\ClearPermissionsCache;
use Gudaojuanma\TalpaCMS\Commands\ClearAccessToken;
use Gudaojuanma\TalpaCMS\Commands\ClearRefreshToken;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    protected $defer = false;

    public function register()
    {
        $this->app->bind(Handler::class, function() {
            return new Handler(config('talpa-cms.key'), config('talpa-cms.secret'), config('talpa-cms.host'));
        });
    }

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/talpa-cms.php' => config_path('talpa-cms.php')
        ]);

        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadMigrationsFrom(__DIR__ . '/../migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                Clear::class,
                ClearUserCache::class,
                ClearMenusCache::class,
                ClearPermissionsCache::class,

                ClearAccessToken::class,
                ClearRefreshToken::class,
            ]);
        }
    }
}
