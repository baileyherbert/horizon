<?php

/**
 * Configuration: Providers
 * Level: Developer
 *
 * This file allows you to add or modify the framework's service providers. These providers are middleware used for the
 * sole purpose of retrieving and/or loading relevant data and files.
 */

return array(

    'views' => array(
        'Horizon\Provider\Services\ViewProvider'
    ),

    'translations' => array(
        'Horizon\Provider\Services\TranslationProvider'
    ),

    'routes' => array(
        'Horizon\Provider\Services\RoutingProvider'
    ),

    'updates' => array(
        'Horizon\Provider\Services\UpdateProvider'
    ),

    'extensions' => array(
        'Horizon\Provider\Services\ExtensionProvider'
    )

);
