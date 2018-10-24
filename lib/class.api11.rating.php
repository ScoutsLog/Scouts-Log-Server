<?php

class api_rating extends API11RequestBase {

	private $cell;
	private $perform;
	private $data;


	public function AllowDefault() {
		return true;
	}
	
	public function Allowed($verb) {
		switch ($verb) {
			case 'GET':
				return array('active', 'submit');
				
				break;
		}
		
		return array();
	}
	
	public function Run() {
		if ($this->isValidRequest() === true) {
			// Get URI request parameters
			$params = $this->request->ActionParameters();
			$tp = count($params);

			// Process parameters
			switch ($tp) {
				case 1:
					if ($params[0] == 'active') {
						$this->perform = 'active';
					} elseif (is_numeric($params[0])) {
						$this->cell = intval($params[0], 10);
						$this->perform = 'cell';
					} else {
						// ERROR: invalid parameter

						$this->request->setError(401);
						return;
					}

					break;
				case 2:
					// Get cell parameter
					if (is_numeric($params[0])) {
						$this->cell = intval($params[0], 10);
					} else {
						// ERROR: invalid parameter

						$this->request->setError(401);
						return;
					}

					// Get action
					$this->perform = preg_replace("/[^a-zA-Z0-9\-]+/", '', $params[1]);

					break;
				default:
					// ERROR: invalid parameters

					$this->request->setError(401);

					return;
			}

			// Get POST data
			$js_data = $this->request->FormData()->get_value('data', '', true, 'POST');
			$this->data = json_decode($js_data);

			// Process request
			switch ($this->perform) {
				case 'active':
					$this->do_active();

					break;
				case 'cell':
					$this->do_cell();

					break;
				case 'submit':
					$this->do_submit();

					break;
				default:
					// ERROR: invalid action

					$this->request->setError(401);

					return;
			}
		} else {
			// Bad request

			$this->request->SetError(401);
		}
	}


	private function do_active() {
		// Get active rating list
		$result = eyewire_rating::GetActive();

		// Save result to request output
		$this->request->OutputType('text/json');
		$this->request->OutputReplace(json_encode($result));
	}

	private function do_cell() {
		// Get rating object for cell
		$rating = new eyewire_rating($this->cell);

		// Prepare result object
		$result = new stdClass();
		$result->cell = $this->cell;
		$result->task = $rating->Task();
		$result->instructions = $rating->Instructions();
		$result->active = ($rating->Active() === true) ? 1 : 0;
		$result->results = $rating->Results();
		$result->ratings = $rating->Ratings();
		$result->has_rated = $rating->HasRated();

		// Save result to request output
		$this->request->OutputType('text/json');
		$this->request->OutputReplace(json_encode($result));
	}

	private function do_submit() {
		// Get rating object for cell
		$rating = new eyewire_rating($this->cell);

		// Save rating entry
		$res = $rating->SaveRating($this->data);
		
		// Prepare result object
		$result = new stdClass();
		$result->cell = $this->cell;
		$result->result = $res;

		// Save result to request output
		$this->request->OutputType('text/json');
		$this->request->OutputReplace(json_encode($result));
	}

}

