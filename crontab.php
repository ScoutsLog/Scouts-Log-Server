<?php

// Load required files
// ------------------------------------
	require_once 'lib/class.globals.php';
	require_once 'lib/class.database.php';
	require_once 'lib/class.eyewire.php';
	require_once 'lib/class.sorter.php';
	require_once 'lib/class.utils.php';
	require_once 'lib/class.imgur.php';
	require_once 'config.php';

	require_once 'vendor/autoload.php';


// Create global objects
// ------------------------------------
	Globals::setInstance(
		'database',
		new php_database(
			$config['database_host'],
			$config['database_username'],
			$config['database_password'],
			$config['database_instance']
		)
	);

	Globals::setInstance('config', $config);

	Globals::setInstance(
		'imgur',
		new Imgur_Uploader()
	);
	
	
// Connect to database
// ------------------------------------
	Globals::getInstance('database')->connect();
	
	
// Get CLI parameters
// ------------------------------------
	$ARGC = $_SERVER['argc'];
	$ARGV = $_SERVER['argv'];
	
	
// Perform action
// ------------------------------------
	$perform = preg_replace('/[^a-zA-Z0-9\-\_]+/', '', $ARGV[1]);
	
	switch($perform) {
		case 'update-cell-list':
			include 'content/cron.update-cell-list.php';
			
			break;
		case 'google-sync':
			include 'content/cron.google-sync.php';

			break;
		case 'imgur-uploader':
			include 'content/cron.imgur-uploader.php';

			break;
	}

	
// Disconnect from database
// ------------------------------------
	Globals::getInstance('database')->disconnect();
	
	