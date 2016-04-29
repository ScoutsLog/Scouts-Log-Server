<?php

class ScoutsLog_Logger {

	private static $fh_log = null;


	public static function LogEntry($action, $result) {
		// Check file handle
		if (is_null(self::$fh_log)) {
			error_log('ERROR: unable to create log entry; log file not opened.');

			return false;
		}

		// Generate log entry
		$entry = sprintf(
			"[%s] session: %s; user: %s; action: %s; result: %s\r\n",
			date('Y-m-d H:i:s'),
			Globals::getInstance('session')->Id(),
			Globals::getInstance('session')->get_key('auth_user'),
			$action,
			$result
		);

		// Write log entry
		flock(self::$fh_log, LOCK_EX);
		fwrite(self::$fh_log, $entry);
		flock(self::$fh_log, LOCK_UN);
		
		// Return result
		return true;
	}

	public static function Start() {
		if (is_null(self::$fh_log)) {
			// Get config instance
			$cfg = Globals::getInstance('config');

			// Determine log file name
			$log_file = $cfg['log_file'] . 'scoutslog-' . date('Ymd') . '.log';

			// Open file handle
			self::$fh_log = fopen($log_file, 'a');
		}
	}

	public static function Stop() {
		// Close file handle
		@fclose(self::$fh_log);

		// Destroy file handle variable
		self::$fh_log = null;
	}

}
