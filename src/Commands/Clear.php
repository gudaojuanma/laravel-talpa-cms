<?php

namespace Gudaojuanma\TalpaCMS\Commands;

use Illuminate\Console\Command;
use Gudaojuanma\TalpaCMS\Handler;

class Clear extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'talpa:cms:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear talpa cms all caches';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        Handler::clearUserCache();
        Handler::clearMenusCache();
        Handler::clearPermissionsCache();
        
        Handler::clearAccessToken();
        Handler::clearRefreshToken();
    }
}
