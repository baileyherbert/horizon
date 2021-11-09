<?php

return [
	'timezone' => env('app_timezone', 'UTC'),
	'redirect_to_directories' => true,
	'view_cache' => is_mode('production'),
	'paths' => []
];
