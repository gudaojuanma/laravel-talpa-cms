<?php

Route::get('talpa/cms/authenticate', [
    'as' => 'talpa.cms.authenticate',
    'uses' => '\Gudaojuanma\TalpaCMS\Controllers\LoginController@login'
])->middleware(['web']);