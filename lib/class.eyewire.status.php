<?php

class eyewire_status {
	private $id;
	private $issue;

	private $text;
	private $sequence;
	
	private $header;

	private $db;
	
	private $tasks;
	
	
	public function __construct($status, $issue='') {
		// Save status
		$this->id = $status;
		$this->issue = $issue;
		
		// Save database reference
		$this->db = Globals::getInstance('database');
		
		// Get basic status info
		$this->header = false;

                $_parts = explode('-', $status);

                if ($_parts[count($_parts)-1] == 'header') {
			$this->header = true;

			array_pop($_parts);
		}

		$this->id = implode('-', $_parts);
		
		switch ($this->id) {
			case 'open':
				$this->text = 'Open Tasks';
				$this->sequence = 0;
			
				break;
			default:
				$res = $this->db->query('SELECT * FROM status WHERE status = "' . $this->db->real_escape_string($this->id) . '"');
				$row = $res->fetch_assoc();
		
				$this->text = $row['text'];
				$this->sequence = intval($row['sequence'], 10);
			
				break;
		}

		$this->tasks = array();
	}
	
	
	public function get_tasks($user='') {
                // Get current user
                $cur_user = Globals::getInstance('session')->get_key('auth_user');

		// Build SQL statement
		if ($this->id == 'need-scythe' and $this->header === true) {
			// Get tasks for need-scythe header
			$sql_select = 'SELECT t.cell, c.name AS cellName, c.name_ko AS cellNameKO, t.id AS task, t.status, s.text AS statusText, t.issue, t.user AS lastUser, t.timestamp AS lastUpdated, (CASE WHEN u.task IS NOT NULL THEN 1 ELSE 0 END) AS has_entries, (CASE WHEN w.task IS NOT NULL THEN 1 ELSE 0 END) AS has_watch ';
			$sql_from = 'FROM tasks t ';
			$sql_from .= 'LEFT JOIN cells c ON (t.cell = c.id) ';
			$sql_from .= 'INNER JOIN status s ON (t.status = s.status) ';
			$sql_from .= 'LEFT JOIN actions u ON (u.task = t.id AND u.user = "' . $this->db->real_escape_string($cur_user) . '") ';
			$sql_from .= 'LEFT JOIN actions w ON (w.task = t.id AND w.user = "' . $this->db->real_escape_string($cur_user) . '" AND w.status = "watch") ';
			$sql_where = 'WHERE s.include_active = 1 ';

			if (!empty($this->issue)) {
				$sql_where .= 'AND t.issue = "' . $this->db->real_escape_string($this->issue) . '" ';
			}

			$sql_order = 'GROUP BY t.id ';
			$sql_order .= 'ORDER BY t.timestamp ASC';
		} elseif ($this->id != 'open') {
			// Get tasks for specific status
			$sql_select = 'SELECT t.cell, c.name AS cellName, c.name_ko AS cellNameKO, t.id AS task, t.status, s.text AS statusText, t.issue, t.user AS lastUser, t.timestamp AS lastUpdated, (CASE WHEN u.task IS NOT NULL THEN 1 ELSE 0 END) AS has_entries, (CASE WHEN w.task IS NOT NULL THEN 1 ELSE 0 END) AS has_watch ';
			$sql_from = 'FROM tasks t ';
			$sql_from .= 'LEFT JOIN cells c ON (t.cell = c.id) ';
			$sql_from .= 'LEFT JOIN status s ON (t.status = s.status) ';
			$sql_from .= 'LEFT JOIN actions u ON (u.task = t.id AND u.user = "' . $this->db->real_escape_string($cur_user) . '") ';
			$sql_from .= 'LEFT JOIN actions w ON (w.task = t.id AND w.user = "' . $this->db->real_escape_string($cur_user) . '" AND w.status = "watch") ';
			$sql_where = 'WHERE t.status = "' . $this->db->real_escape_string($this->id) . '" ';

			if (!empty($this->issue)) {
				$sql_where .= 'AND t.issue = "' . $this->db->real_escape_string($this->issue) . '" ';
			}

			$sql_order = 'GROUP BY t.id ';
			$sql_order .= 'ORDER BY t.timestamp ASC';
		} else {
			// Get all open tasks
			$sql_select = 'SELECT t.cell, c.name AS cellName, c.name_ko AS cellNameKO, t.id AS task, t.status, s.text AS statusText, t.issue, t.user AS lastUser, t.timestamp AS lastUpdated, (CASE WHEN u.task IS NOT NULL THEN 1 ELSE 0 END) AS has_entries, (CASE WHEN w.task IS NOT NULL THEN 1 ELSE 0 END) AS has_watch ';
			$sql_from = 'FROM tasks t ';
			$sql_from .= 'LEFT JOIN cells c ON (t.cell = c.id) ';
			$sql_from .= 'INNER JOIN status s ON (t.status = s.status) ';
			$sql_from .= 'LEFT JOIN actions u ON (u.task = t.id AND u.user = "' . $this->db->real_escape_string($cur_user) . '") ';
			$sql_from .= 'LEFT JOIN actions w ON (w.task = t.id AND w.user = "' . $this->db->real_escape_string($cur_user) . '" AND w.status = "watch") ';
			$sql_where = 'WHERE s.include_open = 1 AND t.active = 1 ';

			if (!empty($this->issue)) {
				$sql_where .= 'AND t.issue = "' . $this->db->real_escape_string($this->issue) . '" ';
			}

			$sql_order = 'GROUP BY t.id ';
			$sql_order .= 'ORDER BY t.timestamp ASC';
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
	}
	
	
	public function Id() {
		return $this->id;
	}
	
	public function Text() {
		return $this->text;
	}
	
	public function Tasks() {
		return $this->tasks;
	}

	public function Total() {
		return count($this->tasks);
	}
	
}

