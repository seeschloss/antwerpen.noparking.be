<?php

require __dir__.'/../inc/common.inc.php';

$now = time();
$alerts = Alert::select(['cancelled' => 0]);
foreach ($alerts as $alert) {
	if ($alert->stop > $now) {
		$alert->send_alert();
	}
}
