<?php

Route::get('/', function () {
    return view('welcome');
});

Route::post('/', 'Controller@greping');
