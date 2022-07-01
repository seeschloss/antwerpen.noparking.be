<?php

require __DIR__.'/../inc/common.inc.php';

$url_pattern = $GLOBALS['config']['data_url'];
$url = str_replace("%date", date("Y-m-d"), $url_pattern);
$http = new HTTP($url);
$json = $http->get();

$data = json_decode($json, true);

if ($data) {
	$location_ids = [];
	foreach ($data['features'] as $feature) {
		$location_ids[$feature['properties']['locationId']] = new Parking_Ban();
	}

	$existing_bans = Parking_Ban::select(['location_id' => array_keys($location_ids)]);
	foreach ($existing_bans as $existing_ban) {
		$location_ids[$existing_ban->location_id] = $existing_ban;
	}

	foreach ($data['features'] as $feature) {
		$ban = $location_ids[$feature['properties']['locationId']];

		if ($ban->id) {
			// let's do nothing
		} else {
			$ban->fill_from_json($feature);
			$ban->save();
		}
	}
}
