<?php

class api_internal extends APIRequestBase {

	public function AllowedVerbs() {
		return array('GET');
	}

	public function Run() {
		if ($this->isValidRequest() === true) {
			// Get parameters
			$params = $this->request->ActionParameters();

			// Determine request action
			$handled = false;

			switch ($params[0]) {
				case 'status':
					$handled = true;
					
					$this->do_status();

					break;
				case 'update-cell-list':
					$handled = true;

					$this->do_cell_list();
			}

			// Make sure request was handled
			if ($handled === false) {
				$this->request->SetError(401);
			}
		} else {
			// Bad request

			$this->request->SetError(401);
		}
	}

	
	private function do_status() {
		// Get config array
		$cfg = Globals::getInstance('config');

		// Create result object
		$obj = new stdClass();
		$obj->status = 'ok';
		$obj->roles = Globals::getInstance('session')->get_key('auth_roles');
		$obj->version = $cfg['chrome_extension_version'];
		
		// Save result to request output
		$this->request->OutputType('text/json');
		$this->request->OutputReplace( json_encode($obj) );
	}

	private function do_cell_list() {
		// Create result object
		$obj = new stdClass();
		$obj->success = false;

		// Check user roles
		$roles = Globals::getInstance('session')->get_key('auth_roles') or array();
		$test = array('scythe', 'admin');
		$result = array_intersect($test, $roles);
						
		if (!empty($result)) {
			// Update cell list

			// Get cells from EyeWire API
			$cells = EyeWire::ActiveCells(true);

			// Update database
			foreach ($cells as $c) {
				Globals::getInstance('database')->query('INSERT INTO cells (id, name, difficulty, active) VALUES("' . $c['cell'] . '", "' . $c['name'] . '", "' . $c['difficulty'] . '", "' . $c['active'] .'") ON DUPLICATE KEY UPDATE name = VALUES(name), difficulty = VALUES(difficulty), active = VALUES(active)');
			}

			$obj->success= true;
		} else {
			// Error - user is not authorized

			$obj->error = 'user not authorized';
		}

		// Save result to request output
		$this->request->OutputType('text/json');
		$this->request->OutputReplace( json_encode($obj) );
	}
	
}

