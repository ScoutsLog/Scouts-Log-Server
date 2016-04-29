<?php

class api_history extends API11RequestBase {
	private $start;
	private $limit;

	private $cell;
	private $type;
	private $accuracy_low;
	private $accuracy_high;


	public function AllowDefault() {
		return true;
	}
	
	public function Allowed($verb) {
		switch ($verb) {
			case 'GET':
				return array('type', 'cell');
				
				break;
		}
		
		return array();
	}
	
	public function Run() {
		if ($this->isValidRequest()) {
			// Get action parameters
			$params = $this->request->ActionParameters();
			$tp = count($params);		
			
			// Process parameters
			$this->cell = 0;
			$this->type = '';
			$this->accuracy_low = 0;
			$this->accuracy_high = 1;
			$this->start = intval(preg_replace('/[^0-9]+/', '', $params[0]), 10);
			$this->limit = intval(preg_replace('/[^0-9]+/', '', $params[1]), 10);
	
			// Check limit parameter
			if ($this->limit == 0) {
				$this->limit = 10;
			}

			// Check for additional parameters
			if ($tp > 2) {
				for ($p=2; $p < $tp; $p++) {
					$param = strtolower(preg_replace('/[^a-zA-Z]+/', '', $params[$p]));

					switch ($param) {
						case 'type':
							$this->type = strtolower(preg_replace('/[^a-zA-Z]+/', '', $params[$p + 1]));
							$p++;

							break;
						case 'cell':
							$this->cell = intval(preg_replace('/[^0-9]+/', '', $params[$p + 1]), 10);
							$p++;

							break;
						case 'accuracy':
							$a = preg_replace('/[^0-9\.\-]+/', '', $params[$p + 1]);
							
							if (strpos($a, '-') !== false) {
								list($_low, $_high) = explode('-', $a);
								
								$this->accuracy_low = floatval($_low);
								$this->accuracy_high = floatval($_high);
							} else {
								$this->accuracy_high = floatval($a);
							}
							
							
							$p++;

							break;
					}
				}
			}
			
			// Check for accuracy filter
			if ($this->accuracy_high < 1) {
				$this->type = 'normal';
			}

			// Perform action
			$this->query_history();
		} else {
			// Bad Request
			
			$this->request->SetError(401);
		}
	}
	

	public function query_history() {
		// Get database object
		$db = Globals::getInstance('database');
			
		// Get current user
		$user = Globals::getInstance('session')->get_key('auth_user');
			
		// Build SQL query
		$sql_select = 'SELECT DISTINCT s.cell, c.name AS cellName, c.name_ko AS cellNameKO, s.task, s.score, s.accuracy, s.type, s.trailblazer, s.timestamp, (CASE WHEN u.task IS NOT NULL THEN 1 ELSE 0 END) AS has_entries, (CASE WHEN w.task IS NOT NULL THEN 1 ELSE 0 END) AS has_watch ';
		$sql_from = 'FROM submissions s LEFT JOIN cells c ON (s.cell = c.id) ';
		$sql_from .= 'LEFT JOIN actions u ON (u.task = s.task AND u.user = "' . $db->real_escape_string($user) . '") ';
		$sql_from .= 'LEFT JOIN actions w ON (w.task = s.task AND w.user = "' . $db->real_escape_string($user) . '" AND w.status = "watch") ';

		$sql_where = 'WHERE s.user = "' . $db->real_escape_string($user) . '" ';

		if (!empty($this->cell)) {
			$sql_where .= 'AND s.cell = "' . $db->real_escape_string($this->cell) . '" ';
		}
		
		if (!empty($this->type)) {
			$sql_where .= 'AND s.type = "' . $db->real_escape_string($this->type) . '" ';
		}
		
		if ($this->type == 'normal' and $this->accuracy_high < 1) {
			if ($this->accuracy_low > 0) {
				$sql_where .= 'AND s.accuracy >= ' . $db->real_escape_string($this->accuracy_low) . ' AND s.accuracy <= ' . $db->real_escape_string($this->accuracy_high) . ' ';
			} else {
				$sql_where .= 'AND s.accuracy <= ' . $db->real_escape_string($this->accuracy_high) . ' ';
			}
		}

		$sql_order .= 'ORDER BY s.timestamp DESC ';
		$sql_order .= 'LIMIT ' . $this->start . ', ' . $this->limit;

		// Prepare result object
		$result = new stdClass();
		$result->user = $user;
		$result->type = $this->type;
		$result->cell = $this->cell;
		
		if ($this->accuracy_low > 0) {
			$result->accuracy = $this->accuracy_low . '-' . $this->accuracy_high;
		} else {
			$result->accuracy = $this->accuracy_high;
		}
		
		$result->start = $this->start;
		$result->limit = $this->limit;
		$result->tasks = array();

		// Execute SQL query
		$res = $db->query($sql_select . $sql_from . $sql_where . $sql_order);
		
		if ($res !== false) {
			if ($res->num_rows > 0) {
				while ($row = $res->fetch_assoc()) {
					$result->tasks[] = $row;
				}
			}
		}

		// Save result to request output
		$this->request->OutputType('text/json');
		$this->request->OutputReplace(json_encode($result));
	}

}

