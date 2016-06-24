<?php

// Begin output buffering
// ------------------------------------
	ob_start();
	
	
// Load required files
// ------------------------------------
	require_once 'lib/class.globals.php';
	require_once 'lib/class.database.php';
	require_once 'lib/class.session.php';
	require_once 'lib/class.form.php';
	require_once 'lib/class.eyewire.php';
	require_once 'lib/class.sorter.php';
	require_once 'lib/class.utils.php';
	require_once 'lib/class.api.php';

	require_once 'config.php';
	
	
// Create global objects
// ------------------------------------	
	Globals::setInstance( 'config', $config );

	Globals::setInstance( 'session', new php_session($config['session_cookie'], $config['session_cookie_domain'], $config['session_data']) );

	Globals::setInstance( 'form', new php_form() );
	
	Globals::setInstance(
		'database', 
		new php_database(
			$config['database_host'], 
			$config['database_username'],
			$config['database_password'],
			$config['database_instance']
		)
	);
	
	Globals::setInstance('api', true);
	
	
// Connect to database
// ------------------------------------
	Globals::getInstance('database')->connect();


// Start session
// ------------------------------------
	Globals::getInstance('session')->start();
	Globals::getInstance('session')->set_key('source', 'api');


// Language preferences
// ------------------------------------
	$_lang1 = Globals::getInstance('form')->get_value('lang', 'ident');
	$_lang2 = Globals::getInstance('session')->get_key('lang');
	$_lang = $config['language_default'];

	if (!empty($_lang1)) {
		$_lang = $_lang1;
	} else {
		if (!empty($_lang2)) {
			$_lang = $_lang2;
		}
	}

	Globals::getInstance('session')->set_key('lang', $_lang);
	Globals::setInstance('language', $_lang);


// Cross Origin Headers
// ------------------------------------
	header('Access-Control-Allow-Origin: http://eyewire.org');
	header('Access-Control-Allow-Headers: origin, x-requested-with, content-type');
	header('Access-Control-Allow-Methods: POST, GET');
	header('Access-Control-Allow-Credentials: true');


// Check if user has been authenticated
// ------------------------------------
	$test_auth = Globals::getInstance('session')->get_key('auth_token');

	if (empty($test_auth)) {
		// User is not authenticated

		Header('Content-Type: text/json');

		echo '{"error": "invalid authentication-token"}';
		
		Globals::getInstance('database')->disconnect();
		Globals::getInstance('session')->stop();

		exit();
	}


// Build request object
// ------------------------------------
	$request = new php_request();
	
	if ($request->isValid() !== true) {
		header('HTTP/1.1 401 Bad Request');
		
		Globals::getInstance('database')->disconnect();
		Globals::getInstance('session')->stop();

		exit();
	}
	
	
// Perform API Request
// ------------------------------------
	$request->Run();

	
// Check request result
// ------------------------------------
	if ($request->HasError() !== true) {
		// Set output mime type
		header('Content-Type: ' . $request->OutputMimeType());
		
		// Set output data
		echo $request->Output();
	} else {
		// Error encountered

		switch ($request->StatusCode()) {
			case 401:
				header('HTTP/1.1 401 Bad Request');
				
				break;
			case 403:
				header('HTTP/1.1 403 Forbidden');
				
				break;
			default:
				header('HTTP/1.1 500 Internal Server Error');
				
				break;
		}
	}


// Disconnect from database
// ------------------------------------
	Globals::getInstance('database')->disconnect();


// End session instance
// ------------------------------------
	Globals::getInstance('session')->stop();


// End output buffering and send buffer
// ------------------------------------
	ob_end_flush();

