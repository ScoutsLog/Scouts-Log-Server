<?php

class api_actions extends APIRequestBase {
	private $cell;
	private $task;
	private $status;
	private $user;
	
	
	public function AllowedVerbs() {
		return array('GET');
	}
	
	public function Run() {
		if ($this->isValidRequest() === true) {
			// Get request query parameters
			$this->cell = intval($this->request->FormData()->get_value('cell', 'numeric'), 10);
			$this->task = intval($this->request->FormData()->get_value('task', 'numeric'), 10);
			$this->status = strtolower($this->request->FormData()->get_value('status', '/[^a-zA-Z\-]+/', true));
			$this->user = strtolower($this->request->FormData()->get_value('user', 'alphanumeric'));
			
			// Check status parameter
			if (!empty($this->status)) {
				if (Utils::isValidStatus($this->status) !== true) {
					// Invalid status specified
					
					$this->request->SetError(401);
					
					return;
				}
			}
			
			// Determine query type
			$type_a = (!empty($this->task));
			$type_b = (!empty($this->cell));
			$type_c = (empty($this->cell) && !empty($this->status));
			$type_d = (empty($this->cell) && !empty($this->user));
			
			
			// Perform query
			if ($type_a) {
				$this->query_task();
			} else if ($type_b) {
				$this->query_cell();
			} else if ($type_c) {
				$this->query_status();
			} else if ($type_d) {
				$this->query_user();
			}
			
		} else {
			// Bad request
			
			$this->request->SetError(401);
		}
	}
	
	
	private function query_cell() {
		$cell = new eyewire_cell( $this->cell );
		$cell->get_tasks( $this->status, $this->user );
		
		// Save result to request output
		$this->request->OutputType( 'text/json' );
		$this->request->OutputReplace( json_encode($cell->Tasks()) );
	}
	
	private function query_task() {
		$task = new eyewire_task($this->task);
		
		// Prepare result object
		$result = new stdClass();
		$result->cell = $task->Cell();
		$result->cellName = $task->CellName();
		$result->task = $task->Id();
		$result->status = $task->Status();
		$result->weight = $task->Weight();
		$result->votes = $task->Votes();
		$result->votesMax = $task->VotesMax();
		$result->statusText = $task->StatusText();
		$result->lastUser = $task->LastUser();
		$result->lastUpdated = $task->LastUpdated();
		$result->actions = $task->Actions();
		
		// Save result to request output
		$this->request->OutputType('text/json');
		$this->request->OutputReplace(json_encode($result));
	}
	
	private function query_status() {
		$status = new eyewire_status($this->status);
		$status->get_tasks($this->user);
		
		// Save result to request output
		$this->request->OutputType('text/json');
		$this->request->OutputReplace(json_encode($status->Tasks()));
		
	}
	
	private function query_user() {
		$user = new eyewire_user($this->user);
		$user->get_tasks($this->status);
		
		// Save result to request output
		$this->request->OutputType('text/json');
		$this->request->OutputReplace(json_encode($user->Tasks()));
	}
	
}
