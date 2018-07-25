<?php

namespace Gudaojuanma\TalpaCMS\Commands;

use Illuminate\Console\Command;
use Gudaojuanma\TalpaCMS\Handler;

class ClearRefreshToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'talpa:cms:clear:refresh-token';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear refresh token';

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
        Handler::clearRefreshToken();
    }
}
