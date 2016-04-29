<?php

class api_internal extends API11RequestBase {

	public function AllowDefault() {
		return false;
	}
	
	public function Allowed($verb) {
		switch ($verb) {
			case 'GET':
				return array('status', 'update-cell-list');
				
				break;
		}
		
		return array();
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
		$obj->plugin = $cfg['chrome_extension_url'];
		
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

			// Mark all cells inactive
			Globals::getInstance('database')->query('UPDATE cells SET active = 0');
			
			// Get cells from EyeWire API (English)
			$cells = EyeWire::CellList(true);

			// Update database
			foreach ($cells as $c) {
				Globals::getInstance('database')->query('INSERT INTO cells (id, name, difficulty, active) VALUES("' . $c['cell'] . '", "' . $c['name'] . '", "' . $c['difficulty'] . '", "' . $c['active'] .'") ON DUPLICATE KEY UPDATE name = VALUES(name), difficulty = VALUES(difficulty), active = VALUES(active)');
			}
			
			// Get cells from EyeWire API (Korean)
			$cells = EyeWire::CellList(true, 'ko');
			
			// Update database
			foreach ($cells as $c) {
				Globals::getInstance('database')->query('UPDATE cells SET name_ko = "' . $c['name'] . '" WHERE id = "' . $c['cell'] . '"');
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

