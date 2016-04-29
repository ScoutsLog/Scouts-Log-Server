<?php

class php_request11 {
	private $action;
	private $parameters;
	private $verb;
	
	private $form_data;
	
	private $output;
	private $output_type;
	
	private $has_error;
	private $status_code;

	public $skip_log;
	
	private $db;
	
	
	public function __construct() {
		// Save objects
		$this->form_data = Globals::getInstance('form');
		$this->db = Globals::getInstance('database');
		
		// Extract main request from URI
		$request = strtolower($this->form_data->get_value('request', '/[^a-zA-Z0-9\.\-\/]+/', true));
		
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
		$this->skip_log = false;
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
		// Process request
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

		// Save log entry
		if ($this->skip_log !== true) {
			$_action = $this->verb . ' ' . $this->action;

			if (!empty($this->parameters)) {
				$_action .= '/' . implode('/', $this->parameters);
			}

			$_result = ($this->has_error === true) ? 'error' : 'success';

			ScoutsLog_Logger::LogEntry($_action, $_result);
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

interface IAPI11Request {
	
	public function AllowDefault();
	
	public function Allowed($verb);
	
	public function Run();
	
	public function SetRequest(php_request11 $request);
	
}

abstract class API11RequestBase implements IAPI11Request {

	protected $request;


	public function SetRequest(php_request11 $request) {
		$this->request = $request;
	}

	protected function isValidRequest() {
		// Test: make sure we have a request object
		$test1 = ( isset($this->request ) and !empty( $this->request ));
		
		// Get current verb
		$verb = $this->request->Verb();
		
		// Get list of allowed actions for this verb
		$allowed = $this->Allowed($verb);

		// Test: check request actions for this verb
		$test2 = false;
		
		$params = $this->request->ActionParameters();
		$pcount = 0;

		if (count($params) > 0) {
			foreach ($params as $param) {
				// Skip numeric parameters
				$_param = preg_replace("/[^0-9]+/", '', $param);
				
				if (is_numeric($_param)) {
					continue;
				}

				// Increment "real" parameter counter
				$pcount++;
				
				// Make sure param is allowed for this verb
				if (in_array($param, $allowed) === true) {
					$test2 = true;
					
					break;
				}
			}
		}


		// No parameters or one parameter counts as default view
		// See if default view is allowed.  Actual request controller
		// will validate parameters when run.
		if ($test2 === false and $pcount <= 1) {
			$test2 = $this->AllowDefault();
		}
		
		// Return result
		return ($test1 and $test2);
	}

}



require_once 'class.api11.cell.php';
require_once 'class.api11.history.php';
require_once 'class.api11.internal.php';
require_once 'class.api11.stats.php';
require_once 'class.api11.status.php';
require_once 'class.api11.task.php';
require_once 'class.api11.user.php';
