<?php

class Imgur_Uploader {
	private $client_id;

	private static $uri = 'https://api.imgur.com/3/image';

	private $buffer;


	public function __construct() {
		// Load configuration
		$cfg = Globals::getInstance('config');

		$this->client_id = $cfg['imgur_client_id'];
		$this->client_secret = $cfg['imgur_client_secret'];

		// Initialize variables
		$this->ClearSendBuffer();
		$this->response = array();
	}



	public function AddBase64File($data) {
		$this->buffer = array(
			'image' => $data,
			'type' => 'base64'
		);
	}

	public function AddDiskFile($filename) {
		$this->buffer = array(
			'image' => $filename,
			'type' => 'file'
		);
	}

	public function AddURLFile($url) {
		$this->buffer = array(
			'image' => $url,
			'type' => 'URL'
		);
	}

	public function ClearSendBuffer() {
		$this->buffer = array();
	}

	public function Send() {
		// Create cURL object
		$ch = curl_init();

		// Build HTTP header
		$headers = array(
			'Authorization: Client-ID ' . $this->client_id
		);

		// Build HTTP data
		$post_data = $this->buffer;

		if ($this->buffer['type'] == 'file') {
			$image_file = file_get_contents($this->buffer['image']);
			$post_data['image'] = base64_encode($image_file);
		}

		// Set cURL options
		curl_setopt($ch, CURLOPT_URL, self::$uri);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

		// Send request
		$response = curl_exec($ch);

		curl_close($ch);

		// Process response
		$obj = new stdClass();
		$obj->status = '';
		$obj->headers = array();
		$obj->body = '';

		$lines = preg_split('/(\r\n|\n)/', $response);
		$tl = count($lines);
		$hd = true;
		$hd_count = 0;

		for ($n=0; $n < $tl; $n++) {
			if (substr($lines[n], 0, 12) == 'HTTP/1.1 100') {
				// skip, jump ahead 3 lines

				$n += 3;
			} else if (substr($lines[$n], 0, 8) == 'HTTP/1.1') {
				// Get status

				$obj->status = $lines[$n];
			} else if ($lines[$n] == "") {
				$hd_count++;

				if ($hd_count >= 2) {
					$hd = false;
				}
			} else {
				if ($hd === true) {
					$obj->headers[] = $lines[$n];
				} else {
					$obj->body .= $lines[$n];
				}
			}
		}

		// Return response
		return $obj;
	}



	public function Buffer() {
		return $this->buffer;
	}

	public function IsReady() {
		return !empty($this->buffer);
	}

}
