<?php

class eyewire_cell_mystic {
	private $id;
	private $name;
	private $name_ko;
	private $difficulty;
	private $active;
	private $mystic;
	private $status;
	private $statusKey;

	private $user;
	private $userA;
	private $userB;

	private $lastUser;
	private $lastUpdated;
	
	private $db;
	
	private $actions;

	public function __construct($cell) {
		// Save cell ID
		$this->id = $cell;
		
		// Save database reference
		$this->db = Globals::getInstance('database');

		// Load data
		$this->load();
	}

	private function load() {
		// Initialize values
		$this->name = '';
		$this->name_ko = '';
		$this->difficulty = 0;
		$this->active = false;
		$this->mystic = false;
		$this->status = '';
		$this->statusKey = '';
		$this->user = '';
		$this->userA = '';
		$this->userB = '';
		$this->lastUser = '';
		$this->lastUpdated = '';
		$this->actions = array();

		// Get basic cell info
		$res1 = $this->db->query('SELECT * FROM cells WHERE id = "' . $this->db->real_escape_string($this->id) . '" AND dataset = 11');
		$row1 = $res1->fetch_assoc();

		$this->name = $row1['name'];
		$this->name_ko = $row1['name_ko'];
		$this->difficulty = $row1['difficulty'];
		$this->active = ($row1['active'] == 1) ? true : false;

		if ($row1 !== false) {
			// Update mystic properties
			$this->mystic = true;
			$this->status = $row1['mystic_status'];
			$this->user = $row1['mystic_user'];
			$this->userA = $row1['mystic_user_a'];
			$this->userB = $row1['mystic_user_b'];

			// Get actions for cell
			$this->actions = array();

			$sql = 'SELECT a.id, a.status, s.localizedKey AS statusKey, a.user, a.notes, a.timestamp ';
			$sql .= 'FROM mystic_actions a LEFT JOIN status s ON (a.status = s.status) ';
			$sql .= 'WHERE a.cell = "' . $this->db->real_escape_string($this->id) . '" ';
			$sql .= 'ORDER BY a.id ASC';

			$res2 = $this->db->query($sql);

			if ($res2 !== false) {
				if ($res2->num_rows > 0) {
					while ($row2 = $res2->fetch_assoc()) {
						$this->actions[] = $row2;

						$this->lastUser = $row2['user'];
						$this->lastUpdated = $row2['timestamp'];
					}
				}
			}
		}
	}

	public function update_status($newStatus, $notes='') {
		// Get current user and roles
		$user = Globals::getInstance('session')->get_key('auth_user');
		$roles = Globals::getInstance('session')->get_key('auth_roles');

		// Validate user
		$testRole = (in_array('mystic', $roles) === true or in_array('admin', $roles) === true) ? true : false;
		$testAdmin = (in_array('admin', $roles) === true) ? true : false;
		$testA = ($user !== $this->userA or empty($this->userA)) ? true : false;
		$testB = ($user !== $this->userB or empty($this->userB)) ? true : false;

		if ($testRole !== true) {
			return false;
		}

		// Validate status change
		$newUser = $user;
		$newUserA = $this->userA;
		$newUserB = $this->userB;
		$error = false;

		switch ($newStatus) {
			case 'need-player-a':
				$newUser = '';
				$newUserA = '';

				if ($this->status != '' and $this->status != 'player-a' and $testAdmin !== true) {
					$error = true;
				}

				break;
			case 'player-a':
				$newUserA = $user;

				if ($this->status != 'need-player-a' and $this->status != '') {
					$error = true;
				}

				if (!empty($this->userA)) {
					$error = true;
				}
				
				break;
			case 'need-player-b':
				$newUser = '';
				$newUserB = '';

				if ($this->status != 'player-a' and $this->status != 'player-b' and $testAdmin !== true) {
					$error = true;
				}

				break;
			case 'player-b':
				$newUserB = $user;

				if ($this->status != 'need-player-b' or $testA !== true) {
					$error = true;
				}

				break;
			case 'need-admin':
				$newUser = '';

				if ($this->status != 'player-b' and $testAdmin !== true) {
					$error = true;
				}

				break;
			case 'complete':
				$newUser = '';

				if ($testAdmin !== true) {
					$error = true;
				}

				break;
		}

		// Check error status
		if ($error === true) {
			return false;
		}

		// Build SQL statements
		$sql1 = 'INSERT INTO mystic_actions (id, cell, status, user, notes, timestamp) ';
		$sql1 .= 'SELECT IFNULL(MAX(id), 0) + 1, ';
		$sql1 .= '"' . $this->db->real_escape_string($this->id) . '", ';
		$sql1 .= '"' . $this->db->real_escape_string($newStatus) . '", ';
		$sql1 .= '"' . $this->db->real_escape_string($user) . '", ';
		$sql1 .= '"' . $this->db->real_escape_string($notes) . '", ';
		$sql1 .= 'UTC_TIMESTAMP() ';
		$sql1 .= 'FROM mystic_actions ';
		$sql1 .= 'WHERE cell = "' . $this->db->real_escape_string($this->id) . '"';

		$sql2 = 'UPDATE cells SET ';
		$sql2 .= 'mystic_status = "' . $this->db->real_escape_string($newStatus) . '", ';
		$sql2 .= 'mystic_user = "' . $this->db->real_escape_string($newUser) . '", ';
		$sql2 .= 'mystic_user_a = "' . $this->db->real_escape_string($newUserA) . '", ';
		$sql2 .= 'mystic_user_b = "' . $this->db->real_escape_string($newUserB) . '" ';
		$sql2 .= 'WHERE id = "' . $this->db->real_escape_string($this->id) . '"';

		// Execute SQL statements
		$this->db->begin_transaction();
		$this->db->query($sql1);
		$this->db->query($sql2);
		$this->db->end_transaction();

		// Refresh cell data
		$this->load();

		// Return success result
		return true;
	}

	public static function get_summary($status='all', $active_only=true) {
		// Save database reference
		$db = Globals::getInstance('database');

		// Prepare result object
		$results = new stdClass();

		// Perform query
		if ($status === 'all') {
			$results->summary = array(
				'need-player-a' => 0,
				'need-player-b' => 0,
				'need-admin' => 0,
				'player-a' => 0,
				'player-b' => 0,
				'open-tasks' => 0
			);

			$results->count = 0;

			$res = $db->query('SELECT mystic_status AS status, COUNT(id) AS cell_count FROM cells WHERE dataset = 11 AND active = 1 GROUP BY mystic_status');

			if ($res !== false) {
				while ($row = $res->fetch_assoc()) {
					if ($row['status'] !== 'complete') {
						$results->summary[ $row['status'] ] = $row['cell_count'];

						$results->count += $row['cell_count'];
					}
				}
			}

			$res2 = $db->query('SELECT COUNT(*) FROM tasks t INNER JOIN status s ON (t.status = s.status) INNER JOIN cells c ON (t.cell = c.id) WHERE c.dataset = 11 AND t.active = 1 AND s.include_open = 1');

			if ($res2 !== false) {
				$row2 = $res2->fetch_row();
		
				$results->summary['open-tasks'] = $row2[0];
			}

		} else {
			$results->cells = array();
			$results->count = 0;

			$sql = 'SELECT DISTINCT m.id AS cell, m.name, m.name_ko, m.difficulty, m.active, m.mystic_status, m.mystic_user, m.mystic_user_a, m.mystic_user_b, a.user AS lastUser, a.timestamp AS lastUpdated, COUNT(t.cell) AS need_admin ';
			$sql .= 'FROM cells m ';
			$sql .= 'LEFT JOIN mystic_actions a ON (m.id = a.cell) ';
			$sql .= 'LEFT JOIN tasks t ON (m.id = t.cell AND t.status = "need-admin") ';
			$sql .= 'WHERE m.dataset = 11 AND m.mystic_status = "' . $db->real_escape_string($status) . '" ';

			if ($status === 'need-player-a') {
				$sql .= 'AND (a.id = (SELECT MAX(b.id) FROM mystic_actions b WHERE b.cell = a.cell) OR a.id IS NULL) ';
			} else {
				$sql .= 'AND a.id = (SELECT MAX(b.id) FROM mystic_actions b WHERE b.cell = a.cell) ';
			}

			if ($active_only === true) {
				$sql .= 'AND m.active = 1 ';
			}

			$sql .= 'GROUP BY m.id ';
			$sql .= 'ORDER BY m.name ASC';

			$res = $db->query($sql);

			if ($res !== false) {
				while ($row = $res->fetch_assoc()) {
					$c = new stdClass();
					$c->cell = $row['cell'];
					$c->cellName = $row['name'];
					$c->cellNameKO = $row['name_ko'];
					$c->difficulty = $row['difficulty'];
					$c->active = ($row['active'] == 1) ? true : false;
					$c->status = $row['mystic_status'];
					$c->user = ($row['mystic_status'] == 'player-a' or $row['mystic_status'] == 'player-b') ? $row['mystic_user'] : '';
					$c->userA = $row['mystic_user_a'];
					$c->userB = $row['mystic_user_b'];
					$c->lastUser = $row['lastUser'];
					$c->lastUpdated = $row['lastUpdated'];
					$c->needAdmin = ($row['need_admin'] > 0) ? 1 : 0;

					$results->cells[] = $c;
				}

				$results->count = count($results->cells);
			}
		}

		// Return results
		return $results;
	}

	public static function is_mystic($cellId) {
		$db = Globals::getInstance('database');

		$res = $db->query('SELECT dataset FROM cells WHERE id = "' . $db->real_escape_string($cellId) . '"');
		$row = $res->fetch_assoc();

		$result = ($row['dataset'] == 11) ? true : false;

		return $result;
	}



	public function Id() {
		return $this->id;
	}
	
	public function Actions() {
		return $this->actions;
	}

	public function ActionCount() {
		return count($this->actions);
	}

	public function Active() {
		return $this->active;
	}

	public function Difficulty() {
		return $this->difficulty;
	}

	public function LastUpdated() {
		return $this->lastUpdated;
	}

	public function LastUser() {
		return $this->lastUser;
	}

	public function Mystic() {
		return $this->mystic;
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

	public function Status() {
		return $this->status;
	}

	public function StatusKey() {
		return $this->statusKey;
	}

	public function User() {
		return $this->user;
	}

	public function UserA() {
		return $this->userA;
	}

	public function UserB() {
		return $this->userB;
	}

}
