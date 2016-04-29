<?php

class api_status extends API11RequestBase {
	private $status;
	private $issue;

	private $user;
	
	private $header;
	
	
	public function AllowDefault() {
		return true;
	}
	
	public function Allowed($verb) {
		switch ($verb) {
			case 'GET':
				return array('header', 'user', 'issue');
				
				break;
		}
		
		return array();
	}
	
	public function Run() {
		if ($this->isValidRequest() === true) {
			// Get task from URI parameters
			$params = $this->request->ActionParameters();
			$tp = count($params);

			// Get status
			$this->status = strtolower(preg_replace('/[^a-zA-Z0-9\-]+/', '', $params[0]));

			// Process additional parameters
			if ($tp > 1) {
				for ($p=1; $p < $tp; $p++) {
					$param = strtolower(preg_replace('/[^a-zA-Z0-9\-\_]+/', '', $params[$p]));

					switch ($param) {
						case 'issue':
							$this->issue = strtolower(preg_replace('/[^a-zA-Z0-9\-]+/', '', $params[$p + 1]));
							$p++;

							break;
						case 'user':
							$this->user = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '', $params[$p + 1]));
							$p++;

							break;
						case 'header':
							$this->header = true;
							
							break;
					}
				}
			}
			
			// Check status parameter
			if (!empty($this->status)) {
				if (Utils::isValidStatus($this->status) !== true and $this->status != 'open') {
					// Invalid status specified
					
					$this->request->SetError(401);
					
					return;
				}
			}

			// Perform query
			$this->query_status();			
		} else {
			// Bad request
			
			$this->request->SetError(401);
		}
	}
	
		
	private function query_status() {
		// Get status object and tasks
		if ($this->header === true) {
			$status = new eyewire_status($this->status . '-header', $this->issue);
		} else {
			$status = new eyewire_status($this->status, $this->issue);
		}
		
		$status->get_tasks($this->user);

		// Prepare result object
		$result = new stdClass();
		$result->status = $this->status;
		$result->statusText = $status->Text();
		$result->header = $this->header;
		$result->issue = $this->issue;
		$result->user = $this->user;
		$result->tasks = $status->Tasks();
		$result->count = $status->Total();
		
		// Save result to request output
		$this->request->OutputType('text/json');
		$this->request->OutputReplace(json_encode($result));
		
	}
	
}

