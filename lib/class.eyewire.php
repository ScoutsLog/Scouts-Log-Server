<?php

require_once 'class.eyewire.cell.php';
require_once 'class.eyewire.cell_mystic.php';
require_once 'class.eyewire.rating.php';
require_once 'class.eyewire.stats.php';
require_once 'class.eyewire.status.php';
require_once 'class.eyewire.task.php';
require_once 'class.eyewire.user.php';

class EyeWire {
	protected static $url_base = 'https://eyewire.org/';
	
	

	protected static $active_cells = array();


	private static function get_url() {
		// Set result
		$url = self::$url_base;

		// Check for origin value
		$_origin = Globals::getInstance('session')->get_key('origin');

		if (!empty($_origin)) {
			$url = $_origin . '/';
		}
	}
		
	
	public static function get($url, $headers=array()) {
		$ch = curl_init();
		
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, true);
		
		if (!empty($headers)) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		}
		
		$response = curl_exec($ch);
		
		curl_close($ch);

		// Process response
		$obj = new stdClass();

		list($_head, $_body) = explode("\r\n\r\n", $response, 2);
		$lines = preg_split('/(\r\n|\n)/', $_head);

		$obj->status = array_shift($lines);
		$obj->headers = array();
		$obj->charset = $charset;
		$obj->body = $_body;

		foreach ($lines as $_line) {
			$obj->headers[] = $_line;
		}

		return $obj;
	}
	
	public static function post($url, $post_data, $headers=array()) {
		$ch = curl_init();
		
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		curl_setopt($ch, CURLOPT_HEADER, true);
		
		if (!empty($headers)) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		}
		
		$response = curl_exec($ch);

		curl_close($ch);
				
		// Process response
		$obj = new stdClass();

		list($_head, $_body) = explode("\r\n\r\n", $response, 2);
		$lines = preg_split('/(\r\n|\n)/', $_head);

		$obj->status = array_shift($lines);
		$obj->headers = array();
		$obj->body = $_body;	
		
		foreach ($lines as $_line) {
			$obj->headers[] = $_line;
		}

		return $obj;
	}

	
	
	public static function ActiveCells($all_cells=false, $lang='en-US,en', $force=false) {
		if (empty(self::$active_cells) or $force === true) {
			// Request active cell list from EyeWire API
			$url = self::$url_base . '1.0/cell?dataset=1';
			//$url = self::get_url() . '1.0/cell?dataset=1';

			$headers = array();
			$headers[] = 'Accept-Language: ' . $lang;
			$response = self::get($url, $headers);
		
			// Process response
			$cell_data = json_decode($response->body);
			$cells = array();

			if (!empty($cell_data)) {
				foreach ($cell_data as $c) {
					if (empty($c->completed) or $all_cells === true) {
						$cells[] = array(
							'cell' => $c->cell,
							'name' => $c->name,
							'difficulty' => $c->difficulty,
							'active' => empty($c->completed) ? 1 : 0
						);
					}
				}
			

				// Sort cell list by difficulty and then name
				$s = new php_sorter($cells, array( array('difficulty', 'ASC', 'NUMERIC'), array('name', 'ASC', 'ALPHA') ));

				self::$active_cells = $s->sort();
			}
		}

		// Return active cell list
		return self::$active_cells;
	}
	
	public static function AuthenticateExchange($auth_code) {
		$cfg = Globals::getInstance('config');

		$url = 'https://tasking.eyewire.org/oauth2/1.0/exchange';

		$data = 'code=' . urlencode($auth_code);
		$data .= '&client_secret=' . urlencode($cfg['eyewire_secret']);
		$data .= '&redirect_uri=' . urlencode($cfg['eyewire_redirect']);
		$data .= '&client_id=' . urlencode($cfg['eyewire_client_id']);
		$data .= '&grant_type=authorization_code';
		
		// Send request to server
		$response = self::post($url, $data);

		// Return authentication exchange response
		return $response;
	}

	public static function CellList($all_cells=false, $lang='en-US,en', $dataset=1) {
		// Request active cell list from EyeWire API
		$url = self::$url_base . '1.0/cell?dataset=' . $dataset;
		//$url = self::get_url() . '1.0/cell?dataset=1';

		$headers = array();
		$headers[] = 'Accept-Language: ' . $lang;
		$response = self::get($url, $headers);

		// Process response
		$cell_data = json_decode($response->body);
		$cells = array();

		foreach ($cell_data as $c) {
			if (empty($c->completed) or $all_cells === true) {
				if ($lang == 'ko') {
					$c->name = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($match) {
						return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
					}, $c->name);
				}

				$cells[] = array(
					'cell' => $c->cell,
					'name' => $c->name,
					'difficulty' => $c->difficulty,
					'active' => empty($c->completed) ? 1 : 0,
					'tags' => $c->tags
				);
			}
		}

		// Sort cell list by difficulty and then name
		$s = new php_sorter($cells, array( array('difficulty', 'ASC', 'NUMERIC'), array('name', 'ASC', 'ALPHA') ));

		$list = $s->sort();

		// Return active cell list
		return $list;
	}

	public static function TaskDescendants($task) {
		$url = self::$url_base . '1.0/task/' . $task . '/hierarchy';
		//$url = self::get_url() . '1.0/task/' . $task . '/hierarchy';

		$response = self::get($url);

		// Process response
		$task_data = json_decode($response->body);

		// Return descendants
		return $task_data->descendants;
	}
	
	public static function UserDetails() {
		$url = 'https://eyewire.org/2.0/account';
		//$url = self::get_url() . '2.0/account';

		$url .= '?access_token=' . urlencode(Globals::getInstance('session')->get_key('auth_token'));
		
		// Send request to server
		$response = self::get($url);

		// Process response
		$obj = json_decode($response->body);

		// Return user details
		return $obj;
	}


	public static function UserBio($username) {
		// Request cell details from EyeWire API
		$url = 'https://eyewire.org/1.0/player/' . rawurlencode($username) . '/bio';
		//$url = self::get_url() . '1.0/player/' . rawurlencode($username) . '/bio';

		$response = self::get($url);
		
		// Process response
		$obj = json_decode($response->body);
		
		// Return user bio
		return $obj;
	}
	
}
