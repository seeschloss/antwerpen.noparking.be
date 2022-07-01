<?php

class Parking_Ban extends Record {
	public static $table = "parkingbans";

	public $id = 0;
	public $reference = "";
	public $location_id = 0;
	public $type = "";
	public $address = "";
	public $start = 0;
	public $stop = 0;
	public $from_hour = 0;
	public $to_hour = 0;
	public $reason = "";
	public $reason_type = "";
	public $weekdays_only = 0;
	public $url = "";
	public $geojson = "";
	public $json = "";
	public $updated = 0;
	public $created = 0;
	
	public static function select_by_distance($lon, $lat, $max_distance) {
		$bans = Parking_Ban::select([]);
		$close_bans = [];

		foreach ($bans as $id => $ban) {
			$distance = $ban->distance($lon, $lat);
			if ($distance <= $max_distance) {
				$close_bans[$id] = $ban;
			}
		}

		return $close_bans;
	}
	
	public static function select_active($time) {
		$bans = Parking_Ban::select(['start < '.(int)$time, 'stop > '.(int)$time]);
		$active_bans = [];

		foreach ($bans as $id => $ban) {
			$active_bans[$id] = $ban;
		}

		return $active_bans;
	}

	function fill_from_json($data) {
		$this->reference = $data['properties']['referenceId'];
		$this->location_id = $data['properties']['locationId'];
		$this->type = $data['properties']['type'];
		$this->address = $data['properties']['address'];
		$this->start = strtotime($data['properties']['dateFrom']);
		$this->stop = strtotime($data['properties']['dateUntil']);

		list($hour, $minute) = explode(':', $data['properties']['timeFrom']);
		$this->from_hour = $hour * 3600 + $minute * 60;

		list($hour, $minute) = explode(':', $data['properties']['timeUntil']);
		$this->to_hour = $hour * 3600 + $minute * 60;

		$this->reason = $data['properties']['externalReason'];
		$this->reason_type = $data['properties']['reason']['reason'];

		if (!$this->reason or $this->reason == 'Er werd geen motivatie gevonden.') {
			$this->reason = $data['properties']['reason']['name'];
		}

		$this->weekdays_only = $data['properties']['onlyOnWeekdays'];
		$this->url = $data['properties']['url'];
		$this->geojson = json_encode($data);
		$this->json = json_encode($data);

		return $this->location_id > 0;
	}

	function save() {
		return $this->id > 0 ? $this->update() : $this->insert();
	}

	function insert() {
		$db = new DB();

		$fields = [
			'reference' => $db->escape($this->reference),
			'location_id' => (int)$this->location_id,
			'type' => $db->escape($this->type),
			'address' => $db->escape($this->address),
			'start' => (int)$this->start,
			'stop' => (int)$this->stop,
			'from_hour' => (int)$this->from_hour,
			'to_hour' => (int)$this->to_hour,
			'reason' => $db->escape($this->reason),
			'reason_type' => $db->escape($this->reason_type),
			'weekdays_only' => (int)$this->weekdays_only,
			'url' => $db->escape($this->url),
			'geojson' => $db->escape($this->geojson),
			'json' => $db->escape($this->json),
			'created' => time(),
			'updated' => time(),
		];

		$query = 'INSERT INTO '.self::$table.' (' . implode(',', array_keys($fields)) . ') '.
		                               'VALUES (' . implode(',', array_values($fields)) . ')';

		$db->query($query);

		$this->id = $db->insert_id();

		return $this->id;
	}

	function update() {
		$db = new DB();

		$fields = [
			'reference' => $db->escape($this->reference),
			'location_id' => (int)$this->location_id,
			'type' => $db->escape($this->type),
			'address' => $db->escape($this->address),
			'start' => (int)$this->start,
			'stop' => (int)$this->stop,
			'from_hour' => (int)$this->from_hour,
			'to_hour' => (int)$this->to_hour,
			'reason' => $db->escape($this->reason),
			'reason_type' => $db->escape($this->reason_type),
			'weekdays_only' => (int)$this->weekdays_only,
			'url' => $db->escape($this->url),
			'geojson' => $db->escape($this->geojson),
			'json' => $db->escape($this->json),
			'updated' => time(),
		];

		$query = 'UPDATE '.self::$table.' SET ' . implode(', ', array_map(function($k, $v) { return $k . '=' . $v; }, array_keys($fields), $fields)) .
		         ' WHERE id = '.(int)$this->id;

		$db->query($query);

		return $this->id;
	}

	function delete() {
		$db = new DB();

		$query = 'DELETE FROM '.self::$table.' WHERE id = '.(int)$this->id;

		$db->query($query);

		return true;
	}

	function postcode() {
		preg_match('/, ([0-9]{4}) /', $this->address, $matches);

		if ($matches and isset($matches[1])) {
			return $matches[1];
		}

		return "";
	}

	function street() {
		preg_match('/^([^,0-9]*) [0-9- ]*, ([0-9]{4}) /', $this->address, $matches);

		if ($matches and isset($matches[1])) {
			return $matches[1];
		}

		return "";
	}

	function number_range() {
		preg_match('/^([^,0-9]*) ([0-9- ]*), ([0-9]{4}) /', $this->address, $matches);

		if ($matches and isset($matches[2])) {
			$numbers = explode('-', $matches[2]);
			
			$number_min = (int)$numbers[0];
			$number_max = (int)$numbers[count($numbers) - 1];
			return [$number_min, $number_max];
		}

		return "";
	}

	function matches_address($postcode, $street, $number) {
		if ($this_postcode = $this->postcode() and $this_postcode != $postcode) {
			return false;
		}

		if ($this_street = $this->street()) {
			$street_a = mb_strtolower($this_street);
			$street_b = mb_strtolower($street);
			if ($street_a == $street_b) {
			} else if (strpos($street_a, $street_b) !== false) {
			} else if (strpos($street_b, $street_a) !== false) {
			} else {
				return false;
			}
		}

		list($number_min, $number_max) = $this->number_range();
		if ($number_min and $number_max and $number < $number_min or $number > $number_max) {
			return false;
		} else if (!Math::odd($number) and Math::odd($number_min) and Math::odd($number_max)) {
			return false;
		} else if (Math::odd($number) and !Math::odd($number_min) and !Math::odd($number_max)) {
			return false;
		}

		return true;
	}

	function distance($lon, $lat) {
		require_once __dir__.'/../lib/geoPHP/geoPHP.inc';

		$target = geoPHP::load("POINT({$lon} {$lat})", 'wkt');

		$geometry = geoPHP::load($this->geojson, 'json');
		
		if ($geometry->contains($target)) {
			return -1;
		}

		$degrees = $geometry->distance($target);
		$meters = $degrees * 111139;

		return $meters;
	}

	function is_active($date) {
		if ($date >= $this->start and $date <= $this->stop) {
			return true;
		}
	}

	function details_text() {
		$start_date = gmdate('d/m/Y', $this->start);
		$stop_date = gmdate('d/m/Y', $this->stop);

		$hours = gmdate('H:i', $this->from_hour).' - '.gmdate('H:i', $this->to_hour);

		$url = "https://parkeerverbod.info{$this->url}";

		return <<<EOT
{$this->__("Address: %s", $this->address)}
{$this->__("From: %s", $start_date)}
{$this->__("To: %s", $stop_date)}
{$this->__("Hours: %s", $hours)}
{$this->__("Reason: %s", $this->reason)}
{$this->__("Link: %s", $url)}
EOT;
	}
}
