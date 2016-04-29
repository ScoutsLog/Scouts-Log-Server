<?php

class eyewire_user {
	private $username;
	
	private $db;
	
	private $tasks;
	private $count;
	
	
	public function __construct($user) {
		$this->username = $user;

		$this->db = Globals::getInstance('database');
		
		$this->tasks = array();
		$this->count = 0;
	}
	
	public function get_tasks($status='', $issue='') {
                // Get current user
                $cur_user = Globals::getInstance('session')->get_key('auth_user');

		// Build SQL statement
		$sql_select = 'SELECT t.cell, c.name AS cellName, c.name_ko AS cellNameKO, t.id AS task, t.status, s.text AS statusText, t.issue, t.user AS lastUser, t.timestamp AS lastUpdated, (CASE WHEN u.task IS NOT NULL THEN 1 ELSE 0 END) AS has_entries, (CASE WHEN w.task IS NOT NULL THEN 1 ELSE 0 END) AS has_watch ';
		$sql_from = 'FROM tasks t ';
		$sql_from .= 'LEFT JOIN cells c ON (t.cell = c.id) ';
		$sql_from .= 'LEFT JOIN status s ON (t.status = s.status) ';
		$sql_from .= 'LEFT JOIN actions u ON (u.task = t.id AND u.user = "' . $this->db->real_escape_string($cur_user) . '") ';
		$sql_from .= 'LEFT JOIN actions u2 ON (u2.task = t.id AND u2.user = "' . $this->db->real_escape_string($this->username) . '") ';
		$sql_from .= 'LEFT JOIN actions w ON (w.task = t.id AND w.user = "' . $this->db->real_escape_string($cur_user) . '" AND w.status = "watch") ';

		// Parameter: user
		$sql_where = 'WHERE t.id = u2.task ';

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
		if (!empty($this->issue)) {
			$sql_where .= 'AND t.issue = "' . $this->db->real_escape_string($issue) . '" ';
		}

		$sql_order = 'GROUP BY t.id ';
		$sql_order .= 'ORDER BY t.timestamp ASC';

		// Execute query
		$res = $this->db->query($sql_select . $sql_from . $sql_where . $sql_order);
		
		// Prepare results
		$this->tasks = array();
		$this->count = 0;
		
		if ($res !== false) {
			while ($row = $res->fetch_assoc()) {
				$this->tasks[] = $row;
			}

			$this->count = count($this->tasks);
		}
	}

	public function get_tasks_count($status='', $issue='') {
                // Get current user
                $cur_user = Globals::getInstance('session')->get_key('auth_user');

		// Build SQL statement
		$sql_select = 'SELECT COUNT(DISTINCT t.id) AS tasks ';
		$sql_from = 'FROM tasks t ';
		$sql_from .= 'LEFT JOIN actions u ON (u.task = t.id AND u.user = "' . $this->db->real_escape_string($this->username) . '") ';

		// Parameter: user
		$sql_where = 'WHERE t.id = u.task ';

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
		if (!empty($this->issue)) {
			$sql_where .= 'AND t.issue = "' . $this->db->real_escape_string($issue) . '" ';
		}

		// Execute query
		$res = $this->db->query($sql_select . $sql_from . $sql_where);
		
		// Prepare results
		$this->tasks = array();
		$this->count = 0;
		
		if ($res !== false) {
			$row = $res->fetch_assoc();

			$this->count = $row['tasks'];
		}
	}
	
	
	
	public function Username() {
		return $this->username;
	}
	
	public function Tasks() {
		return $this->tasks;
	}

	public function TaskCount() {
		return $this->count;
	}
	
}

