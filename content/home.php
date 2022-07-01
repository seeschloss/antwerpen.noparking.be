<?php // vim: ft=html:et:sw=2:sts=2:ts=2

if (!empty($_POST['longitude']) and !empty($_POST['latitude']) and !empty($_POST['contact_email'])) {
	if (strpos($_POST['contact_email'], '@') !== false) {
		$alert = new Alert();
		$alert->generate_unique_id();
		$alert->longitude = $_POST['longitude'];
		$alert->latitude = $_POST['latitude'];
		$alert->contact_email = $_POST['contact_email'];
		$alert->start = time();
		$alert->stop = strtotime("+7 days");
		$alert->lang = Lang::determine_lang();
		$alert->save();
		$alert->send_confirmation();
	}
}

?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.5.1/dist/leaflet.css"
   integrity="sha512-xwE/Az9zrjBIphAcBb3F6JVqxf46+CDLwfLMHloNu6KEQCAWi6HcDUbeOfBIptF7tcCzusKFjFw2yuvEpDL9wQ=="
   crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.5.1/dist/leaflet.js"
   integrity="sha512-GffPMF3RvMeYyc1LWMHtK8EbPv0iNZ8/oTtHPx9/cc2ILxQ+u905qIwdpULaqDkyBKgOaB57QTMg7ztg8Jm2Og=="
   crossorigin=""></script>

<style>
	body { margin: 0; }
	#map { height: 100%; }
</style>

<div id="map"></div>

<script>
var map = L.map('map').setView([51.215, 4.404], 13);

var tile = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors, <a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>',
    maxZoom: 19,
    id: 'osm'
}).addTo(map);

if (Geolocation = navigator.geolocation) {
  Geolocation.getCurrentPosition(function(position) {
      map.setView([position.coords.latitude, position.coords.longitude], 18);
  });
}

map.on('click', function(e) {
  if (e.originalEvent.target == map.getContainer()) {
    showAlertForm(e.latlng.lat, e.latlng.lng);
  }
});

map.on('zoomend', function() {
    bansLayer.setStyle({
      weight: Math.pow(map.getZoom()/13, 8)
    });
});

var popup;
var bansLayer = L.geoJSON([], {
  style: function(feature) {
    return {
      weight: Math.pow(map.getZoom()/13, 8),
      lineCap: 'butt',
      lineJoin: 'round'
    };
  },
  onEachFeature: function(feature, layer) {
    layer.on('click', function(e) {
        showInfo(e.latlng.lat, e.latlng.lng, feature);
        return false;
    });
  }
}).addTo(map);

var bans = <?php echo json_encode(Parking_Ban::select_active(time())); ?>;
for (var id in bans) {
  var ban = bans[id];
  bansLayer.addData(JSON.parse(ban.geojson));
}

function showInfo(lat, lon, feature) {
  console.log(feature);
			var html = 
				'<dl>' +
				' <dt><?php __js("Address") ?></dt>' +
				' <dd>' + feature.properties.address + '</dd>' +
				' <dt><?php __js("Reference number") ?></dt>' +
				' <dd>' + feature.properties.referenceId + '</dd>' +
				' <dt><?php __js("Period") ?></dt>' +
				' <dd>' + feature.properties.dateFrom + ' - ' + feature.properties.dateUntil +
        '<br />' + (feature.properties.entireDay ? ('<?php __js("whole day") ?>') : (feature.properties.timeFrom + ' - ' + feature.properties.timeUntil)) +
        (feature.properties.onlyOnWeekdays ? '<br /><?php __js("only on weekdays") ?>' : '') +
        ' </dd>' +
				' <dt><?php __js("Reason") ?></dt>' +
				' <dd>' + feature.properties.reason.name + '</dd>' +
				' <dt><?php __js("Link") ?></dt>' +
				' <dd><a target="_blank" href="https://parkeerverbod.info' + feature.properties.url + '">https://parkeerverbod.info' + feature.properties.url + '</a></dd>' +
				'</dl>'
				;

				popup = L.popup({minWidth: 300, maxWidth: 600})
					.setLatLng({lat: lat, lng: lon})
					.setContent(
						'<p>' + html + '</p>'
					)
					.openOn(map);
}


function showAlertForm(lat, lon) {
			var alert_form = 
				'<form method="POST" action="">' +
				' <input type="hidden" name="longitude" value="' + lon + '" />' +
				' <input type="hidden" name="latitude" value="' + lat + '" />' +
				' <p><nobr>' +
				'  <input type="email" name="contact_email" placeholder="<?php __js("Email address") ?>" />' +
				'  <button><?php __js("Set an alert") ?></button>' +
				' </nobr></p><p>' +
				'  <span><?php __js("You will receive a confirmation email with a link that allows you to cancel this alert. If you do not cancel it, the alert will be active for one week.") ?></span>' +
				' </p>' +
				'</form>'
				;

				popup = L.popup()
					.setLatLng({lat: lat, lng: lon})
					.setContent(
						'<p>' + alert_form + '</p>'
					)
					.openOn(map);
}

</script>
