<?php

// Begin output buffering
// ------------------------------------
	ob_start();
	
	
// Load required files
// ------------------------------------
	require_once 'lib/class.globals.php';
	require_once 'lib/class.session.php';
	require_once 'lib/class.form.php';
	require_once 'lib/class.eyewire.php';
	require_once 'lib/class.sorter.php';
	require_once 'lib/class.utils.php';
	require_once 'lib/class.language.php';
	require_once 'config.php';
	
	
// Create global objects
// ------------------------------------
	Globals::setInstance('config', $config);

	Globals::setInstance('session', new php_session($config['session_cookie'], $config['session_cookie_domain'], $config['session_data']));
	
	Globals::setInstance('form', new php_form());


// Start session
// ------------------------------------
	Globals::getInstance('session')->start();


// Language preferences
// ------------------------------------
	$_lang1 = Globals::getInstance('form')->get_value('lang', 'ident');
	$_lang2 = Globals::getInstance('session')->get_key('lang');
	$_lang = '';

	if (!empty($_lang1)) {
		$_lang = $_lang1;
	} else {
		if (!empty($_lang2)) {
			$_lang = $_lang2;
		}
	}

	if (!empty($_lang)) {
		Globals::setInstance('language', new php_language($_lang));
	} else {
		Globals::setInstance('language', new php_language());
	}

	$language = Globals::getInstance('language')->DisplayLanguage();

	Globals::getInstance('session')->set_key('lang', $language);
	Globals::setInstance('language', $language);


// Get form request
// ------------------------------------
	$request = strtolower(Globals::getInstance('form')->get_value('request', '/[^a-zA-Z0-9\-\_\/]+/', true));


// Process request
// ------------------------------------
	$response = array();

	switch ($request) {
		case 'auth':
			// Perform OAuth2 code exchange for access token
			
			
			// Get code response
			$auth_code = Globals::getInstance('form')->get_value('code', 'alphanumeric');
			
			// Check code
			if (!empty($auth_code)) {
				if ($auth_code != 'access_denied') {
					// Exchange code for access token
					$auth = EyeWire::AuthenticateExchange($auth_code);

					// Check auth response
					if ($auth->status == 'HTTP/1.1 200 OK') {
						// Extract token
						$o_token = json_decode($auth->body);

						// Save auth token to session
						Globals::getInstance('session')->set_key('auth_token', $o_token->access_token);
						
						// Get user info
						$user = EyeWire::UserDetails();
						
						Globals::getInstance('session')->set_key('auth_userid', $user->id);
						Globals::getInstance('session')->set_key('auth_user', $user->username);
						
						// Get user details
						$user_bio = EyeWire::UserBio($user->username);
						
						Globals::getInstance('session')->set_key('auth_roles', $user_bio->roles);
						
						// Check user for an appropriate role
						$test = array('scout', 'scythe', 'admin');
						$res = array_intersect($test, $user_bio->roles);
						
						if (empty($res)) {
							// User is not authorized
							
							include('content/header.login.php');
							include('content/content.auth-error.php');
							include('content/footer.login.php');

							// Clean and destroy session
							Globals::getInstance('session')->delete_key('auth_user');
							Globals::getInstance('session')->delete_key('auth_token');
							Globals::getInstance('session')->delete_key('auth_roles');
							Globals::getInstance('session')->destroy_cookie($config['session_cookie']);
							Globals::getInstance('session')->destroy_cookie($config['session_cookie'] . '_sec');
							
							break;
						}
						
						
						
						// Check for extension auth
						$do = Globals::getInstance('session')->get_key('do');
						
						if ($do == 'extension-auth') {
							// Display message for extension user

							include('content/header.login.php');
							include('content/content.auth-success.php');
							include('content/footer.login.php');
						} else {
							// Redirect user to home page
							header('Location: http://scoutslog.org/home');
						}

						// Clear extension auth flag
						Globals::getInstance('session')->delete_key('do');
						Globals::getInstance('session')->delete_key('from');
					} else {
						// Error during exchange process
	
						echo 'Error during exchange process.';

ob_start();
print_r( $auth );
error_log( 'OAuth Status Result: ' . ob_get_contents() );
ob_end_clean();

					}
				} else {
					// Error during authentication process
	
					echo 'Authentication error.';
				}
			} else {
				// Error - no code
				
				echo 'No authentication code.';
			}
			
			break;
		case 'account/refresh':
			// Get auth token
			$token = Globals::getInstance('session')->get_key('auth_token');

			header('Content-Type: text/json');
			
			if (!empty($token)) {
				// Get user info
				$user = EyeWire::UserDetails();

				Globals::getInstance('session')->set_key('auth_userid', $user->id);
				Globals::getInstance('session')->set_key('auth_user', $user->username);
						
				// Get user details
				$user_bio = EyeWire::UserBio($user->username);
						
				Globals::getInstance('session')->set_key('auth_roles', $user_bio->roles);

				// Check user for an appropriate role
				$test = array('scout', 'scythe', 'admin');
				$res = array_intersect($test, $user_bio->roles);
						
				if (!empty($res)) {
					// Success

					$obj = new stdClass();
					$obj->success = true;
					$obj->roles = $user_bio->roles;

					echo json_encode($obj);
				} else {
					// Error - user is not authorized

					$obj = new stdClass();
					$obj->success = false;
					$obj->error = "user not authorized";

					echo json_encode($obj);

					// Clean and destroy session
					Globals::getInstance('session')->delete_key('auth_user');
					Globals::getInstance('session')->delete_key('auth_token');
					Globals::getInstance('session')->delete_key('auth_roles');
					Globals::getInstance('session')->destroy_cookie($config['session_cookie']);
					Globals::getInstance('session')->destroy_cookie($config['session_cookie'] . '_sec');
				}
			} else {
				// Error - no auth token

				$obj = new stdClass();
				$obj->success = false;
				$obj->error = "invalid authentication-token";

				echo json_encode($obj);
			}

			break;
		case 'account/logout':
			// Clean and destroy session
			Globals::getInstance('session')->delete_key('auth_user');
			Globals::getInstance('session')->delete_key('auth_token');
			Globals::getInstance('session')->delete_key('auth_roles');
			Globals::getInstance('session')->destroy_cookie($config['session_cookie']);
			Globals::getInstance('session')->destroy_cookie($config['session_cookie'] . '_sec');
			
			// Send user to home screen
			header('Location: http://scoutslog.org/home');

			break;
	}


// End session instance
// ------------------------------------
	Globals::getInstance('session')->stop();


// End output buffering
// ------------------------------------
	ob_end_flush();
	
