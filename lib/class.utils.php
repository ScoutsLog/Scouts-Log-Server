<?php

class Utils {

	public static function getActiveCells() {
		$db = Globals::getInstance('database');

		$res = $db->query('SELECT id AS cell, name AS cellName, difficulty FROM cells WHERE active = 1 ORDER BY difficulty, name');

		$cells = array();

		if ($res !== false) {
			while ($row = $res->fetch_assoc()) {
				$cells[ $row['cell'] ] = $row;
			}
		}

		return $cells;
	}

	public static function getActiveCellList() {
		$db = Globals::getInstance('database');

		$res = $db->query('SELECT id FROM cells WHERE active = 1 ORDER BY difficulty, name');

		$cells = array();

		if ($res !== false) {
			while ($row = $res->fetch_row()) {
				$cells[] = $row[0];
			}
		}

		return $cells;
	}

	public static function getStatusList() {
		$res = Globals::getInstance('database')->query('SELECT status, text FROM status ORDER BY sequence');
		
		$status = array();
		
		if ($res !== false) {
			while ($row = $res->fetch_assoc()) {
				$status[] = $row;
			}
		}
		
		return $status;		
	}
	
	
	public static function nice_number($n) {
		// first strip any formatting
		$n = preg_replace('/[^0-9\.]+/', '', $n);

		// is this a number?
		if (!is_numeric($n)) return false;

		// now filter it
		if ($n >= 1000000000000) {
			return number_format(round(($n/1000000000000), 2), 1) . 'T';
		} elseif ($n >= 1000000000) {
			return number_format(round(($n/1000000000), 2), 1) . 'B';
		} elseif ($n >= 1000000) {
			return number_format(round(($n/1000000), 2), 1) . 'M';
		} elseif ($n >= 1000) {
			return number_format(round(($n/1000), 2), 1) . 'K';
		}

		return number_format($n, 1);
	}

	public static function isValidStatus($status) {
		$valid = array(
			'good',
			'missing-nub',
			'missing-branch',
			'merger',
			'need-admin',
			'need-scythe',
			'note',
			'scythe-complete',
			'still-growing',
			'subtree-complete',
			'watch',
			'all'
		);
		
		$status = strtolower($status);
		
		return in_array($status, $valid);		
	}
	
	public static function generateSSOToken($user, $omni, $auth) {
		// Get database object
		$db = Globals::getInstance('database');

		// Generate a unique token
		$id = hash('ripemd256', base64_encode(mt_rand() . microtime() . mt_rand()));

		// Save token to database
		$sql = 'INSERT INTO sso (id, user, roles, omni, auth) VALUES(';
		$sql .= '"' . $db->real_escape_string($id) . '", ';
		$sql .= '"' . $db->real_escape_string(implode(',', $user->roles)) . '", ';
		$sql .= '"' . $db->real_escape_string($omni) . '", ';
		$sql .= '"' . $db->real_escape_string($auth) . '")';

		$db->query($sql);

		return $id;
	}

	public static function redeemSSOToken($token) {
		// Get objects
		$db = Globals::getInstance('database');
		$session = Globals::getInstance('session');

		// Retrieve and delete token data
		$db->begin_transaction();
		$res = $db->query('SELECT * FROM sso WHERE id = "' . $db->real_escape_string($token) . '"');
		$db->query('DELETE FROM sso WHERE id = "' . $db->real_escape_string($token) . '"');
		$db->end_transaction();

		// Check if we received a valid token
		if ($res !== false) {
			// Get token details
			$info = $res->fetch_assoc();

			// Update current session
			$session->set_key('auth_user', $info['user']);
			$session->set_key('auth_token', $info['auth']);
			$session->set_key('auth_roles', explode(',', $info['roles']));
			$session->set_key('omni_session', $info['omni']);

			return true;
		}

		return false;
	}

}

