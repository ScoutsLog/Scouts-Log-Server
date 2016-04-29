<?php

class api_user extends API11RequestBase {
	private $user;
	private $status;
	private $issue;

	private $perform;
	
	
	public function AllowDefault() {
		return true;
	}
	
	public function Allowed($verb) {
		switch ($verb) {
			case 'GET':
				return array('status', 'summary', 'tasks');
				
				break;
		}
		
		return array();
	}
	
	public function Run() {
		if ($this->isValidRequest() === true) {
			// Get URI parameters
			$params = $this->request->ActionParameters();
			$tp = count($params);

			// Get user
			$this->user = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '', $params[0]));

			// Process additional parameters
			$this->perform = 'summary';

			if ($tp > 1) {
				for ($p=1; $p < $tp; $p++) {
					$param = strtolower(preg_replace('/[^a-zA-Z0-9\-\_]+/', '', $params[$p]));

					switch ($param) {
						case 'issue':
							$this->issue = strtolower(preg_replace('/[^a-zA-Z\-]+/', '', $params[$p + 1]));
							$p++;

							break;
						case 'status':
							$this->status = strtolower(preg_replace('/[^a-zA-Z\-]+/', '', $params[$p + 1]));
							$p++;

							break;
						case 'summary':
						case 'tasks':
							$this->perform = $param;

							break;
					}
				}
			}
			
			// Check status parameter
			if (!empty($this->status)) {
				if (Utils::isValidStatus($this->status) !== true) {
					// Invalid status specified
					
					$this->request->SetError(401);
					
					return;
				}
			}

			// Perform request
			switch ($this->perform) {
				case 'summary':
					$this->user_summary();

					break;
				case 'tasks':
					$this->user_tasks();

					break;
			}
		} else {
			// Bad request
			
			$this->request->SetError(401);
		}
	}

	private function user_summary() {
		// Get user object and tasks
		$user = new eyewire_user($this->user);
		$user->get_tasks_count($this->status, $this->issue);

		// Prepare result object
		$result = new stdClass();
		$result->user = $this->user;
		$result->status = $this->status;
		$result->issue = $this->issue;
		$result->count = $user->TaskCount();
		
		// Save result to request output
		$this->request->OutputType( 'text/json' );
		$this->request->OutputReplace( json_encode($result) );
	}

	private function user_tasks() {
		// Get user object and tasks
		$user = new eyewire_user($this->user);
		$user->get_tasks($this->status, $this->issue);

		// Prepare result object
		$result = new stdClass();
		$result->user = $this->user;
		$result->status = $this->status;
		$result->issue = $this->issue;
		$result->tasks = $user->Tasks();
		$result->count = $user->TaskCount();

		// Save result to request output
		$this->request->OutputType( 'text/json' );
		$this->request->OutputReplace( json_encode($result) );
	}

}


