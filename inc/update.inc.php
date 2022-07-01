<?php

class Update {
	static function perform() {
		$db = new DB();

		if (!$db->query('SELECT COUNT(*) FROM parkingbans')) {
			self::to_1();
		}

		if (!$db->query('SELECT COUNT(*) FROM alerts')) {
			self::to_2();
		}

		if (!$db->query('SELECT lang FROM alerts')) {
			self::to_3();
		}

		echo "All done.\n";
	}

	static function to_3() {
		echo "3. Add lang to table `alerts`\n";

		$db = new DB();
		$db->query('ALTER TABLE alerts ADD lang TEXT');
		if ($db->error()) {
			print $db->error()."\n";
		}
	}

	static function to_2() {
		echo "2. Create table `alerts`\n";

		$db = new DB();
		$db->query(
			'CREATE TABLE alerts (
				id INTEGER PRIMARY KEY,
				unique_id TEXT,
				start INTEGER,
				stop INTEGER,
				longitude REAL,
				latitude REAL,
				distance INTEGER,
				contact_email TEXT,
				last_email INTEGER,
				cancelled INTEGER,
				updated INTEGER,
				created INTEGER
			);'
		);

		if ($db->error()) {
			print $db->error()."\n";
		}
	}

	static function to_1() {
		echo "1. Create table `parkingbans`\n";

		$db = new DB();
		$db->query(
			'CREATE TABLE parkingbans (
				id INTEGER PRIMARY KEY,
				reference TEXT,
				location_id TEXT,
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
			);'
		);

		if ($db->error()) {
			print $db->error()."\n";
		}
	}
}
