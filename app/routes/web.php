<?php

Route::get('/', 'App\\Http\\Controllers\\Welcome')->fallback('index.php');
