<?php
class DB {
	static private $resource;

	function schema() {
		return [
			'CREATE TABLE alerts (
				id INTEGER PRIMARY KEY,
				unique_id TEXT,
				start INTEGER,
				stop INTEGER,
				longitude REAL,
				latitude REAL,
				distance INTEGER,
				contact_email TEXT,
				lang TEXT,
				last_email INTEGER,
				cancelled INTEGER,
				updated INTEGER,
				created INTEGER
			);',
			'CREATE TABLE parkingbans (
				id INTEGER PRIMARY KEY,
				reference TEXT,
				location_id INTEGER,
				type TEXT,
				address TEXT,
				start INTEGER,
				stop INTEGER,
				from_hour INTEGER,
				to_hour INTEGER,
				reason TEXT,
				reason_type TEXT,
				weekdays_only INTEGER,
				url TEXT,
				geojson TEXT,
				json TEXT,
				updated INTEGER,
				created INTEGER
			);',
		];
	}

	function init_schema() {
		foreach ($this->schema() as $table) {
			$this->query($table);
		}
	}

	function __construct() {
		if (!isset(self::$resource)) {
			self::$resource = new PDO($GLOBALS['config']['database']['dsn']);

			$result = $this->query("SELECT COUNT(*) FROM parkingbans");
			if (!$result) {
				$this->init_schema();
			}
		}
	}

	function error() {
		$error = self::$resource->errorInfo();

		return is_array($error) ? $error[2] : "";
	}

	function query($query) {
		$result = self::$resource->query($query);
		if (!$result) {
			$error = $this->error();
			if (class_exists('Logger')) {
				Logger::error($error);
				Logger::error("Query was: ".$query);
			}
			else {
				trigger_error($error);
				trigger_error("Query was: ".$query);
			}
		}
		return $result;
	}

	function value($query) {
		$result = $this->query($query);
		if ($result) while ($row = $result->fetch()) {
			return $row[0];
		}

		return '';
	}

	function escape($string) {
		return self::$resource->quote($string);
	}

	function insert_id() {
		return self::$resource->lastInsertId();
	}
}

