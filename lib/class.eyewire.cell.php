<?php

class eyewire_cell {
	private $id;
	private $name;
	private $name_ko;
	private $difficulty;
	private $active;
	
	private $db;
	
	private $tasks;

	
	public function __construct($cell) {
		// Save cell ID
		$this->id = $cell;
		
		// Save database reference
		$this->db = Globals::getInstance('database');
		
		// Get basic cell info
		$res = $this->db->query('SELECT * FROM cells WHERE id = "' . $this->db->real_escape_string($this->id) . '"');
		$row = $res->fetch_assoc();
		
		$this->name = $row['name'];
		$this->name_ko = $row['name_ko'];
		$this->difficulty = $row['difficulty'];
		$this->active = ($row['active'] == 1) ? true : false;
	}
	
	public function get_tasks($status='', $user='', $issue='') {
                // Get current user
                $cur_user = Globals::getInstance('session')->get_key('auth_user');


		// Build SQL statement
		$sql_select = 'SELECT DISTINCT t.cell, c.name AS cellName, t.id AS task, t.status, s.text AS statusText, t.issue, t.user AS lastUser, t.timestamp AS lastUpdated, (CASE WHEN u.task IS NOT NULL THEN 1 ELSE 0 END) AS has_entries, (CASE WHEN w.task IS NOT NULL THEN 1 ELSE 0 END) AS has_watch ';
		$sql_from = 'FROM tasks t ';
		$sql_from .= 'LEFT JOIN cells c ON (t.cell = c.id) ';
		$sql_from .= 'LEFT JOIN status s ON (t.status = s.status) ';
		$sql_from .= 'LEFT JOIN actions u ON (u.task = t.id AND u.user = "' . $this->db->real_escape_string($cur_user) . '") ';
		$sql_from .= 'LEFT JOIN actions w ON (w.task = t.id AND w.user = "' . $this->db->real_escape_string($cur_user) . '" AND w.status = "watch") ';
		$sql_order = 'GROUP BY t.id ';
		$sql_order .= 'ORDER BY t.timestamp ASC';

		// Parameter: cell
		$sql_where = 'WHERE t.cell = "' . $this->db->real_escape_string($this->id) . '" ';

		// Parameter: status
		if (!empty($status)) {
			switch ($status) {
				case 'all':

					break;
				case 'open':
					$sql_where .= 'AND t.active = 1 ';

					break;
				default:
					$sql_where .= 'AND t.status = "' . $this->db->real_escape_string($status) . '" ';

					break;
			}
		} else {
			$sql_where .= 'AND t.active = 1 ';
		}
		
		// Parameter: issue
		if (!empty($issue)) {
			$sql_where .= 'AND t.issue = "' . $this->db->real_escape_string($issue) . '" ';
		}

		// Parameter: user
		if (!empty($user)) {
			$sql_from .= 'LEFT JOIN actions u2 ON (u2.task = t.id AND u2.user = "' . $this->db->real_escape_string($user) . '") ';
			$sql_where .= 'AND t.id = u2.task ';
		}



		
		// Execute query
		$res = $this->db->query($sql_select . $sql_from . $sql_where . $sql_order);
			
		// Prepare results
		$this->tasks = array();
		
		if ($res !== false) {
			while ($row = $res->fetch_assoc()) {
				$this->tasks[] = $row;
			}
		}
		
		// Return results
		return $this->tasks;
	}

	public static function CellList($active_only=true) {
		// Get database object
		$db = Globals::getInstance('database');

		// Build query
		//$sql = 'SELECT c.id AS cell, c.name AS cellName, c.name_ko AS cellNameKO, c.difficulty, c.active ';
		//$sql .= 'FROM cells c ';
		//$sql .= 'WHERE c.dataset = 1 ';

		$sql = 'SELECT c.id AS cell, c.name AS cellName, c.name_ko AS cellNameKO, c.difficulty, c.active, COUNT(t.id) AS tasks ';
		$sql .= 'FROM cells c ';
		$sql .= 'INNER JOIN ((SELECT c2.id FROM cells c2 WHERE c2.dataset = 1 AND c2.active = 1) UNION DISTINCT (SELECT t2.cell FROM tasks t2 WHERE t2.active = 1)) cl ON (cl.id = c.id) LEFT JOIN tasks t ON (c.id = t.cell AND t.active = 1) ';
		$sql .= 'WHERE c.dataset = 1 ';

		if ($active_only === true) {
			$sql .= 'AND c.active = 1 ';
		}

		$sql .= 'GROUP BY c.id ';
		$sql .= 'ORDER BY c.difficulty, c.name';


		// Execute query
		$res = $db->query($sql);

		// Prepare results
		$cell_list = array();

		if ($res !== false) {
			while ($row = $res->fetch_assoc()) {
				$cell_list[] = $row;
			}
		}

		// Return results
		return $cell_list;
	}
	
	
	
	public function Id() {
		return $this->id;
	}
	
	public function Name($locale='en') {
		switch ($locale) {
			case 'ko':
				return $this->name_ko;

				break;
			case 'en':
			default:
				return $this->name;

				break;
		}
	}
	
	public function Difficulty() {
		return $this->difficulty;
	}
	
	public function Active() {
		return $this->active;
	}

	public function Tasks() {
		return $this->tasks;
	}

	public function TaskCount() {
		return count($this->tasks);
	}
	
}

