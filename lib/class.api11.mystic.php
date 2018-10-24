<?php

class api_mystic extends API11RequestBase {
	
	private $summary;
	private $tasks;
	private $cell;
	private $update;

	private $id;
	private $status;
	private $issue;
	private $user;

	
	public function AllowDefault() {
		return true;
	}
	
	public function Allowed($verb) {
		switch ($verb) {
			case 'GET':
				return array('cell', 'summary', 'tasks', 'issue', 'user');
				
				break;
			case 'POST':
				return array('cell', 'update');

				break;
		}
		
		return array();
	}
	
	public function Run() {
		if ($this->isValidRequest() === true) {
			// Get task from URI parameters
			$params = $this->request->ActionParameters();
			$tp = count($params);

			$this->cell = false;
			$this->update = false;
			$this->id = 0;
			$this->summary = true;
			$this->tasks = false;
			$this->status = 'all';
			$this->issue = '';
			$this->user = '';

			// Process additional parameters
			if ($tp > 0) {
				for ($p=0; $p < $tp; $p++) {
					$param = strtolower(preg_replace('/[^a-zA-Z0-9\-\_]+/', '', $params[$p]));

					switch ($param) {
						case 'cell':
							$this->cell = true;
							$this->summary = false;
							$this->id = intval(preg_replace('/[^0-9]+/', '', $params[$p + 1]), 10);
							$p++;

							break;
						case 'issue':
							$this->issue = strtolower(preg_replace('/[^a-zA-Z0-9\-]+/', '', $params[$p + 1]));
							$p++;

							break;
						case 'summary':
							$this->status = strtolower(preg_replace('/[^a-zA-Z0-9\-]+/', '', $params[$p + 1]));
							$p++;

							break;
						case 'tasks':
							$this->status = strtolower(preg_replace('/[^a-zA-Z0-9\-]+/', '', $params[$p + 1]));
							$p++;

							if (empty($this->status)) {
								$this->status = 'open';
							}

							$this->cell = false;
							$this->summary = false;
							$this->tasks = true;
						case 'update':
							if ($this->cell === true and !empty($this->id)) {
								$this->update = true;
							}

							break;
						case 'user':
							$this->user = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '', $params[$p + 1]));
							$p++;

							break;
					}
				}
			}

			// Check status parameter
			if (!empty($this->status) and $this->summary === true) {
				if (self::isValidMysticStatus($this->status) !== true and $this->status != 'all') {
					// Invalid status specified
					
					$this->request->SetError(401);
					
					return;
				}
			}

			if (!empty($this->status) and $this->tasks === true) {
				if (Utils::isValidStatus($this->status) !== true and $this->status != 'open') {
					// Invalid status specified
					
					$this->request->SetError(401);
					
					return;
				}
			}

			// Check cell parameters
			if ($this->cell === true and empty($this->id)) {
				// Invalid cell specified

				$this->request->SetError(401);

				return;
			}				

			// Perform query
			switch (true) {
				case ($this->cell):
					if ($this->update) {
						$this->update_cell();
					} else {
						$this->query_cell();
					}

					break;
				case ($this->summary):
					$this->query_summary();

					break;
				case ($this->tasks):
					$this->query_tasks();

					break;
			}
		} else {
			// Bad request
			
			$this->request->SetError(401);
		}
	}

	private function query_cell() {
		// Get cell data
		$cell = new eyewire_cell_mystic($this->id);

		// Prepare result object
		$result = new stdClass();
		$result->id = $cell->Id();
		$result->cellName = $cell->Name();
		$result->cellNameKO = $cell->Name('ko');
		$result->active = $cell->Active();
		$result->difficulty = $cell->Difficulty();
		$result->lastUpdated = $cell->LastUpdated();
		$result->lastUser = $cell->LastUser();
		$result->mystic = $cell->Mystic();
		$result->status = $cell->Status();
		$result->user = $cell->User();
		$result->userA = $cell->UserA();
		$result->userB = $cell->UserB();
		$result->actions = $cell->Actions();

		// Save result to request output
		$this->request->OutputType('text/json');
		$this->request->OutputReplace(json_encode($result));
	}

	private function query_summary() {
		// Get summary data
		$data = eyewire_cell_mystic::get_summary($this->status);

		if ($this->status === 'all') {
			// Prepare result object
			$result = new stdClass();
			$result->status = $this->status;
			$result->summary = $data->summary;
			$result->count = $data->count;
		} else {
			// Prepare result object
			$result = new stdClass();
			$result->status = $this->status;
			$result->cells = $data->cells;
			$result->count = $data->count;
		}
		
		// Save result to request output
		$this->request->OutputType('text/json');
		$this->request->OutputReplace(json_encode($result));
	}

	private function query_tasks() {
		// Get status object and tasks
		$status = new eyewire_status($this->status, $this->issue, 11);
		$status->get_tasks($this->user);

		// Prepare result object
		$result = new stdClass();
		$result->status = $this->status;
		$result->statusText = $status->Text();
		$result->issue = $this->issue;
		$result->user = $this->user;
		$result->tasks = $status->Tasks();
		$result->count = $status->Total();
		
		// Save result to request output
		$this->request->OutputType('text/json');
		$this->request->OutputReplace(json_encode($result));
	}

	private function update_cell() {
		// Get FORM parameters
		$js_data = $this->request->FormData()->get_value('data', '', true, 'POST');
		$data = json_decode($js_data);

		$status = preg_replace('/[^a-zA-Z0-9\_\-]+/', '', $data->status);
		$notes = $data->notes;

		// Get cell data
		$cell = new eyewire_cell_mystic($this->id);

		// Update cell status
		$res = $cell->update_status($status, $notes);

		// Prepare result object
		$result = new stdClass();
		$result->result = $res;
		$result->cell = $cell->Id();
		$result->status = $cell->Status();
		$result->user = $cell->User();

		// Save result to request output
		$this->request->OutputType('text/json');
		$this->request->OutputReplace(json_encode($result));
	}
	

	private static function isValidMysticStatus($status) {
		$valid = array(
			'need-player-a',
            		'player-a',
            		'need-player-b',
            		'player-b',
			'need-admin',
			'complete'
		);
		
		$status = strtolower($status);
		
		return in_array($status, $valid);
	}

}

