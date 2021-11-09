<?php

/**
 * This file configures the service providers to boot.
 *
 * Most core services will offer a provider class that loads the resources necessary to perform the service. For
 * example, the `ViewServiceProvider` helps the framework understand where your view templates can be found. If there
 * is something in this list that you're not using, remove it to reduce the app's boot time.
 */
return [

	'Horizon\Routing\RoutingServiceProvider',
	'Horizon\View\ViewServiceProvider',
	'Horizon\Extension\ExtensionServiceProvider',
	'Horizon\Translation\TranslationServiceProvider',
	'Horizon\Database\MigrationServiceProvider'

];
