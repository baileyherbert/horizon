<?php

Route::middleware('Horizon\Http\Middleware\TokenMiddleware');

Route::any('/', 'App\Welcome', 'index.php');
