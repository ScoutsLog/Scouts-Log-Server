<?php

class eyewire_rating {

	private $id;
	private $task;
	private $instructions;
	private $active;

	private $ratings;
	private $counts;
    private $counts_total;

	private $has_rated;

	private $db;


	public function __construct($cell) {
		// Save cell ID
		$this->id = $cell;

		// Save database reference
		$this->db = Globals::getInstance('database');

		// Get basic cell rating info
		$res = $this->db->query('SELECT * FROM cells_rating WHERE id = "' . $this->db->real_escape_string($this->id) . '"');
		$row = $res->fetch_assoc();

		$this->task = $row['task'];
		$this->instructions = $row['instructions'];
		$this->active = ($row['active'] == 1) ? true : false;

		// Get current user
		$user = Globals::getInstance('session')->get_key('auth_user');

		// Get ratings for cell
		$this->ratings = array();
		$this->counts = array();
		$this->has_rated = false;

		$res2 = $this->db->query('SELECT * FROM rating WHERE cell = "' . $this->db->real_escape_string($this->id) . '" ORDER BY id');

		if ($res2 !== false) {
			if ($res2->num_rows > 0) {
				while ($row2 = $res2->fetch_assoc()) {
					$this->ratings[] = $row2;

					if (!empty($row2['rating'])) {
						$this->counts[$row2['rating']]++;
                        $this->counts_total++;

						if ($row2['user'] == $user) {
							$this->has_rated = true;
						}
					}
				}
			}
		}
	}

	public function SaveRating($data) {
		if ($data->cell == $this->id) {
			// Get data
			$rating = intval(preg_replace("/[^0-9]+/", '', $data->rating), 10);
			$notes = $data->notes;

			// Get current user
			$user = Globals::getInstance('session')->get_key('auth_user');

			// Build SQL statement
			$sql = 'INSERT INTO rating (cell, user, rating, notes, timestamp) VALUES(';
			$sql .= '"' . $this->db->real_escape_string($data->cell) . '", ';
			$sql .= '"' . $this->db->real_escape_string($user) . '", ';
			$sql .= '"' . $this->db->real_escape_string($rating) . '", ';
			$sql .= '"' . $this->db->real_escape_string($notes) . '", ';
			$sql .= 'UTC_TIMESTAMP())';
			
			// Execute SQL
			$this->db->begin_transaction();
			$this->db->query($sql);
			$this->db->end_transaction();

			return true;
		}

		return false;
	}

	public function SetActive($state) {
		$this->active = ($state === true or $state == 1) ? true : false;

		$_active = ($this->active === true) ? 1 : 0;

		$this->db->query('UPDATE cells_rating SET active = "' . $this->db->real_escape_string($_active) . '" WHERE id = "' . $this->db->real_escape_string($this->id) . '"');
	}


	public static function GetActive() {
		$cells = array();

		$db = Globals::getInstance('database');

		$res = $db->query('SELECT id FROM cells_rating WHERE active = 1 ORDER BY id');

		if ($res !== false) {
			if ($res->num_rows > 0) {
				while ($row = $res->fetch_assoc()) {
					$rating = new eyewire_rating($row['id']);

					$e = $rating->Ratings();

					$r = new stdClass();
					$r->cell = $rating->Id();
					$r->task = $rating->Task();
					$r->instructions = $rating->Instructions();
					$r->results = $rating->Results();
					$r->results_total = $rating->TotalResults();
					$r->has_rated = $rating->HasRated();

					$cells[] = $r;
				}
			}
		}

		return $cells;
	}



	public function Id() {
		return $this->id;
	}

	public function Active() {
		return $this->active;
	}

	public function HasRated() {
		return $this->has_rated;
	}

	public function Instructions() {
		return $this->instructions;
	}

	public function Ratings() {
		return $this->ratings;
	}

	public function Results() {
		return $this->counts;
	}

	public function Task() {
		return $this->task;
	}

	public function TotalResults() {
		return $this->counts_total;
	}


}
