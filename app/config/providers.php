<?php

/**
 * Configuration: Providers
 * Level: Developer
 *
 * This file allows you to add or modify the framework's service providers. These providers are middleware used for the
 * sole purpose of retrieving and/or loading relevant data and files.
 */

return array(

	'Horizon\Routing\RoutingServiceProvider',
	'Horizon\View\ViewServiceProvider',
	'Horizon\Extension\ExtensionServiceProvider',
	'Horizon\Translation\TranslationServiceProvider',
	'Horizon\Database\MigrationServiceProvider'

);
