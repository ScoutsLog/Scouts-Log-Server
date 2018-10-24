<?php

class eyewire_task {
	private $id;
	private $cell;
	private $cellName;
	private $cellNameKO;
	private $status;
	private $statusText;
	private $issue;
	private $lastUser;
	private $lastUpdated;
	private $mismatched;

	private $db;

	private $actions;


	public function __construct($task) {
		// Save task ID
		$this->id = $task;

		// Save database reference
		$this->db = Globals::getInstance('database');
		
		// Set default values
		$this->cell = 0;
		$this->cellName = 'n/a';
		$this->status = '';
		$this->statusText = 'n/a';
		$this->issue = '';
		$this->weight = 0;
		$this->votes = 0;
		$this->votes_max = 0;
		$this->lastUser = 'n/a';
		$this->lastUpdated = 'n/a';
		$this->mismatched = false;

		$this->actions = array();

		// Load details
		$this->load();
	}


	private function load() {
		// Get basic task info
		
		// Get current status of task
		$sql = 'SELECT t.cell, c.name AS cellName, c.name_ko AS cellNameKO, t.status, s.text AS statusText, t.issue, t.user AS lastUser, t.timestamp AS lastUpdated ';
		$sql .= 'FROM tasks t ';
		$sql .= 'LEFT JOIN cells c ON (t.cell = c.id) ';
		$sql .= 'LEFT JOIN status s ON (t.status = s.status) ';
		$sql .= 'WHERE t.id = "' . $this->db->real_escape_string($this->id) . '"';

		$res1 = $this->db->query($sql);

		if ($res1 !== false) {
			if ($res1->num_rows > 0) {
				$row = $res1->fetch_assoc();
			
				$this->cell = $row['cell'];
				$this->cellName = $row['cellName'];
				$this->cellNameKO = $row['cellNameKO'];
				$this->status = $row['status'];
				$this->statusText = $row['statusText'];
				$this->issue = $row['issue'];
				$this->lastUser = $row['lastUser'];
				$this->lastUpdated = $row['lastUpdated'];
			}
		}

		// Get actions for task
		$this->actions = array();

		$sql = 'SELECT a.id, a.status, s.text as statusText, a.issue, a.user, a.reaped, a.notes, a.image, a.timestamp ';
		$sql .= 'FROM actions a LEFT JOIN status s ON (a.status = s.status) ';
		$sql .= 'WHERE a.task = "' . $this->db->real_escape_string($this->id) . '" ';
		$sql .= 'ORDER BY a.id ASC';

		$res2 = $this->db->query($sql);

		if ($res2 !== false) {
			if ($res2->num_rows > 0) {
				while ($row = $res2->fetch_assoc()) {
					$this->actions[] = $row;
	
					$this->lastUser = $row['user'];
					$this->lastUpdated = $row['timestamp'];
				}
			}
		}

		// Check for cell ID mismatched
		$res3 = $this->db->query('SELECT COUNT(DISTINCT cell) AS cell_count FROM actions WHERE task = "' . $this->db->real_escape_string($this->id) . '"');

		if ($res3 !== false) {
			$row = $res3->fetch_assoc();

			if ($row['cell_count'] > 1) {
				$this->mismatched = true;
			}
		}
	}


	/*
	 * Public Methods
	 */

	public function AddAction($data) {
		//if ($data->cell == $this->cell and $data->task == $this->id) {
		if ($data->task == $this->id) {
			$this->cell = $data->cell;

			// Get current user
			$user = Globals::getInstance('session')->get_key('auth_user');
		
			// Process image data
			$image = '';
			$image_do_status = -1;

			// Check for standard image processing
			if (!empty($data->image)) {
				if (substr($data->image, 0, 7) == 'http://' or substr($data->image, 0, 8) == 'https://') {
					// Image data is a web link
			
					$image = $data->image;
				} elseif (substr($data->image, 0, 5) == 'data:') {
					// Image is inline data
			
					$_pos1 = strpos($data->image, ';');
					$_pos2 = strpos($data->image, ',', $_pos1);
			
					$encoding = substr($data->image, $_pos1 + 1, $_pos2 - $_pos1 - 1);
					$image_data = substr($data->image, $_pos2 + 1);
	
					$image = $this->SaveImage($encoding, $image_data);	
				}
			}
			
			// Check for browser extension image processing
			if (!empty($data->image2D) or !empty($data->image3D)) {
				// Get 2D image data
				$image2D = null;
				$image3D = null;
				
				if (!empty($data->image2D)) {
					$_pos1 = strpos($data->image2D, ';');
					$_pos2 = strpos($data->image2D, ',', $_pos1);
			
					$_enc = substr($data->image2D, $_pos1 + 1, $_pos2 - $_pos1 - 1);
					$_data = substr($data->image2D, $_pos2 + 1);
					
					if ($_enc == 'base64') {
						$image2D = imagecreatefromstring( base64_decode($_data) );
					}
				}
				
				if (!empty($data->image3D)) {
					$_pos1 = strpos($data->image3D, ';');
					$_pos2 = strpos($data->image3D, ',', $_pos1);
			
					$_enc = substr($data->image3D, $_pos1 + 1, $_pos2 - $_pos1 - 1);
					$_data = substr($data->image3D, $_pos2 + 1);
					
					if ($_enc == 'base64') {
						$image3D = imagecreatefromstring( base64_decode($_data) );
					}
				}
				
				// Create combined image
				$w = imagesx($image3D);
				$h = imagesy($image3D);
				
				$im = imagecreatetruecolor($w, $h);
				$bk = imagecolorallocate($im, 35, 35, 35);
				imagefill($im, 0, 0, $bk);
				
				if (!is_null($image2D)) {
					// Crop 3D image and copy
					$_w = floor($w / 2);
					$_x = floor($w / 4);
						
					imagecopy($im, $image3D, 0, 0, $_x, 0, $_w, $h);
					
					// Copy 2D image
					$_w2 = imagesx($image2D);
					$_h2 = imagesy($image2D);
					
					imagecopy($im, $image2D, $_w, 0, 0, 0, $_w2, $_h2);
					
					// Add divider line
					$_c = imagecolorallocate($im, 136, 136, 136);
					imagedashedline($im, $_w, 0, $_w, $h, $_c);
				} else {
					// Copy 3D as is
					
					imagecopy($im, $image3D, 0, 0, 0, 0, $w, $h);
				}
				
				// Cleanup memory
				imagedestroy($image2D);
				imagedestroy($image3D);
				
				// Check for annotation
				if (!empty($data->imageAN)) {
					$imageAN = null;
					
					$_pos1 = strpos($data->imageAN, ';');
					$_pos2 = strpos($data->imageAN, ',', $_pos1);
			
					$_enc = substr($data->imageAN, $_pos1 + 1, $_pos2 - $_pos1 - 1);
					$_data = substr($data->imageAN, $_pos2 + 1);
					
					if ($_enc == 'base64') {
						$imageAN = imagecreatefromstring( base64_decode($_data) );
					}
					
					if (!is_null($imageAN)) {
						$_w = imagesx($imageAN);
						$_h = imagesy($imageAN);
						
						imagecopy($im, $imageAN, 0, 0, 0, 0, $_w, $_h);
						
						imagedestroy($imageAN);
					}
				}
				
				// Add details to image
				$wht = imagecolorallocate($im, 204, 204, 204);
				$blk = imagecolorallocatealpha($im, 0, 0, 0, 63);
				imagefilledrectangle($im, 5, 5, 615, 63, $blk);
				
				imagettftext($im, 12, 0, 10, 22, $wht, '/home/scoutslog/private/fonts/arial.ttf', 'Task: ' . $this->id);
				imagettftext($im, 12, 0, 10, 40, $wht, '/home/scoutslog/private/fonts/arial.ttf', 'Cell: ' . $this->cell);
				imagettftext($im, 12, 0, 10, 58, $wht, '/home/scoutslog/private/fonts/arial.ttf', 'User: ' . $user);

				imagettftext($im, 12, 0, 310, 22, $wht, '/home/scoutslog/private/fonts/arial.ttf', '큐브: ' . $this->id);
				imagettftext($im, 12, 0, 310, 40, $wht, '/home/scoutslog/private/fonts/arial.ttf', '세포: ' . $this->cell);
				imagettftext($im, 12, 0, 310, 58, $wht, '/home/scoutslog/private/fonts/arial.ttf', '플레이어 이름: ' . $user);
				
				
				// Save final image to file
				$img = self::GetNewImagePath($this->cell, $this->id);
			
				if ($img !== false) {
					// Save image file
					imagepng($im, $img->file);
					
					// Cleanup
					imagedestroy($im);
					
					// Get URL to file
					$image = $img->url;
					$image_do_status = 0;
				}
			}

			// Build SQL statement
			$sql = 'INSERT INTO actions (id, task, status, issue, user, reaped, notes, image, image_do_status, timestamp) ';
			$sql .= 'SELECT IFNULL(MAX(id), 0) + 1, ';
			$sql .= '"' . $this->db->real_escape_string($data->task) . '", ';
			$sql .= '"' . $this->db->real_escape_string($data->status) . '", ';
			$sql .= '"' . $this->db->real_escape_string($data->issue) . '", ';
			$sql .= '"' . $this->db->real_escape_string($user) . '", ';
			$sql .= '"' . $this->db->real_escape_string($data->reaped) . '", ';
			$sql .= '"' . $this->db->real_escape_string($data->notes) . '", ';
			$sql .= '"' . $this->db->real_escape_string($image) . '", ';
			$sql .= $image_do_status . ', ';
			$sql .= ' UTC_TIMESTAMP() ';
			$sql .= 'FROM actions ';
			$sql .= 'WHERE task = "' . $this->db->real_escape_string($data->task) . '"';

			$active = ($data->status == 'good') ? 0 : 1;

			if ($data->status != 'note') {
				if ($data->issue != '') {
					$sql2 = 'INSERT INTO tasks (id, cell, status, issue, user, timestamp, active) VALUES(';
					$sql2 .= '"' . $this->db->real_escape_string($data->task) . '", ';
					$sql2 .= '"' . $this->db->real_escape_string($data->cell) . '",';
					$sql2 .= '"' . $this->db->real_escape_string($data->status) . '",';
					$sql2 .= '"' . $this->db->real_escape_string($data->issue) . '",';
					$sql2 .= '"' . $this->db->real_escape_string($user) . '",';
					$sql2 .= 'UTC_TIMESTAMP(),';
					$sql2 .= $active . ') ';
					$sql2 .= 'ON DUPLICATE KEY UPDATE cell = VALUES(cell), status = VALUES(status), issue = VALUES(issue), user = VALUES(user), timestamp = VALUES(timestamp), active = VALUES(active) ';
				} else {
					$sql2 = 'INSERT INTO tasks (id, cell, status, user, timestamp, active) VALUES(';
					$sql2 .= '"' . $this->db->real_escape_string($data->task) . '", ';
					$sql2 .= '"' . $this->db->real_escape_string($data->cell) . '",';
					$sql2 .= '"' . $this->db->real_escape_string($data->status) . '",';
					$sql2 .= '"' . $this->db->real_escape_string($user) . '",';
					$sql2 .= 'UTC_TIMESTAMP(),';
					$sql2 .= $active . ') ';
					$sql2 .= 'ON DUPLICATE KEY UPDATE cell = VALUES(cell), status = VALUES(status), user = VALUES(user), timestamp = VALUES(timestamp), active = VALUES(active) ';
				}
			} else {
				if ($data->issue != '') {
					$sql2 = 'INSERT IGNORE INTO tasks (id, cell, status, issue, user, timestamp, active) VALUES(';
					$sql2 .= '"' . $this->db->real_escape_string($data->task) . '", ';
					$sql2 .= '"' . $this->db->real_escape_string($data->cell) . '",';
					$sql2 .= '"' . $this->db->real_escape_string($data->status) . '",';
					$sql2 .= '"' . $this->db->real_escape_string($data->issue) . '",';
					$sql2 .= '"' . $this->db->real_escape_string($user) . '",';
					$sql2 .= 'UTC_TIMESTAMP(),';
					$sql2 .= $active . ') ';
					$sql2 .= 'ON DUPLICATE KEY UPDATE cell = VALUES(cell), issue = VALUES(issue), user = VALUES(user), timestamp = VALUES(timestamp) ';
				} else {
					$sql2 = 'INSERT IGNORE INTO tasks (id, cell, status, user, timestamp, active) VALUES(';
					$sql2 .= '"' . $this->db->real_escape_string($data->task) . '", ';
					$sql2 .= '"' . $this->db->real_escape_string($data->cell) . '",';
					$sql2 .= '"' . $this->db->real_escape_string($data->status) . '",';
					$sql2 .= '"' . $this->db->real_escape_string($user) . '",';
					$sql2 .= 'UTC_TIMESTAMP(),';
					$sql2 .= $active . ') ';
					$sql2 .= 'ON DUPLICATE KEY UPDATE cell = VALUES(cell), user = VALUES(user), timestamp = VALUES(timestamp) ';
				}
			}

			// Execute SQL
			$this->db->begin_transaction();
			$this->db->query($sql);
			$this->db->query($sql2);
			$this->db->end_transaction();

			return true;
		}

		return false;
	}

	public function AddAction2($data, $image_data) {
		if ($data->task == $this->id) {
			$this->cell = $data->cell;

			// Get current user
			$user = Globals::getInstance('session')->get_key('auth_user');
		
			// Process image data
			$image = '';
			$image_do_status = -1;

			if (!empty($image_data)) {
				$img = self::GetNewImagePath($this->cell, $this->id);

				if (move_uploaded_file($image_data['tmp_name'], $img->file)) {
					$image = $img->url;
					$image_do_status = 0;
				}
			}

			// Build SQL statement
			$sql = 'INSERT INTO actions (id, task, status, issue, user, reaped, notes, image, image_do_status, timestamp) ';
			$sql .= 'SELECT IFNULL(MAX(id), 0) + 1, ';
			$sql .= '"' . $this->db->real_escape_string($data->task) . '", ';
			$sql .= '"' . $this->db->real_escape_string($data->status) . '", ';
			$sql .= '"' . $this->db->real_escape_string($data->issue) . '", ';
			$sql .= '"' . $this->db->real_escape_string($user) . '", ';
			$sql .= '"' . $this->db->real_escape_string($data->reaped) . '", ';
			$sql .= '"' . $this->db->real_escape_string($data->notes) . '", ';
			$sql .= '"' . $this->db->real_escape_string($image) . '", ';
			$sql .= $image_do_status . ', ';
			$sql .= ' UTC_TIMESTAMP() ';
			$sql .= 'FROM actions ';
			$sql .= 'WHERE task = "' . $this->db->real_escape_string($data->task) . '"';

			$active = ($data->status == 'good') ? 0 : 1;

			if ($data->status != 'note') {
				if ($data->issue != '') {
					$sql2 = 'INSERT INTO tasks (id, cell, status, issue, user, timestamp, active) VALUES(';
					$sql2 .= '"' . $this->db->real_escape_string($data->task) . '", ';
					$sql2 .= '"' . $this->db->real_escape_string($data->cell) . '",';
					$sql2 .= '"' . $this->db->real_escape_string($data->status) . '",';
					$sql2 .= '"' . $this->db->real_escape_string($data->issue) . '",';
					$sql2 .= '"' . $this->db->real_escape_string($user) . '",';
					$sql2 .= 'UTC_TIMESTAMP(),';
					$sql2 .= $active . ') ';
					$sql2 .= 'ON DUPLICATE KEY UPDATE cell = VALUES(cell), status = VALUES(status), issue = VALUES(issue), user = VALUES(user), timestamp = VALUES(timestamp), active = VALUES(active) ';
				} else {
					$sql2 = 'INSERT INTO tasks (id, cell, status, user, timestamp, active) VALUES(';
					$sql2 .= '"' . $this->db->real_escape_string($data->task) . '", ';
					$sql2 .= '"' . $this->db->real_escape_string($data->cell) . '",';
					$sql2 .= '"' . $this->db->real_escape_string($data->status) . '",';
					$sql2 .= '"' . $this->db->real_escape_string($user) . '",';
					$sql2 .= 'UTC_TIMESTAMP(),';
					$sql2 .= $active . ') ';
					$sql2 .= 'ON DUPLICATE KEY UPDATE cell = VALUES(cell), status = VALUES(status), user = VALUES(user), timestamp = VALUES(timestamp), active = VALUES(active) ';
				}
			} else {
				if ($data->issue != '') {
					$sql2 = 'INSERT IGNORE INTO tasks (id, cell, status, issue, user, timestamp, active) VALUES(';
					$sql2 .= '"' . $this->db->real_escape_string($data->task) . '", ';
					$sql2 .= '"' . $this->db->real_escape_string($data->cell) . '",';
					$sql2 .= '"' . $this->db->real_escape_string($data->status) . '",';
					$sql2 .= '"' . $this->db->real_escape_string($data->issue) . '",';
					$sql2 .= '"' . $this->db->real_escape_string($user) . '",';
					$sql2 .= 'UTC_TIMESTAMP(),';
					$sql2 .= $active . ') ';
					$sql2 .= 'ON DUPLICATE KEY UPDATE cell = VALUES(cell), issue = VALUES(issue), user = VALUES(user), timestamp = VALUES(timestamp) ';
				} else {
					$sql2 = 'INSERT IGNORE INTO tasks (id, cell, status, user, timestamp, active) VALUES(';
					$sql2 .= '"' . $this->db->real_escape_string($data->task) . '", ';
					$sql2 .= '"' . $this->db->real_escape_string($data->cell) . '",';
					$sql2 .= '"' . $this->db->real_escape_string($data->status) . '",';
					$sql2 .= '"' . $this->db->real_escape_string($user) . '",';
					$sql2 .= 'UTC_TIMESTAMP(),';
					$sql2 .= $active . ') ';
					$sql2 .= 'ON DUPLICATE KEY UPDATE cell = VALUES(cell), user = VALUES(user), timestamp = VALUES(timestamp) ';
				}
			}

			// Execute SQL
			$this->db->begin_transaction();
			$this->db->query($sql);
			$this->db->query($sql2);
			$this->db->end_transaction();

			return true;
		}

		return false;
	}


	public function AddSubmission($data) {
		if ($data->status == 'finished') {
			// Get database object
			$db = Globals::getInstance('database');

			// Build SQL statement
			$user = Globals::getInstance('session')->get_key('auth_user');
			$tb = ($data->trailblazer === true) ? 1 : 0;

			$sql = 'INSERT INTO submissions (user, cell, task, score, accuracy, type, trailblazer, timestamp) VALUES(';
			$sql .= '"' . $db->real_escape_string($user) . '", ';
			$sql .= '"' . $db->real_escape_string($data->cell) . '", ';
			$sql .= '"' . $db->real_escape_string($data->task) . '", ';
			$sql .= '"' . $db->real_escape_string($data->score) . '", ';
			$sql .= '"' . $db->real_escape_string($data->accuracy) . '", ';
			$sql .= '"' . $db->real_escape_string($data->special) . '", ';
			$sql .= '"' . $tb . '", ';
			$sql .= 'UTC_TIMESTAMP() )';

			// Execute SQL
			$db->begin_transaction();
			$db->query($sql);
			$db->end_transaction();

			//return true;
		}

		return true;
	}

	


	public static function GetFullActions($task) {
		$actions = array();

		$db = Globals::getInstance('database');

		$sql = 'SELECT a.*, s.text as statusText ';
		$sql .= 'FROM actions a LEFT JOIN status s ON (a.status = s.status) ';
		$sql .= 'WHERE a.task = "' . $db->real_escape_string($task) . '" ';
		$sql .= 'ORDER BY a.timestamp ASC';

		$res = $db->query($sql);

		if ($res !== false) {
			if ($res->num_rows > 0) {
				while ($row = $res->fetch_assoc()) {
					$actions[] = $row;
				}
			}
		}

		return $actions;
	}

	public function UpdateAction($data) {
		if ($data->task == $this->id and !empty($data->id)) {			
			// Get current user
			$user = Globals::getInstance('session')->get_key('auth_user');

			// Build SQL statement
			$sql = 'UPDATE actions SET ';
			$sql .= 'status = "' . $this->db->real_escape_string($data->status) . '", ';
			$sql .= 'issue = "' . $this->db->real_escape_string($data->issue) . '", ';
			$sql .= 'reaped = "' . $this->db->real_escape_string($data->reaped) . '", ';
			$sql .= 'notes = "' . $this->db->real_escape_string($data->notes) . '", ';
			$sql .= 'timestamp = UTC_TIMESTAMP() ';
			$sql .= 'WHERE task = "' . $this->db->real_escape_string($data->task) . '" AND id = "' . $this->db->real_escape_string($data->id) . '" AND user = "' . $this->db->real_escape_string($user) . '"';

			$sql2 = 'UPDATE tasks t ';
			$sql2 .= 'INNER JOIN actions a ON (t.id = a.task) ';
			$sql2 .= 'LEFT JOIN actions b ON (a.task = b.task AND a.id < b.id AND b.status <> "note") ';
			$sql2 .= 'LEFT JOIN actions i on (a.task = i.task AND i.issue <> "") ';
			$sql2 .= 'SET t.status = a.status, t.issue = i.issue, t.user = a.user, t.timestamp = a.timestamp, t.active = (CASE WHEN a.status = "good" THEN 0 ELSE 1 END) ';
			$sql2 .= 'WHERE t.id = "' . $this->db->real_escape_string($this->id) . '" AND b.id IS NULL AND a.status <> "note" ';
			$sql2 .= 'AND (i.id IS NULL OR i.id = (SELECT MAX(j.id) FROM actions j WHERE j.task = a.task AND j.issue <> ""))';

			// Execute SQL
			$this->db->begin_transaction();
			$this->db->query($sql);
			$this->db->query($sql2);
			$this->db->end_transaction();

			return true;
		}

		return false;
	}

	public function SaveImage($encoding, $data) {
		// De-code data
		if ($encoding == 'base64') {
			$data = base64_decode($data);
		}

		// Load image
		$im = imagecreatefromstring($data);

		if ($im !== false) {
			// Get dimensions of image
			$w = imagesx($im);
			$h = imagesy($im);

			// Create new image with black background
			$im2 = imagecreatetruecolor($w, $h);
			$bk = imagecolorallocate($im2, 35, 35, 35);
			imagefill($im2, 0, 0, $bk);

			// Copy image data to base image
			//imagecopymerge($im2, $im, 0, 0, 0, 0, $w, $h, 99);
			imagecopy($im2, $im, 0, 0, 0, 0, $w, $h);

			// Get image file path
			$img = self::GetNewImagePath($this->cell, $this->id);
			
			if ($img !== false) {
				// Save image file
				imagepng($im2, $img->file);
	
				// Return URL to file
				return $img->url;
			} else {
				return false;
			}
		}

		return false;
	}

	
	/*
	 * Public Static Methods (for new task entries)
	 */
	
	public static function ProcessNewImage($cell, $task, $encoding, $data) {
		// De-code data
		if ($encoding == 'base64') {
			$data = base64_decode($data);
		}

		// Load image
		$im = imagecreatefromstring($data);

		if ($im !== false) {
			// Get dimensions of image
			$w = imagesx($im);
			$h = imagesy($im);

			// Create new image with black background
			$im2 = imagecreatetruecolor($w, $h);
			$bk = imagecolorallocate($im2, 35, 35, 35);
			imagefill($im2, 0, 0, $bk);

			// Copy image data to base image
			//imagecopymerge($im2, $im, 0, 0, 0, 0, $w, $h, 99);
			imagecopy($im2, $im, 0, 0, 0, 0, $w, $h);


			// Get image file path
			$img = self::GetNewImagePath($cell, $task);
			
			if ($img !== false) {
				// Save image file
				imagepng($im2, $img->file);
	
				// Return URL to file
				return $img->url;
			} else {
				return false;
			}
		}

		return false;
	}
	
	public static function GetNewImagePath($cell, $task) {
		// Get config instance
		$cfg = Globals::getInstance('config');
		
		// Determine base path
		//$path = $cfg['image_repository'] . '/' . $cell . '/' . $task . '/';
		$path = $cfg['image_repository'];

		
		if (!empty($path)) {
			// Check for cell directory
			//if (file_exists($cfg['image_repository'] . '/' . $cell) === false) {
			//	chdir($cfg['image_repository']);
			//	mkdir($cell);
			//}
			
			// Check for task directory
			//if (file_exists($path) === false) {
			//	chdir($cfg['image_repository'] . '/' . $cell);
			//	mkdir($task);
			//}
	
			// Generate unique file name
			$_name = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
			$res = false;
			
			while (!$res) {
				//$id = base64_encode(mt_rand() . mt_rand() . mt_rand() . mt_rand() . mt_rand());
				//$file = substr($id, 3, 7) . '.png';

				$file = '';

				for ($n=0; $n < 8; $n++) {
					$file .= $_name[mt_rand(0, 63)];
				}

				$file .= '.png';

			
				if (file_exists($path . $file) === false) {
					$res = true;
				}
			}
			
			// Generate response
			$obj = new stdClass();
			$obj->path = $path;
			$obj->file = $path . $file;
			$obj->filename = $file;
			//$obj->url = $cfg['image_repository_url'] . '/' . $cell . '/' . $task . '/' . $file;
			$obj->url = $cfg['image_repository_url'] . $file;

			
			// Return response
			return $obj;
		} else {
			// No image repository specified
			
			return false;
		}
	}
	
	

	/*
	 * Properties
	 */

	public function Id() {
		return $this->id;
	}

	public function Cell() {
		return $this->cell;
	}

	public function CellName() {
		return $this->cellName;
	}
	
	public function CellNameKO() {
		return $this->cellNameKO;
	}

	public function IsMismatched() {
		return $this->mismatched;
	}

	public function Issue() {
		return $this->issue;
	}
	
	public function Status() {
		return $this->status;
	}

	public function StatusText() {
		return $this->statusText;
	}

	public function LastUser() {
		return $this->lastUser;
	}

	public function LastUpdated() {
		return $this->lastUpdated;
	}


	public function Actions() {
		return $this->actions;
	}

}
