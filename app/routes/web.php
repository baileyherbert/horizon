<?php

Route::middleware('Horizon\Http\Middleware\VerifyCsrfToken');

Route::any('/', 'App\Welcome', 'index.php');

