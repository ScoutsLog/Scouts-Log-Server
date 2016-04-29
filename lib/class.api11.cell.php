<?php

class api_cell extends API11RequestBase {
	private $cell;
	private $status;
	private $issue;
	private $user;

	private $perform;
	
	
	public function AllowDefault() {
		return true;
	}
	
	public function Allowed($verb) {
		switch ($verb) {
			case 'GET':
				return array('list', 'status', 'summary', 'tasks', 'user', 'active', 'issue');
				
				break;
		}
		
		return array();
	}
	
	public function Run() {
		if ($this->isValidRequest() === true) {
			// Get URI parameters
			$params = $this->request->ActionParameters();
			$tp = count($params);

			// Get cell
			$this->cell = intval(preg_replace('/[^0-9]+/', '', $params[0]), 10);

			// Process additional parameters
			$this->perform = 'summary';

			if ($tp > 0) {
				for ($p=0; $p < $tp; $p++) {
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
						case 'user':
							$this->user = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '', $params[$p + 1]));
							$p++;

							break;
						case 'list':
						case 'summary':
						case 'tasks':
							$this->perform = $param;

							break;
						case 'active':
							if ($this->perform == 'list') {
								$this->perform = 'list-active';	
							}
													
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

//echo $this->perform;

			// Perform request
			switch ($this->perform) {
				case 'list':
					$this->cell_list(false);
					
					break;
				case 'list-active':
					$this->cell_list(true);
					
					break;
				case 'summary':
					$this->cell_summary();

					break;
				case 'tasks':
					$this->cell_tasks();

					break;
			}
		} else {
			// Bad request
			
			$this->request->SetError(401);
		}
	}
	
	
	private function cell_list($active_only) {
		$result = EyeWire_Cell::CellList($active_only);

		// Save result to request output
		$this->request->OutputType( 'text/json' );
		$this->request->OutputReplace( json_encode($result) );
	}

	private function cell_summary() {
		// Get cell object and tasks
		$cell = new eyewire_cell( $this->cell );
		$cell->get_tasks( $this->status, $this->user );

		// Prepare result object
		$result = new stdClass();
		$result->cell = $this->cell;
		$result->cellName = $cell->Name();
		$result->cellNameKO = $cell->Name('ko');
		$result->status = $this->status;
		$result->user = $this->user;
		$result->tasks = $cell->TaskCount();
		
		// Save result to request output
		$this->request->OutputType( 'text/json' );
		$this->request->OutputReplace( json_encode($result) );
	}

	private function cell_tasks() {
		// Get cell object and tasks
		$cell = new eyewire_cell( $this->cell );
		$cell->get_tasks( $this->status, $this->user, $this->issue );

		// Prepare result object
		$result = new stdClass();
		$result->cell = $this->cell;
		$result->cellName = $cell->Name();
		$result->cellNameKO = $cell->Name('ko');
		$result->status = $this->status;
		$result->issue = $this->issue;
		$result->user = $this->user;
		$result->tasks = $cell->Tasks();
		
		// Save result to request output
		$this->request->OutputType( 'text/json' );
		$this->request->OutputReplace( json_encode($result) );
	}

}

