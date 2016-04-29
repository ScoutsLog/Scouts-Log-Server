<?php

class php_session {
	
	private $id;
	private $token;
	
	private $cookie_name;
	private $cookie_domain;
	
	public function __construct($cookie_name, $cookie_domain, $data_path) {
		$this->cookie_name = $cookie_name;
		$this->cookie_domain = $cookie_domain;
		
		ini_set('session.auto_start', 0);
		ini_set('session.save_path', $data_path);
		ini_set('session.name', $this->cookie_name);
		ini_set('session.use_only_cookies', true);
		ini_set('session.cookie_domain', $this->cookie_domain);
		ini_set('session.cookie_httponly', true);
		ini_set('session.cookie_lifetime', 2592000);		
		ini_set('session.gc_maxlifetime', 5184000);
	}
	
	
	/**
	 * Create PHP Session
	 *
	 * @access private
	 * @since 1.0
	 * @see start()
	 */
	private function create() {
		// Start Session
		session_start();
		session_regenerate_id();
	
		// Set random key to ensure session is started
		$this->set_key('random', hash('sha1', mt_rand()));
	
		// Store session ID
		$this->id = session_id();
	
		// Save a security token in the session data and
		// send the user a security key
		$source = 'qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM1234567890';
	
		$this->token = '';
		$key = '';
	
		for ($i=0; $i<128; $i++) {
			$a = $source{mt_rand(0, 61)};
	
			$this->token .= $a;
		}
	
		$key = hash('sha1', $this->token . $this->id);
		
		$this->set_key('token', $this->token);

		// Update user cookies
		$this->set_cookie($this->cookie_name, $this->id, time() + 2592000);
		$this->set_cookie($this->cookie_name . '_sec', $key, time() + 2592000);		
	}
	
	/**
	 * Delete PHP Session Key
	 *
	 * @param string $key Name of session key to delete
	 * @return void
	 * @access public
	 * @since 1.0
	 */
	public function delete_key($key) {
		unset($_SESSION[$key]);
	}
	
	/**
	 * Destroy PHP Session
	 *
	 * @return void
	 * @access private
	 * @since 1.0
	 */
	private function destroy() {
		$this->id = '';
	
		session_unset();
		session_destroy();
	}
	
	/**
	 * Get Current Session ID
	 *
	 * @return string Session ID
	 * @access public
	 * @since 1.0
	 */
	public function get_id() {
		return $this->id;
	}
	
	/**
	 * Retrieve PHP Session Key
	 *
	 * @param string $key Name of session key to retrieve
	 * @return string
	 * @access public
	 * @since 1.0
	 * @see set_key()
	 * @see get_array_keys()
	 */
	public function get_key($key) {
		return $_SESSION[$key];
	}
	
	/**
	 * Retrieve ALL PHP Session Keys
	 *
	 * @return array
	 * @access public
	 * @since 1.0
	 * @see get_key()
	 */
	public function get_array_keys() {
		return $_SESSION;
	}
	
	/**
	 * Stop PHP Session
	 *
	 * Ends the current session and stores any session data
	 *
	 * @return void
	 * @access public
	 * @since 1.0
	 * @see start()
	 */
	public function stop() {
		if ($this->id != '') {
			session_write_close();
		}
	}
	
	/**
	 * Set PHP Session Key
	 *
	 * @param string $key Name of session key to write
	 * @param string $value Value of session key to write
	 * @return void
	 * @access public
	 * @since 1.0
	 * @see get_key()
	 */
	public function set_key($key, $value) {
		$_SESSION[$key] = $value;
	}
	
	/**
	 * Start PHP Session
	 *
	 * @return void
	 * @access public
	 * @since 1.0
	 * @see stop()
	 */
	public function start() {
		// Start Session
		session_start();
	
		// Set random key to ensure session is started
		$this->set_key('random', hash('sha1', mt_rand()));
	
		// Store session ID
		$this->id = session_id();
	
		// Load token
		$this->token = $this->get_key('token');
	
		// Check session token against user cookie
		$user_key = $this->get_cookie($this->cookie_name . '_sec');
		$real_key = hash('sha1', $this->token . $this->id);
		
		if ($user_key !== $real_key) {
			// User cookie does not match session data
	
			$this->destroy();
			$this->create();
		} else {
			// Update session cookies

			$this->set_cookie($this->cookie_name, $this->id, time() + 2592000);
			$this->set_cookie($this->cookie_name . '_sec', $real_key, time() + 2592000);
		}
	}
	
	
	/**
	 * Remove Cookie on Client
	 *
	 * @param string $key Name of cookie
	 * @return boolean
	 * @access public
	 * @since 1.0
	 */
	public function destroy_cookie($key) {
		$time = time() - 259200;
		$path = '/';
		$host = $_SERVER['HTTP_HOST'];
		$ssl = false;

		if (isset($_SERVER['HTTPS'])) {
			$ssl = ($_SERVER['HTTPS'] == 1) ? true : false;
		}

		return setcookie($key, '', $time, $path, $host, $ssl, true);
	}
	
	/**
	 * Retrieve Cookie Contents
	 *
	 * @param string $key Name of cookie
	 * @return string
	 * @access public
	 * @since 1.0
	 */
	public function get_cookie($key) {
		return $_COOKIE[$key];
	}
	
	/**
	 * Create Cookie on Client
	 *
 	 * @param string $key Name of cookie
	 * @param string $value Value for cookie
	 * @return boolean
	 * @access public
	 * @since 1.0
	 */
	public function set_cookie($key, $value, $time=0) {
		$path = '/';
		$host = $_SERVER['HTTP_HOST'];
		$ssl = ($_SERVER['HTTPS'] == 1) ? true : false;

		return setcookie($key, $value, $time, $path, $host, $ssl, true);
	}
	


	public function Id() {
		return $this->id;
	}

}


