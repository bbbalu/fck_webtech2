<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<?php
	/*if (isset($_GET['ajax'])) {

		if ($_GET['ajax'] == 'getSchools') $result = $db->...

		die(json_encode($result));
	}*/
	$schools = array();
	$addresses = array();

	$users = $db->run('users');
	foreach ($users->result() as $user) {

		if (isset($schools[md5($user->school_address)])) $schools[md5($user->school_address)]['num']++;
		else $schools[md5($user->school_address)] = array("id" => $user->id, "name" => $user->school, "address" => $user->school_address, "num" => 1);

		$sp_address = $user->city.', '.$user->address.', '.$user->zip_code;
		if (isset($addresses[md5($sp_address)])) $addresses[md5($sp_address)]['num']++;
		else $addresses[] = array("id" => $user->id, "name" => $sp_address,/*$user->firstname.' '.$user->lastname,*/ "address" => $sp_address, "num" => 1);
	}

	?>

	<main class="container">
		<section>



			<!-- Login page -->
			<h1 class="page_title">Domovská stránka</h1>

			<a class="button_a" id="showSchools" onclick="showArrayOnMap(schools);">Ukaž školy</a>
			<a class="button_a" id="showAddresses" onclick="showArrayOnMap(addresses);">Ukaž adresy</a>

			<p>Na mape sú zobrazené <span id="showingType">školy</span> používateľov</p>

			<p id="loading" style="color: #F00;">Načítávanie dát...</p>

			<div id="map"></div>


			<script async src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAsMZqqT3Azn1HQF5tk8pG6BYW9Qp4s6LY&callback=initMap"></script>

			<script type="text/javascript">

				<?php echo "var schools = ". json_encode(array_values($schools)) . ";\n"; ?>
				<?php echo "var addresses = ". json_encode(array_values($addresses)) . ";\n"; ?>

				var map;
				var bounds;
				var markers;
				var infoWindow;
				var infoWindowOpeners;
				var geocoder;

				function initMap() {
					map = new google.maps.Map(document.getElementById("map"), {
			          zoom: 7,
			          center: {lat: 48.7685, lng: 19.4807},
			          mapTypeId: 'terrain'
			        });
					bounds = new google.maps.LatLngBounds();
					infoWindow = new google.maps.InfoWindow();
					markers = [];
					infoWindowOpeners = {};
					geocoder = new google.maps.Geocoder();

					showArrayOnMap(schools);
				}


				function removeAllMarkers() {
					bounds = new google.maps.LatLngBounds();

					for (var i = 0; i < markers.length; i++) {
						markers[i].setMap(null);
					}
				}




				var drawArray = [];
				var drawIndex = 0;

				function showArrayOnMap(arrIn) {
					document.getElementById('loading').style.display = "block";
					removeAllMarkers();

					if (arrIn.length > 0) {
						drawArray = arrIn;
						drawIndex = 0;
					}

					drawOutWithDelay();
				}

				function drawOutWithDelay() {
					var val = drawArray[drawIndex];

					geocoder.geocode({'address': drawArray[drawIndex].address}, (function(index) {
						return function(results, status) {
							if (status === 'OK') {
								val.location = results[0].geometry.location;
								drawOut(val)
							}
						};
					})(val));

					if (drawArray[++drawIndex]) {
						setTimeout(drawOutWithDelay, 100);
					} else {
						// Automatically center the map fitting all markers on the screen
						map.fitBounds(bounds);
						map.panToBounds(bounds);
						document.getElementById('loading').style.display = "none";
					}
				}

				function drawOut(pointData) {
				// Display multiple markers on a map
				var infoWindow = new google.maps.InfoWindow(), marker, i;

				// Loop through our array of markers & place each one on the map
				bounds.extend(pointData.location);
				marker = new google.maps.Marker({
					position: pointData.location,
					map: map,
					title: pointData.name
				});

				infoWindowOpeners[pointData.id] = null;

				// Allow each marker to have info window
				google.maps.event.addListener(marker, 'click', (function(marker, eid) {
					return infoWindowOpeners[pointData.id] = function() {
						infoWindow.setContent('<strong>' + pointData.name + ' (' + pointData.num + 'ks)</strong>');
						infoWindow.open(map, marker);
					}
				})(marker));

				markers.push(marker);
			}
			
		</script>

	</section>
</main>