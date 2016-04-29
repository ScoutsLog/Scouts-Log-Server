<?php

class php_database {
	private $host;
	private $username;
	private $password;
	private $database;	
	
	private $mysqli;
	
	
	public function __construct($host, $username, $password, $database) {
		$this->host = $host;
		$this->username = $username;
		$this->password = $password;
		$this->database = $database;
	}
	
	
	public function connect() {
		$this->mysqli = new mysqli(
			$this->host, 
			$this->username,
			$this->password, 
			$this->database
		);
		
		if ($this->mysqli->connect_errno) {
			// Error connecting to database
			
			return false;
		}
		
		return true;
	}
	
	public function disconnect() {
		$this->mysqli->close();
	}
	
	
	public function query($sql, $resultmode=MYSQLI_STORE_RESULT) {
		return $this->mysqli->query($sql, $resultmode);
	}
	
	public function real_escape_string($input) {
		return $this->mysqli->real_escape_string($input);
	}


	public function begin_transaction() {
		$this->mysqli->autocommit(false);
	}
	
	public function rollback_transaction() {
		$this->mysqli->rollback();
		$this->mysqli->autocommit(true);
	}
	
	public function end_transaction() {
		$this->mysqli->commit();
		$this->mysqli->autocommit(true);
	}
	
	
	public function error() {
		return $this->mysqli->error;
	}
}

