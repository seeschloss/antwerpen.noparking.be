<?php // vim: ft=html:et:sw=2:sts=2:ts=2

$path = explode('/', $_SERVER['REQUEST_URI']);

$alert_unique_id = array_pop($path);

$alert = new Alert();
$alert->load(['unique_id' => $alert_unique_id]);

if (isset($_POST['cancel']) and $_POST['cancel']) {
	$alert->cancelled = 1;
	$alert->save();
}

print $alert->details_html();

if ($alert->cancelled) {
	print "<p>This alert has been cancelled. You can always create a new one.</p>";
} else {
	print $alert->cancel_form();
}
