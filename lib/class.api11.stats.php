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
			
			// Get stats object
			$stats = new eyewire_stats();
			$stats->get_overall();

			$data = $stats->data;
			unset($data->cell_summary);

			// Sort stats data
			//$sorter = new php_sorter($data->cell_summary, array(array('difficulty', 'ASC', 'NUMERIC'), array('cellName', 'ASC', 'ALPHANUMERIC')));
			//$data->cell_summary = $sorter->sort();

			// Get current view telemetry
			$js_view_data = $this->request->FormData()->get_value('data', '', true);
			$view_data = json_decode($js_view_data);

			// Check for mystic cell in current view
			$_roles = Globals::getInstance('session')->get_key('auth_roles');

			if (in_array('mystic', $_roles) === true or in_array('admin', $_roles) === true) {
				// Get general mystic summary
				$m_summary = eyewire_cell_mystic::get_summary('all');

				$data->mystic = new stdClass();
				//$data->mystic->header = $m_summary->summary['need-player-a'] + $m_summary->summary['need-player-b'] + $m_summary->summary['need-admin'];
				$data->mystic->header = $m_summary->summary['open-tasks'];
				$data->mystic->summary = $m_summary->summary;
				$data->mystic->count = $m_summary->count;

				// Get cell specific information
				if (!empty($view_data)) {
					if (isset($view_data->cell)) {
						if (eyewire_cell_mystic::is_mystic($view_data->cell) === true) {
							$m_cell = new eyewire_cell_mystic($view_data->cell);

							$data->mystic->cell = new stdClass();
							$data->mystic->cell->id = $m_cell->Id();
							$data->mystic->cell->cellName = $m_cell->Name();
							$data->mystic->cell->cellNameKO = $m_cell->Name('ko');
							$data->mystic->cell->active = $m_cell->Active();
							$data->mystic->cell->difficulty = $m_cell->Difficulty();
							$data->mystic->cell->lastUpdated = $m_cell->LastUpdated();
							$data->mystic->cell->lastUser = $m_cell->LastUser();
							$data->mystic->cell->mystic = $m_cell->Mystic();
							$data->mystic->cell->status = $m_cell->Status();
							$data->mystic->cell->user = $m_cell->User();
							$data->mystic->cell->actions = $m_cell->Actions();
						}
					}
				}
			}

			// Send response
			$this->request->OutputType('text/json');
			$this->request->OutputReplace(json_encode($data));
		} else {
			// Bad request

			$this->request->SetError(401);
		}
	}

}

