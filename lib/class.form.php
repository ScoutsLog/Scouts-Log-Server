<?php

class php_form {
	private $get;
	private $post;
	private $file;

	/**
	 * Class Constructor
	 *
	 * @return php_form
	 * @access public
	 * @since 1.0
	 */
	public function __construct() {
		// Copy FORM data into class
		$this->get = $_GET;
		$this->post = $_POST;
		$this->file = $_FILES;

		// Destroy super globals
		unset($_GET);
		unset($_POST);
		unset($_FILE);
		unset($_REQUEST);
	}

	/**
	 * Clear ALL Form Values
	 *
	 * @return boolean
	 * @access public
	 * @since 1.1
	 */
	public function clear_values() {
		$this->get = array();
		$this->post = array();
		$this->file = array();

		return true;
	}

	/**
	 * Get Form Fields
	 *
	 * Returns a unique list of FORM fields
	 *
	 * @return array
	 * @access public
	 * @since 1.0
	 */
	public function get_fields($_type='') {
		$fields = array();

		$_type = strtoupper($_type);

		if ($_type == '') {
			// Retrieve all field types
				
			if (is_array($this->get) === true) {
				$fields = array_merge($fields, array_keys($this->get));
			}

			if (is_array($this->post) === true) {
				$fields = array_merge($fields, array_keys($this->post));
			}

			if (is_array($this->file) === true) {
				$fields = array_merge($fields, array_keys($this->file));
			}
		} else {
			// Retrieve specific field type
				
			switch ($_type) {
				case 'POST':
					if (is_array($this->post) === true) {
						$fields = array_merge($fields, array_keys($this->post));
					}
						
					break;
				case 'GET':
					if (is_array($this->get) === true) {
						$fields = array_merge($fields, array_keys($this->get));
					}
						
					break;
				case 'FILE':
					if (is_array($this->file) === true) {
						$fields = array_merge($fields, array_keys($this->file));
					}
						
					break;
			}
		}

		$fields = array_unique($fields);

		return $fields;
	}

	/**
	 * Retrieve Form Value
	 *
	 * Retrieves a CLEANED form value using a specified format.
	 *
	 * All form values are cleaned for PHP/HTML tags.
	 *
	 * Predefined data formats:
	 * - alpha        (/[^a-zA-Z]+/)
	 * - alphanumeric (/[^a-zA-Z0-9]+/)
	 * - email        (/[^a-zA-Z0-9\_\@\-\.]+/)
	 * - integer      (/[^0-9\-]+/)
	 * - float        (/[^0-9\-\.]+/)
	 *
	 * @param string $name Name of form value
	 * @param string $format Type of data format
	 * @param boolean $is_regex Set to TRUE if $format is a REGEXP expression
	 * @return mixed Form value
	 * @access public
	 * @since 1.0
	 */
	public function get_value($name, $format, $is_regex=false, $type='') {
		$type = strtoupper($type);
		
		if ($type == '') {
			if (isset($this->file[$name])) {
				// FILE value

				$value = $this->file[$name];
				$type = 'FILE';
			} elseif (isset($this->post[$name])) {
				// POST value

				$value = $this->post[$name];
				$type = 'POST';
			} elseif (isset($this->get[$name])) {
				// GET value

				$value = $this->get[$name];
				$type = 'GET';
			} else {
				// Field not set

				return '';
			}
		} else {
			switch ($type) {
				case 'POST':
					if (isset($this->post[$name])) {
						$value = $this->post[$name];
					} else {
						// Field not set

						return '';
					}
						
					break;
				case 'GET':
					if (isset($this->get[$name])) {
						$value = $this->get[$name];
					} else {
						// Field not set

						return '';
					}
						
					break;
				case 'FILE':
					if (isset($this->file[$name])) {
						$value = $this->file[$name];
					} else {
						// Field not set

						return '';
					}
						
					break;
				default:
					// Invalid type
						
					return '';
						
					break;
			}
		}

		if ($type != 'FILE') {
			if ($is_regex !== true) {
				// Select pre-defined format
					
				switch ($format) {
					case 'alpha':
						$_regexp = '/[^a-zA-Z]+/';
							
						break;
					case 'numeric':
						$_regexp = '/[^0-9]+/';
							
						break;
					case 'alphanumeric':
						$_regexp = '/[^a-zA-Z0-9]+/';
							
						break;
					case 'ident':
						$_regexp = '/[^a-zA-Z0-9\-\_]+/';
							
						break;
					case 'text':
						$_regexp = '/[^\x20-\x7E\r\n]+/';
							
						break;
					case 'integer':
						$_regexp = '/[^0-9\-]+/';
							
						break;
					case 'float':
						$_regexp = '/[^0-9\-\.]+/';
							
						break;
					case 'date':
						$_regexp = '/[^0-9\.\-\/]+/';
							
						break;
					case 'email':
						$_regexp = '/[^a-zA-Z0-9\-\+\.\@\_]+/';
							
						break;
				}
			} else {
				// Use custom format
					
				$_regexp = $format;
			}
	
			// Clean string
			if ($_regexp != '') {
				$value = preg_replace($_regexp, '', $value);
			}
		}

		// Return string
		return $value;
	}

	/**
	 * Set Form Value
	 *
	 * @param string $name Name of the form value
	 * @param string $value Actual data for the form value
	 * @param string $type Type of form value (GET or POST)
	 * @return boolean Result of operation
	 * @access public
	 * @since 1.1
	 */
	public function set_value($name, $value, $type='') {
		// Auto detect
		if ($type == '') {
			if (isset($this->post[$name])) {
				// POST value

				$type = 'POST';
			} elseif (isset($this->get[$name])) {
				// GET value

				$type = 'GET';
			} else {
				// Error
				return false;
			}
		}

		// Set value
		switch ($type) {
			case 'GET':
				$this->get[$name] = $value;

				break;
			case 'POST':
				$this->post[$name] = $value;

				break;
		}

		return true;
	}
}
