<?php

class eyewire_stats {
	private $db;

	public $data;


	public function __construct() {
		// Get database instance
		$this->db = Globals::getInstance('database');

		// Initialize data object
		$this->init();
	}

	private function init() {
		$this->data = new stdClass();
		$this->data->cells = 0;
		$this->data->cell_summary = array();
		$this->data->tasks = 0;
		$this->data->task_summary = array();
	}


	public function get_overall() {
		$this->init();

		$this->get_cell_count();
		$this->get_cell_summary();
		$this->get_open_task_count();
		$this->get_open_task_summary();
	}

	
	public function get_cell($cell) {
		$this->data = new stdClass();

	}

	public function get_cell_count() {
		$cells = EyeWire::ActiveCells();

		$this->data->cells = count($cells);
	}

	public function get_cell_summary() {
		$res = $this->db->query('SELECT c.id AS cell, c.name AS cellName, c.name_ko AS cellNameKO, c.difficulty, COUNT(t.id) AS tasks FROM cells c INNER JOIN ((SELECT c2.id FROM cells c2 WHERE c2.active = 1) UNION DISTINCT (SELECT t2.cell FROM tasks t2 WHERE t2.active = 1)) cl ON (cl.id = c.id) LEFT JOIN tasks t ON (c.id = t.cell AND t.active = 1) GROUP BY c.id ORDER BY c.difficulty, c.name');
 
		$summary = array();

		if ($res !== false) {
			while ($row = $res->fetch_assoc()) {
				$summary[ $row['cell'] ] = $row;
			}
		}

		$this->data->cell_summary = $summary;
		
		$this->data->cells = count($summary);
	}


	public function get_open_task_count() {
		$res = $this->db->query('SELECT COUNT(*) FROM tasks t INNER JOIN status s ON (t.status = s.status) WHERE t.active = 1 AND s.include_open = 1');

		if ($res !== false) {
			$row = $res->fetch_row();
		
			// Set task count
			$this->data->tasks = $row[0];
		}
	}

	public function get_open_task_summary() {
		$res = $this->db->query('select s.status, s.text AS statusText, SUM(CASE WHEN t.id IS NOT NULL THEN 1 ELSE 0 END) AS tasks, s.sequence from status s left join tasks t on (t.status = s.status AND t.active = 1) where s.include_open = 1 group by s.status order by s.sequence');

		$summary = array();
		$this->data->tasks = 0;

		if ($res !== false) {
			while ($row = $res->fetch_assoc()) {
				$summary[ $row['status'] ] = array(
					'status' => $row['status'],
					'statusText' => $row['statusText'],
					'sequence' => $row['sequence']
				);
				
				$summary[ $row['status'] ]['tasks'] += $row['tasks'];
				$this->data->tasks += $row['tasks'];
			}
		} else {
			error_log('SQL ERROR: ' . $this->db->error());
		}

		$this->data->task_summary = $summary;
	}

}

