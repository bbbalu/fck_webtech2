<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<?php

	// This page can open only administrator
	if ($user_level != 2) header("Location ".BASEPATH);

	if (isset($_GET['activate'])) {
		$login->active_trip($_GET['activate']);

		$status = array('code' => '1', 'type' => "success", 'msg' => "Trasa bola úspešne aktivovaná!");

		$_SESSION['msg'] = $status;
		header("Location: ".BASEPATH);
		session_write_close();
		exit();
	}

	// Set user levels
	if (isset($_GET['new']) && isset($_POST['save'])) {
		$trip_data = json_decode($_POST['trip_code']);

		$type = ($user_level == 2) ? $_POST['type'] : 1;

		$db->insert('trips', array(
			'user_id' => $_SESSION['user_id'],
			'trip_name' => $_POST['name'],
			'type' => $type,
			'start' => $_POST['place_from'],
			'end' => $_POST['place_to'],
			'distance' => $trip_data->distance,
			'tripdata' => $_POST['trip_code']
		));

		$status = array('code' => '1', 'type' => "success", 'msg' => "Trasa bola úspešne uložená!");

		$_SESSION['msg'] = $status;
		header("Location: ".BASEPATH);
		session_write_close();
		exit();
	}

	if (isset($_GET['trip']))
	{
		$trip_data = $db->where('id =', $_GET['trip'])->run('trips');

	}


	if (!isset($_GET['trip']))
	{
		// Currently selected trip
		$active_trip = $login->get_my_active_trip();

		// Get private trips -> $private_trips
		$privt_query = $db->select('t.*, u.id AS user_id, u.firstname AS firstname, u.lastname AS lastname')->join('users AS u', 'u.id = t.user_id')->where('t.type =', 1);
		if ($user_level != 2) $privt_query = $privt_query->where('u.id', $_SESSION['user_id']);
		$privt_query = $privt_query->run('trips AS t');
		if ($privt_query->num_rows() > 0)
		{
			$private_trips = $privt_query->result();
		}

		// Get public trips -> $public_trips
		$pubt_query = $db->select('t.*, u.id AS user_id, u.firstname AS firstname, u.lastname AS lastname')->join('users AS u', 'u.id = t.user_id')->where('t.type =', 2)->run('trips AS t');
		if ($pubt_query->num_rows() > 0)
		{
			$public_trips = $pubt_query->result();
		}

		// Get team trips -> $team_trips
		if ($user_level == 1)
		{
			// Get team trips where i am assigned too
			$teamt_query = $db->select('te.*')->join('teams AS te', 'te.id = u2t.team_id')->where('u2t.user_id =', $_SESSION['user_id'])->run('user2team AS u2t');
			if ($teamt_query->num_rows())
			{
				$team_trips = array();
				foreach ($teamt_query->result() as $my_team) {
					$teamt_query = $db->join('trips AS t', 't.id = t2t.trip_id')->where('t2t.team_id =', $my_team->id)->run('team2trip AS t2t');
					if ($teamt_query->num_rows() > 0)
					{
						$team_trips = array_merge($team_trips, $teamt_query->result());
					}
				}
			}
		}
		else
		{
			$teamt_query = $db->where('t.type =', 3)->run('trips AS t');
			if ($teamt_query->num_rows() > 0)
			{
				$team_trips = $teamt_query->result();
			}
		}	
	}	


?>

<?php if (!isset($_GET['ajax'])): ?>
<main class="container">
	<section>
<?php endif; ?>

		<?php if (!isset($_GET['ajax'])): ?>

			<?php
				if (isset($_SESSION['msg'])) {
					$status = $_SESSION['msg'];
					unset($_SESSION['msg']);
				}
			?>

			<!-- Login page -->
			<h1 class="page_title">Trasy</h1>

			<?php echo (isset($status) && $status['code'] != 0) ? '<div class="note '.$status['type'].'" id="send_status">'.$status['msg'].'</div>' : ''; ?>

		<?php endif; ?>

		
		<?php if (isset($_GET['new'])): ?>

			<article class="page_content">
				<h2 style="margin-top: 0;">Vytvorenie trasy</h2>

				<form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">

					<div class="form_row">
						<label for="name">Názov trasy<span>*</span></label>
						<input type="text" name="name" id="name" required />
					</div>
					<div class="form_row">
						<label for="name">Typ trasy<span>*</span></label>
						<select name="type">
							<option value="1">Privátna trasa</option>
							<?php if($user_level == 2): ?>
								<option value="2">Verejna trasa</option>
								<option value="3">Štafetová trasa</option>
							<?php endif; ?>
						</select>
					</div>
					<div class="form_row">
						<label for="place_from">Začiatok<span>*</span></label>
						<input type="text" name="place_from" id="place_from" required />
					</div>
					<div class="form_row">
						<label for="place_to">Koniec<span>*</span></label>
						<input type="text" name="place_to" id="place_to" required />
					</div>

					<input type="hidden" name="trip_code" id="trip_code" />

					<div class="form_full">
						<input type="submit" name="save" value="Vytvoriť trasu" class="button_a">
					</div>
				</form>

				<div id="map"></div>

			</article>

			<script async src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAsMZqqT3Azn1HQF5tk8pG6BYW9Qp4s6LY&callback=initMap&libraries=places&language=sk"></script>

			<script type="text/javascript">

				var map;

				function initMap() {
					map = new google.maps.Map(document.getElementById("map"), {
						zoom: 7,
						center: {lat: 48.7685, lng: 19.4807},
						mapTypeControl: false
					});

					new AutocompleteDirectionsHandler(map);
				}


				function AutocompleteDirectionsHandler(map) {
					this.map = map;
					this.originPlaceId = null;
					this.destinationPlaceId = null;
					this.travelMode = 'WALKING';
					var originInput = document.getElementById('place_from');
					var destinationInput = document.getElementById('place_to');
					this.directionsService = new google.maps.DirectionsService;
					this.directionsDisplay = new google.maps.DirectionsRenderer;
					this.directionsDisplay.setMap(map);

					var originAutocomplete = new google.maps.places.Autocomplete(
						originInput, {placeIdOnly: true});
					var destinationAutocomplete = new google.maps.places.Autocomplete(
						destinationInput, {placeIdOnly: true});

					this.setupPlaceChangedListener(originAutocomplete, 'ORIG');
					this.setupPlaceChangedListener(destinationAutocomplete, 'DEST');
				}

				AutocompleteDirectionsHandler.prototype.setupPlaceChangedListener = function(autocomplete, mode) {
					var me = this;
					autocomplete.bindTo('bounds', this.map);
					autocomplete.addListener('place_changed', function() {
						var place = autocomplete.getPlace();
						if (!place.place_id) {
							window.alert("Please select an option from the dropdown list.");
							return;
						}
						if (mode === 'ORIG') {
							me.originPlaceId = place.place_id;
						} else {
							me.destinationPlaceId = place.place_id;
						}
						me.route();
					});

				};

				AutocompleteDirectionsHandler.prototype.route = function() {
					if (!this.originPlaceId || !this.destinationPlaceId) {
						return;
					}
					var me = this;

					this.directionsService.route({
						origin: {'placeId': this.originPlaceId},
						destination: {'placeId': this.destinationPlaceId},
						travelMode: this.travelMode
					}, function(response, status) {
						if (status === 'OK') {
							me.directionsDisplay.setDirections(response);

							var tripData = {};

							tripData.distance = parseInt(response.routes[0].legs[0].distance.value);
							tripData.overview_polyline = response.routes[0].overview_polyline;

							tripData.start_lat = response.routes[0].legs[0].start_location.lat().toString();
							tripData.start_lng = response.routes[0].legs[0].start_location.lng();
							tripData.end_lat = response.routes[0].legs[0].end_location.lat();
							tripData.end_lng = response.routes[0].legs[0].end_location.lng();
							tripData.start_state = response.routes[0].legs[0].start_address.split(',')[response.routes[0].legs[0].start_address.split(',').length-1];
							tripData.end_state = response.routes[0].legs[0].end_address.split(',')[response.routes[0].legs[0].end_address.split(',').length-1];

							document.getElementById("trip_code").value = JSON.stringify(tripData);

						} else {
							window.alert('Directions request failed due to ' + status);
						}
					});
				};

			</script>

		<?php elseif (isset($_GET['trip'])): ?>



















		<?php else: ?>
		
			<?php if (!isset($_GET['ajax'])): ?>
				<article class="page_content">
					<a class="button_a" href="<?php echo BASEPATH; ?>?new">Vytvoriť trasu</a>&nbsp;
				</article>


				<?php if (isset($private_trips) && count($private_trips) > 0): ?>

					<article class="page_content">
						<h2 style="margin-top: 0;">Privátne trasy</h2>

						<?php if($user_level == 2): ?>
							<form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">
								<div class="form_full">
									<input type="text" name="filterName" id="filterName" onkeyup="filterTable()" placeholder="Filtrovať na meno používateľa" />
								</div>
								<hr ><br />
							</form>
						<?php endif; ?>

						<div class="timetable_table">
							<table class="sortable" id="private_trip_table">

								<thead>
									<tr><th>Štart</th><th>Koniec</th><th>Používateľ</th><th>Operácie</th></tr>
								</thead>

								<tbody>
									<?php foreach ($private_trips as $trip): ?>
										<tr>
											<td><?php echo $trip->start ?></td>
											<td><?php echo $trip->end ?></td>
											<td><?php echo $trip->firstname.' '.$trip->lastname; ?></td>
											<td>
												<a class="button_a" href="<?php echo BASEPATH.'?trip='.$trip->id.'&user='.$trip->user_id; ?>">Zobraziť</a> 
												<?php if ($trip->id != $active_trip): ?><a class="button_a" href="<?php echo BASEPATH.'?activate='.$trip->id; ?>">Aktivovať</a><?php endif; ?>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>

							</table>
						</div>
					</article>
					
				<?php endif; ?>

			<?php endif; ?>


			<div id="ajax_refreshable">

				<?php if (isset($public_trips) && count($public_trips) > 0): ?>

					<article class="page_content">
						<h2 style="margin-top: 0;">Verejné trasy</h2>
						<div class="timetable_table">
							<table class="sortable">

								<thead>
									<tr><th>Štart</th><th>Koniec</th><th>Vytvoriľ</th><th>Operácie</th></tr>
								</thead>

								<tbody>
									<?php foreach ($public_trips as $trip): ?>
										<tr>
											<td><?php echo $trip->start ?></td>
											<td><?php echo $trip->end ?></td>
											<td><?php echo $trip->firstname.' '.$trip->lastname; ?></td>
											<td>
												<a class="button_a" href="<?php echo BASEPATH.'?trip='.$trip->id.'&user='.$trip->user_id; ?>">Zobraziť</a> 
												<?php if ($trip->id != $active_trip): ?><a class="button_a" href="<?php echo BASEPATH.'?activate='.$trip->id; ?>">Aktivovať</a><?php endif; ?>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>

							</table>
						</div>
					</article>
					
				<?php endif; ?>



				<?php if (isset($team_trips) && count($team_trips) > 0): ?>

					<article class="page_content">
						<h2 style="margin-top: 0;">Štafetové trasy</h2>

						<div class="timetable_table">
							<table class="sortable">

								<thead>
									<tr><th>Štart</th><th>Koniec</th><th>Operácie</th></tr>
								</thead>

								<tbody>
									<?php foreach ($team_trips as $trip): ?>
										<tr>
											<td><?php echo $trip->start ?></td>
											<td><?php echo $trip->end ?></td>
											<td>
												<a class="button_a" href="<?php echo BASEPATH.'?trip='.$trip->id.'&user='.$trip->user_id; ?>">Zobraziť</a> 
												<?php if ($trip->id != $active_trip): ?><a class="button_a" href="<?php echo BASEPATH.'?activate='.$trip->id; ?>">Aktivovať</a><?php endif; ?>
												<?php if ($user_level == 2): ?><a class="button_a" href="<?php echo BASEPATH.'?teams='.$trip->id; ?>">Teams</a><?php endif; ?>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>

							</table>
						</div>
					</article>
					
				<?php endif; ?>

			</div>

			<?php if (!isset($_GET['ajax'])): ?>
				<script>
					function filterTable() {
						var input, filter, table, tr, td, i;
						input = document.getElementById("filterName");
						filter = input.value.toUpperCase();
						table = document.getElementById("private_trip_table");
						tr = table.getElementsByTagName("tr");
						for (i = 0; i < tr.length; i++) {
							td = tr[i].getElementsByTagName("td")[2];
							if (td) {
								if (td.innerHTML.toUpperCase().indexOf(filter) > -1) {
									tr[i].style.display = "";
								} else {
									tr[i].style.display = "none";
								}
							}       
						}
					}

					function refreshingLoop() {
						$.ajax({url: "<?php echo BASEPATH.'?p=my_trips&ajax'; ?>", success: function(result){
							$("#ajax_refreshable").html(result);
						}});
					}
					setInterval(refreshingLoop, 1000);
					
				</script>
			<?php endif; ?>

		<?php endif; ?>

<?php if (!isset($_GET['ajax'])): ?>
	</section>
</main>
<?php endif; ?>