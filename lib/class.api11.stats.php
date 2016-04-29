<?php

class api_stats extends API11RequestBase {

	public function AllowDefault() {
		return true;
	}
	
	public function Allowed($verb) {
		return array();
	}
	
	public function Run() {
		if ($this->isValidRequest() === true) {
			// Prevent logging
			$this->request->skip_log = true;

			// Get action parameters
			$params = $this->request->ActionParameters();

			// Prepare output
			$this->request->OutputType('text/json');
			
			// Get stats object
			$stats = new eyewire_stats();
			$stats->get_overall();			

			// Sort stats data
			$data = array_values($stats->data->cell_summary);

			$sorter = new php_sorter($data, array(array('difficulty', 'ASC', 'NUMERIC'), array('cellName', 'ASC', 'ALPHANUMERIC')));
			$stats->data->cell_summary = $sorter->sort();

			// Send response
			$this->request->OutputReplace(json_encode($stats->data));
		} else {
			// Bad request

			$this->request->SetError(401);
		}
	}

}

