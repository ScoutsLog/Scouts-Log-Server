<?php

class api_task extends API11RequestBase {
	private $task;

	private $id;
	private $perform;
	private $data;
	
	
	public function AllowDefault() {
		return true;
	}
	
	public function Allowed($verb) {
		switch ($verb) {
			case 'GET':
				return array('actions', 'inspect', 'mismatch', 'ping', 'summary');
				
				break;
			case 'POST':
				return array('action', 'mismatch', 'submit');
				
				break;
		}
		
		return array();
	}
	
	public function Run() {
		if ($this->isValidRequest() === true) {
			// Get task from URI parameters
			$params = $this->request->ActionParameters();
	
			$this->id = intval(preg_replace('/[^0-9]+/', '', $params[0]), 10);
			$this->perform = strtolower(preg_replace('/[^a-zA-Z0-9\-\_]+/', '', $params[1]));
			$this->perform = (empty($this->perform)) ? 'summary' : $this->perform;
	
			if (!empty($params[2])) {
				$this->perform .= '-' . strtolower(preg_replace('/[^a-zA-Z0-9\-\_]+/', '', $params[2]));
			}

			if (!empty($params[3])) {
				$this->perform .= '-' . strtolower(preg_replace('/[^a-zA-Z0-9\-\_]+/', '', $params[3]));
			}
	
				
			// Get POST data
			$js_data = $this->request->FormData()->get_value('data', '', true, 'POST');
			$this->data = json_decode($js_data);

			// Get task object
			$this->task = new eyewire_task($this->id);

			
			// Perform request
			switch ($this->perform) {
				case 'actions':
					$this->task_actions();
					$res = true;

					break;
				case 'action-create':
					$res = $this->task->AddAction($this->data);

					// Prepare result object
					$result = new stdClass();
					$result->cell = $this->task->Cell();
					$result->task = $this->task->Id();
					$result->result = $res;

					// Save result to request output
					$this->request->OutputType('text/json');
					$this->request->OutputReplace(json_encode($result));
					
					break;
				case 'action-create-upload':
					// Get FILE data
					$img = $this->request->FormData()->get_value('image', '', true, 'FILE');

					$res = $this->task->AddAction2($this->data, $img);

					// Prepare result object
					$result = new stdClass();
					$result->cell = $this->task->Cell();
					$result->task = $this->task->Id();
					$result->result = $res;

					// Save result to request output
					$this->request->OutputType('text/json');
					$this->request->OutputReplace(json_encode($result));

					break;
				case 'action-update':
					$res = $this->task->UpdateAction($this->data);
					
					// Prepare result object
					$result = new stdClass();
					$result->cell = $this->task->Cell();
					$result->task = $this->task->Id();
					$result->result = $res;

					// Save result to request output
					$this->request->OutputType('text/json');
					$this->request->OutputReplace(json_encode($result));

					break;
				case 'summary':
					$this->task_summary();
					$res = true;

					break;
				case 'mismatch':
					$this->task_mismatch();
					$res = true;

					break;
				case 'mismatch-save':
					$res = $this->task_mismatch_save();

					// Prepare result object
					$result = new stdClass();
					$result->result = $res;

					// Save result to request output
					$this->request->OutputType('text/json');
					$this->request->OutputReplace(json_encode($result));

					break;
				case 'submit':
					$res = $this->task->AddSubmission($this->data);

					// Prepare result object
					$result = new stdClass();
					$result->cell = $this->task->Cell();
					$result->task = $this->task->Id();
					$result->result = $res;

					// Save result to request output
					$this->request->OutputType('text/json');
					$this->request->OutputReplace(json_encode($result));

					break;
			}

			// Check result
			if ($res === false) {
				// Error
				
				$this->request->SetError(500);
			}
		} else {
			// Bad request

			$this->request->SetError(401);
		}
	}
	

	private function task_actions() {
		// Prepare result object
		$result = new stdClass();
		$result->cell = $this->task->Cell();
		$result->cellName = $this->task->CellName();
		$result->cellNameKO = $this->task->CellNameKO();
		$result->task = $this->task->Id();
		$result->status = $this->task->Status();
		$result->statusText = $this->task->StatusText();
		$result->issue = $this->task->Issue();
		$result->lastUser = $this->task->LastUser();
		$result->lastUpdated = $this->task->LastUpdated();
		$result->mismatched = ($this->task->IsMismatched() ? 1 : 0);

		$result->actions = $this->task->Actions();
		
		// Save result to request output
		$this->request->OutputType('text/json');
		$this->request->OutputReplace(json_encode($result));
	}

	private function task_inspect() {
		// Prepare result object
		$result = new stdClass();
		$result->task = $this->task->Id();
		$result->users = $this->task->InspectUsers();

		// Save result to request output
		$this->request->OutputType('text/json');
		$this->request->OutputReplace(json_encode($result));
	}

	private function task_mismatch() {
		// Prepare result object
		$result = new stdClass();
		$result->task = $this->task->Id();

		// Get full actions for task
		$result->entries = eyewire_task::GetFullActions($this->task->Id());

		// Get cell list for entries
		$_id = 1;
		$_cells = array();

		foreach ($result->entries as $_idx => $_act) {
			$result->entries[$_idx]['new_id'] = $_id;
			$_cells[] = $_act['cell'];

			$_id++;
		}
			
		// Save result to request output
		$this->request->OutputType('text/json');
		$this->request->OutputReplace(json_encode($result));

	}

	private function task_mismatch_save() {
		$db = Globals::getInstance('database');

		$statements = array();

		foreach ($this->data->entries as $_entry) {
			$sql = 'UPDATE actions SET ';
			$sql .= 'cell = "' . $db->real_escape_string($this->data->new_cell) . '", ';
			$sql .= 'id = "' . $db->real_escape_string($_entry->new_id) . '" ';
			$sql .= 'WHERE task = "' . $db->real_escape_string($this->id) . '" ';
			$sql .= 'AND cell = "' . $db->real_escape_string($_entry->cell) . '" ';
			$sql .= 'AND id = "' . $db->real_escape_string($_entry->id) . '"';

			$statements[] = $sql;
		}

		$statements = array_reverse($statements);

		$db->begin_transaction();

		foreach ($statements as $_sql) {
			$db->query($_sql);
		}

		$db->end_transaction();

		// Refresh task object
		$this->task = new eyewire_task($this->id);


		// Add note documenting change
		$note_data = new stdClass();
		$note_data->cell = $this->data->new_cell;
		$note_data->task = $this->id;
		$note_data->status = 'note';
		$note_data->issue = '';
		$note_data->reaped = 0;
		$note_data->notes = 'Fixed cell mismatch';
		$note_data->image = '';

		$this->task->AddAction($note_data);

		return true;
	}

	private function task_summary() {
		// Prepare result object
		$result = new stdClass();
		$result->cell = $this->task->Cell();
		$result->cellName = $this->task->CellName();
		$result->cellNameKO = $this->task->CellNameKO();
		$result->task = $this->task->Id();
		$result->status = $this->task->Status();
		$result->statusText = $this->task->StatusText();
		$result->issue = $this->task->Issue();
		$result->lastUser = $this->task->LastUser();
		$result->lastUpdated = $this->task->LastUpdated();
		$result->mismatched = ($this->task->IsMismatched() ? 1 : 0);
		
		// Save result to request output
		$this->request->OutputType('text/json');
		$this->request->OutputReplace(json_encode($result));
	}

}

