<?php

$lon = (float)$_REQUEST['lon'];
$lat = (float)$_REQUEST['lat'];

$now = time();

$bans = Parking_Ban::select_by_distance($lon, $lat, 100);
$data = [];

foreach ($bans as $ban) {
	if ($ban->is_active($now)) {
		$data[$ban->id] = [
			'reference' => $ban->reference,
			'location_id' => $ban->location_id,
			'type' => $ban->type,
			'address' => $ban->address,
			'start' => $ban->start,
			'stop' => $ban->stop,
			'from_hour' => $ban->from_hour,
			'to_hour' => $ban->to_hour,
			'reason' => $ban->reason,
			'weekdays_only' => $ban->weekdays_only,
			'url' => $ban->url,
			'geojson' => json_decode($ban->geojson),
		];
	}
}

echo json_encode($data);
