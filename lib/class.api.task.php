<?php

class api_task extends APIRequestBase {
	private $task;
	private $data;
	
	public function AllowedVerbs() {
		return array('POST');
	}
	
	public function Run() {
		if ($this->isValidRequest() === true) {
			// Get task from URI parameters
			$params = $this->request->ActionParameters();
			$this->task = intval(preg_replace('/[^0-9]+/', '', $params[0]));
			$perform = strtolower(preg_replace('/[^a-zA-Z0-9\-\_]+/', '', $params[1]) . '-' . preg_replace('/[^a-zA-Z0-9\-\_]+/', '', $params[2]));
			
			// Get request data
			$request = $this->request->FormData()->get_value('request', '', true, 'POST');
			$this->data = json_decode($request);

			// Get task object
			$task = new eyewire_task($this->task);
			
			// Perform request
			$res = false;

			switch ($perform) {
				case 'action-':
				case 'action-create':
					$res = $task->AddAction($this->data);
					
					break;
				case 'action-update':
					$res = $task->UpdateAction($this->data);
					
					break;
				case 'action-delete':
					$res = $task->DeleteAction($this->data);
					
					break;
				default:
					// Bad request
					$this->request->SetError(401);

					return;
			}

			// Check result
			if ($res === false) {
				$this->request->SetError(500);
			}
		} else {
			// Bad request
				
			$this->request->SetError(401);
		}
	}
	
}



