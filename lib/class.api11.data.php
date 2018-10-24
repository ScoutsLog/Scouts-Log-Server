<?php

class api_data extends API11RequestBase {
	private $cell;
	private $issue;
	private $status;
	private $task;
	private $user;

	private $type;
	private $group;

	private $start;
	private $end;

	private $data;
	
	
	public function AllowDefault() {
		return false;
	}
	
	public function Allowed($verb) {
		switch ($verb) {
			case 'GET':
				return array('cell', 'end', 'group', 'issue', 'start', 'status', 'task', 'type', 'user');
				
				break;
		}
		
		return array();
	}
	
	public function Run() {
		if ($this->isValidRequest() === true) {
			// Get task from URI parameters
			$params = $this->request->ActionParameters();
			$tp = count($params);

			// Process parameters
			for ($p=0; $p < $tp; $p++) {
				$param = strtolower(preg_replace('/[^a-zA-Z0-9\-\_]+/', '', $params[$p]));

				switch ($param) {
					case 'cell':
						$this->cell = intval(preg_replace('/[^0-9]+/', '', $params[$p + 1]), 10);
						$p++;

						break;
					case 'end':
						$this->end = preg_replace('/[^0-9\-]+/', '', $params[$p + 1]);
						$this->end = date('Y-m-d', strtotime($this->end));
						$p++;

						break;
					case 'group':
						$this->group = strtolower(preg_replace('/[^a-zA-Z0-9\-]+/', '', $params[$p + 1]));
						$p++;

						break;
					case 'issue':
						$this->issue = strtolower(preg_replace('/[^a-zA-Z0-9\-]+/', '', $params[$p + 1]));
						$p++;

						break;
					case 'start':
						$this->start = preg_replace('/[^0-9\-]+/', '', $params[$p + 1]);
						$this->start = date('Y-m-d', strtotime($this->start));
						$p++;

						break;
					case 'status':
						$this->status = strtolower(preg_replace('/[^a-zA-Z\-]+/', '', $params[$p + 1]));
						$p++;

						break;
					case 'task':
						$this->task = intval(preg_replace('/[^0-9]+/', '', $params[$p + 1]), 10);
						$p++;

						break;
					case 'type':
						$this->type = strtolower(preg_replace('/[^a-zA-Z]+/', '', $params[$p + 1]));
						$p++;

						break;
					case 'user':
						$this->user = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '', $params[$p + 1]));
						$p++;

						break;
				}
			}
			
			// Check for missing end date
			if (empty($this->end) or $this->end == '0000-00-00') {
				$this->end = gmdate('Y-m-d');
			}

			// Check for missing type
			if (empty($this->type)) {
				$this->type = 'raw';
			}

			// Check for missing group
			if (empty($this->group)) {
				$this->group = 'status';
			}

			// Check for missing start date
			if (empty($this->start) or $this->start == '0000-00-00') {
					// Invalid start date specified
					
					$this->request->SetError(401);
					
					return;
			}

			// Check for reversed date range
			if (strtotime($this->start) > strtotime($this->end)) {
					// Start date is after end date
					
					$this->request->SetError(401);
					
					return;
			}

			// Check for valid status
			if (!empty($this->status)) {
				if (Utils::isValidStatus($this->status) !== true) {
					// Invalid status specified
					
					$this->request->SetError(401);
					
					return;
				}
			}

			// Check for valid group field
			if ($this->type != 'raw') {
				$_group = array('week', 'month', 'year', 'cell', 'task', 'status', 'issue', 'user');

				if (!in_array($this->group, $_group)) {
					// Invalid group field specified
					
					$this->request->SetError(401);
					
					return;
				}
			}


			// Perform query for data
			$this->query_data();

			// Group data
			if (!empty($this->type) and $this->type != 'raw') {
				$this->group_data();
			}

			// Format data for chart type
			switch ($this->type) {
				case 'bar':
					$this->format_bar();

					break;
				case 'doughnut':
				case 'line':
				case 'pie':
				case 'polararea':
				case 'radar':
				default:
					$this->format_raw();

					break;
			}

		} else {
			// Bad request
			
			$this->request->SetError(401);
		}
	}


	private function query_data() {
		// Get database instance
		$db = Globals::getInstance('database');

		// Prepare SQL query
		if ($this->type != 'raw') {
			switch ($this->group) {
				case 'cell':
					$sql = 'SELECT t.cell, COUNT(a.task) AS tasks ';

					break;
				case 'issue':
					$sql = 'SELECT t.issue, COUNT(a.task) AS tasks ';

					break;
				case 'month':
					$sql = 'SELECT CONCAT(YEAR(a.timestamp), " ", MONTHNAME(a.timestamp)) AS month, COUNT(a.task) AS tasks ';

					break;
				case 'status':
					$sql = 'SELECT a.status, COUNT(a.task) AS tasks ';

					break;
				case 'user':
					$sql = 'SELECT a.user, COUNT(a.task) AS tasks ';

					break;
				case 'week':
					$sql = 'SELECT CONCAT(YEAR(a.timestamp), " ", WEEKOFYEAR(a.timestamp)) AS week, COUNT(a.task) AS tasks ';

					break;
				case 'year':
					$sql = 'SELECT YEAR(a.timestamp), COUNT(a.task) AS tasks ';

					break;
			}
		} else {
			$sql = 'SELECT WEEKOFYEAR(a.timestamp) AS week, MONTHNAME(a.timestamp) AS month, YEAR(a.timestamp) AS year, t.cell, a.task, a.status, t.issue, a.user ';
		}

		$sql .= 'FROM actions a INNER JOIN tasks t ON (a.task = t.id) ';
		$sql .= 'WHERE a.id = 1 ';
		$sql .= 'AND a.timestamp >= "' . $db->real_escape_string($this->start) . '" ';
		$sql .= 'AND a.timestamp <= "' . $db->real_escape_string($this->end) . '" ';

		if (!empty($this->cell)) {
			$sql .= 'AND t.cell = ' . $this->cell . ' ';
		}

		if (!empty($this->task)) {
			$sql .= 'AND a.task = ' . $this->task . ' ';
		}

		if (!empty($this->status)) {
			$sql .= 'AND a.status = "' . $db->real_escape_string($this->status) . '" ';
		}

		if (!empty($this->issue)) {
			$sql .= 'AND t.issue = "' . $db->real_escape_string($this->issue) . '" ';
		}

		if (!empty($this->user)) {
			$sql .= 'AND a.user = "' . $db->real_escape_string($this->user) . '" ';
		}

		if ($type != 'raw') {
			$sql .= 'GROUP BY ' . $this->group . ' ';
		}

		$sql .= 'ORDER BY a.timestamp';

		error_log('SQL DEBUG: ' . $sql);

		// Execute query
		$res = $db->query($sql);

		// Get results
		$this->data = array();

		if ($res !== false) {
			while ($row = $res->fetch_assoc()) {
				$this->data[] = $row;
			}
		}
	}

	private function group_data() {
		$data = new stdClass();
		$data->labels = array();
		$data->data = array();

		// Pre-populate labels (if needed)
		switch ($this->group) {
			case 'week':
				$_current = $this->start;

				while (strtotime($_current) <= strtotime($this->end)) {
					$_label = date('Y W', strtotime($_current));

					$data->labels[] = $_label;
					$data->data[$_label] = 0;

					$_current = date('Y-m-d', strtotime('+1 WEEK', strtotime($_current)));
				}

				break;
			case 'month':
				$_current = $this->start;

				while (strtotime($_current) <= strtotime($this->end)) {
					$_label = date('Y F', strtotime($_current));

					$data->labels[] = $_label;
					$data->data[$_label] = 0;

					$_current = date('Y-m-d', strtotime('+1 MONTH', strtotime($_current)));
				}

				break;
			case 'year':
				$_current = $this->start;

				while (strtotime($_current) <= strtotime($this->end)) {
					$_label = date('Y', strtotime($_current));

					$data->labels[] = $_label;
					$data->data[$_label] = 0;

					$_current = date('Y-m-d', strtotime('+1 YEAR',strtotime($_current)));
				}
			case 'status':
				$_status = Utils::GetStatusList();

				foreach ($_status as $_s) {
					if ($_s['active'] == 1) {
						$data->labels[] = $_s['id'];
						$data->data[$_s['id']] = 0;
					}
				}

				break;
		}

		// Group data
		foreach ($this->data as $_entry) {
			// Determine label
			$_label = $_entry[$this->group];

			if (in_array($_label, $data->labels)) {
				$data->labels[] = $_label;
			}

			$data->data[$_label] = intval($_entry['tasks'], 10);
		}

		// Save grouped data over raw data
		$this->data = $data;
	}


	private function format_bar() {
		// Create output object
		$output = new stdClass();
		$output->labels = $this->data->labels;
		$output->datasets = array();

		// Create dataset
		$set = new stdClass();
		$set->label = "Cubes by " . ucwords($this->group);
		$set->data = array_values($this->data->data);

		$output->datasets[] = $set;

		// Save result to request output
		$this->request->OutputType('text/json');
		$this->request->OutputReplace(json_encode($output));
	}


	private function format_raw() {
		// Save result to request output
		$this->request->OutputType('text/json');
		$this->request->OutputReplace(json_encode($this->data));
	}

}
