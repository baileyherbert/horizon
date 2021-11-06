<?php

/**
 * Configuration: Updates
 * Level: Expert
 *
 * This file provides configuration for update functionality. Because updates are very complex, it is not recommended
 * to modify these settings unless you know what you're doing or are following instructions from the developer.
 */

return array(

	'env' => array(
		'max_execution_time' => 120
	),

	'ssl' => array(
		'certificate_authority' => 'horizon/resources/ca-bundle.crt',
		'peer_validation' => true,
		'enforce_security_policy' => false
	),

	'timeout' => array(
		'default' => 3,
		'ns' => 6,
		'init' => 10,
		'repository' => null,
		'channel' => null,
		'version' => null,
		'payload' => 30,
		'script' => 6
	)

);
