<?php

class api_stats extends APIRequestBase {

	public function AllowedVerbs() {
		return array('GET');
	}
	
	public function Run() {
		if ($this->isValidRequest() === true) {
			// Get action parameters
			$params = $this->request->ActionParameters();

			// Prepare output
			$this->request->OutputType('text/json');
			
			// Get stats object
			$stats = new eyewire_stats();
			$stats->get_overall();

			// Check for additional stats
			if ($params[0] == 'header') {
				$stats->get_attention_summary();
			}

			// Set response
			$this->request->OutputReplace(json_encode($stats->data));
		} else {
			// Bad request

			$this->request->SetError(401);
		}
	}

}

