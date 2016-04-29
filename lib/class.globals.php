<?php

class Globals {
	private static $instanceArray;
	
	
	public static function getInstance($key) {
		return self::$instanceArray[$key];
	}
	
	public static function setInstance($key, $object) {
		self::$instanceArray[$key] = $object;
	}
	
}

