<?php

class php_request {
	private $action;
	private $parameters;
	private $verb;
	
	private $form_data;
	
	private $output;
	private $output_type;
	
	private $has_error;
	private $status_code;
	
	private $db;
	
	
	public function __construct() {
		// Save objects
		$this->form_data = Globals::getInstance('form');
		$this->db = Globals::getInstance('database');
		
		// Extract main request from URI
		$request = strtolower($this->form_data->get_value('request', '/[^a-zA-Z0-9\-\/]+/', true));
		
		// Determine main action and any parameters
		if (strpos($request, '/') !== false) {
			// Request contains parameters
			
			$_parts = explode('/', $request);
			
			$this->action = array_shift($_parts);
			$this->parameters = $_parts;
			
		} else {
			// No parameters specified
			
			$this->action = $request;
			$this->parameters = array();
		}
		
		// Determine action verb (GET, POST, etc)
		$this->verb = $_SERVER['REQUEST_METHOD'];
		
		// Set default values
		$this->output = '';
		$this->output_type = 'text/html';
		$this->has_error = false;
		$this->statusCode = 200;
	}
	
	
	/*
	 * General Public Methods
	 */
	
	public function isValid() {
		// Test: make sure a main action is specified
		$test1 = !empty($this->action);
		
		// Test: make sure the api class for the main action is available
		$test2 = class_exists('api_' . $this->action);
		
		// Return result
		return ($test1 && $test2);
	}
	
	public function Run() {
		if ($this->isValid() === true) {
			// Get class instance for main action
			$className = 'api_' . $this->action;
			$act = new $className();
			
			$act->SetRequest($this);
			
			// Process request
			$act->Run();
			
		} else {
			// Invalid request
			
			$this->SetError(401);
		}
	}
	
	public function SetError($code) {
		$this->has_error = true;
		$this->status_code = $code;
	}
	
	
	/*
	 * Output Methods
	 */
	
	public function OutputAppend($data) {
		$this->output += $data;
	}
	
	public function OutputClear() {
		$this->output = '';
	}
	
	public function OutputError($has_error) {
		$this->has_error = ($has_error === true) ? true : false;
	}
	
	public function OutputReplace($data) {
		$this->output = $data;
	}
	
	public function OutputStatusCode($code) {
		$this->status_code = $code;
	}
	
	public function OutputType($mimeType) {
		$this->output_type = $mimeType;
	}
	
	
	
	/*
	 * Properties
	 */
	
	public function Action() {
		return $this->action;
	}
	
	public function ActionParameters() {
		return $this->parameters;
	}
	
	public function Database() {
		return $this->db;
	}
	
	public function FormData() {
		return $this->form_data;
	}
	
	public function HasError() {
		return $this->has_error;
	}
	
	public function Output() {
		return $this->output;
	}
	
	public function OutputMimeType() {
		return $this->output_type;
	}
	
	public function StatusCode() {
		return $this->status_code;
	}
	
	public function Verb() {
		return $this->verb;
	}

}

interface IAPIRequest {
	
	public function AllowedVerbs();
	
	public function Run();
	
	public function SetRequest(php_request $request);
	
}

abstract class APIRequestBase implements IAPIRequest {

	protected $request;


	public function SetRequest(php_request $request) {
		$this->request = $request;
	}

	protected function isValidRequest() {
		// Test: make sure we have a request object
		$test1 = (isset( $this->request ) and !empty( $this->request ));
		
		// Test: check request verb
		$allowed = $this->AllowedVerbs();
		$verb = $this->request->Verb();
		
		$test2 = (empty( $allowed ) or in_array( $verb, $allowed ));
		
		// Return result
		return ($test1 and $test2);
	}

}



require_once 'class.api.actions.php';
require_once 'class.api.internal.php';
require_once 'class.api.stats.php';
require_once 'class.api.task.php';
