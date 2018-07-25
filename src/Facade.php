<?php

namespace Gudaojuanma\TalpaCMS;

use Illuminate\Support\Facades\Facade as BaseFacade;

class Facade extends BaseFacade
{
    protected static function getFacadeAccessor() { return Handler::class; }
}
