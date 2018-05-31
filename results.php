<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<?php
	function people_time($input) {
		$int_input = intval($input);
		$hours = floor($int_input / 60);
		$int_input -= $hours*60;
		return $hours.':'.$int_input;
	}

	// Currently selected trip
	$active_trip = $login->get_my_active_trip();

	// Write in workout
	if (isset($_GET['new']) && isset($_POST['save']))
	{
		$in_data = $_POST;

		$in_data['distance'] = intval($in_data['distance'])*1000;

		if (!empty($in_data['time_start']))
		{
			$tmp = explode(':', $in_data['time_start']);
			$in_data['time_start'] = (intval($tmp[0])*60) + intval($tmp[1]);
		}

		if (!empty($in_data['time_end']))
		{
			$tmp = explode(':', $in_data['time_end']);
			$in_data['time_end'] = (intval($tmp[0])*60) + intval($tmp[1]);
		}

		$in_data['start_lat'] = floatval(str_replace(',', '.', $in_data['start_lat']));
		$in_data['start_lon'] = floatval(str_replace(',', '.', $in_data['start_lon']));
		$in_data['end_lat'] = floatval(str_replace(',', '.', $in_data['end_lat']));
		$in_data['end_lon'] = floatval(str_replace(',', '.', $in_data['end_lon']));

		if (empty($in_data['day'])) $in_data['day'] = null;

		$db->insert('workouts', array(
			'user_id' => $_SESSION['user_id'],
			'trip_id' => $active_trip,
			'distance' => $in_data['distance'],
			'day' => $in_data['day'],
			'time_start' => intval($in_data['time_start']),
			'time_end' => intval($in_data['time_end']),
			'start_lat' => $in_data['start_lat'],
			'start_lon' => $in_data['start_lon'],
			'end_lat' => $in_data['end_lat'],
			'end_lon' => $in_data['end_lon'],
			'rating' => $in_data['rating'],
			'note' => $in_data['note'],
		));

		$status = array('code' => '1', 'type' => "success", 'msg' => "Trasa bola úspešne uložená!");

		$_SESSION['msg'] = $status;
		header("Location: ".BASEPATH.'?p=results');
		session_write_close();
		exit();
	}
	else
	{
		$w_user_id = (!isset($_GET['user'])) ? $_SESSION['user_id'] : $_GET['user'];

		$w_user = $db->where('id =', $w_user_id)->run('users');

		if ($w_user->num_rows() > 0)
		{
			$row = $w_user->row();
			$w_user_name = $row->firstname.' '.$row->lastname;
		}
		else
		{
			$w_user_id = $_SESSION['user_id'];
			$w_user_name = $_SESSION['userdata']['firstname'].' '.$_SESSION['userdata']['lastname'];
		}


		if ($user_level == 2)
		{
			$workouts = $db->where('user_id =', $w_user_id)->run('workouts')->result();
		}
		else
		{
			$workouts = $db->where('user_id =', $_SESSION['user_id'])->run('workouts')->result();
		}

		$write_in_enabled = $w_user_id == $_SESSION['user_id'];

	}

?>

<main class="container">
	<section>

		<?php
			if (isset($_SESSION['msg'])) {
				$status = $_SESSION['msg'];
				unset($_SESSION['msg']);
			}
		?>

		<!-- Login page -->
		<h1 class="page_title">Výsledky používateľa: <?php echo $w_user_name; ?></h1>

		<?php echo (isset($status) && $status['code'] != 0) ? '<div class="note '.$status['type'].'" id="send_status">'.$status['msg'].'</div>' : ''; ?>


		<?php if (isset($_GET['new'])): ?>

			<article class="page_content">
				<h2 style="margin-top: 0;">Zapísanie tréningu</h2>

				<form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">

					<div class="form_row">
						<label for="distance">Počet odbehnutých km:<span>*</span></label>
						<input type="text" name="distance" id="distance" required />
					</div>
					<div class="form_row">
						<label for="day">Deň tréningu:</label>
						<input type="date" name="day" id="day" />
					</div>
					<div class="form_row">
						<label for="time_start">Začiatok tréningu:</label>
						<input type="time" name="time_start" id="time_start" />
					</div>
					<div class="form_row">
						<label for="time_end">Koniec tréningu:</label>
						<input type="time" name="time_end" id="time_end" />
					</div>

					<hr><br />

					<div class="form_row">
						<label for="start_lat">Začiatok Lat:</label>
						<input type="text" name="start_lat" id="start_lat" />
					</div>
					<div class="form_row">
						<label for="start_lon">Začiatok Lon:</label>
						<input type="text" name="start_lon" id="start_lon" />
					</div>

					<div class="form_row">
						<label for="end_lat">Koniec Lat:</label>
						<input type="text" name="end_lat" id="end_lat" />
					</div>
					<div class="form_row">
						<label for="end_lon">Koniec Lon:</label>
						<input type="text" name="end_lon" id="end_lon" />
					</div>

					<div class="form_row">
						<label for="rating">Hodnotenie</label>
						<select name="rating">
							<option value="5">*****</option>
							<option value="4">****</option>
							<option value="3">***</option>
							<option value="2">**</option>
							<option value="1">*</option>
						</select>
					</div>

					<div class="form_full">
						<label for="note">Poznámka</label>
						<textarea name="note" id="note"></textarea>
					</div>

					<div class="form_full">
						<input type="submit" name="save" value="Zapísať tréning" class="button_a">
					</div>
				</form>

			</article>






		<?php else: ?>

			<?php if ($write_in_enabled): ?>
				<article class="page_content">
					<a class="button_a" href="<?php echo BASEPATH; ?>?p=results&new">Zapísať tréning</a>&nbsp;
				</article>
			<?php endif; ?>

			<?php if (isset($workouts) && count($workouts) > 0): ?>

				<article class="page_content toprint">

					<div class="timetable_table">
						<table class="sortable" id="result_table">

							<thead>
								<tr>
									<th>Vzďialenosť</th>
									<th>Deň tréningu</th>
									<th>Začiatok</th>
									<th>Koniec</th>
									<th>Začiatok GPS</th>
									<th>Koniec GPS</th>
									<th>Hodnotenie</th>
									<th>Poznamka</th>
									<th>Priemerna rýchlosť</th>
								</tr>
							</thead>

							<tbody>
								<?php $full_distance = 0; foreach ($workouts as $workout): $full_distance += intval($workout->distance); ?>
									<tr>
										<td><?php echo intval($workout->distance) / 1000 ?> km</td>
										<td><?php echo $workout->day ?></td>
										<td><?php echo ($workout->time_start != 0) ? people_time($workout->time_start) : ''; ?></td>
										<td><?php echo ($workout->time_end != 0) ? people_time($workout->time_end) : ''; ?></td>
										<td><?php echo ($workout->start_lat != 0) ? $workout->start_lat . ', ' . $workout->start_lon : ''; ?></td>
										<td><?php echo ($workout->end_lat != 0) ? $workout->end_lat . ', ' . $workout->end_lon : ''; ?></td>
										<td><?php echo str_repeat('*', $workout->rating) ?></td>
										<td><?php echo $workout->note ?></td>
										<?php $full_fime = $workout->time_end - $workout->time_start; ?>
										<td><?php echo ($full_fime > 0) ? round(($workout->distance/1000)/(($full_fime/60)), 2). 'km/h' : 'nevypočítateľné' ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>

							<tfoot>
								<tr style="border-top: 2px solid #000;">
									<td colspan="8"><strong>Priemerná hodnota odbehnutých kilometrov:</strong></td>
									<td><?php echo ($full_distance/1000) / count($workouts); ?> km</td>
								</tr>
							</tfoot>

						</table>

						<p><a onclick="createPDF();" class="button_a">Stiahnúť ako PDF</a></p>

					</div>
				</article>

				<script defer src="js/libs/jspdf.debug.js"></script>
				<script defer src="js/jspdf.customfonts.min.js"></script>
				<script defer src="js/default_vfs.js"></script>
				<script defer src="js/libs/jspdf.plugin.autotable.js"></script>

				<script defer type="text/javascript">
					function createPDF() {
						var pdf = new jsPDF('l' , 'pt' , 'a4');

						pdf.addFont('Roboto-Bold.ttf', 'roboto-bold', 'bold');
						pdf.addFont('Roboto-Regular.ttf', 'roboto', 'normal');

						pdf.text("Vygenerovaný zoznam tréningov", 14, 16);
						var elem = document.getElementById("result_table");
						var res = pdf.autoTableHtmlToJson(elem);

						pdf.autoTable(res.columns, res.data,{
							theme: 'grid',
							startY: 20,
							margin: {
								horizontal: 7
							},
							styles: {
								cellPadding: 9,
								fontSize: 9,
								font: "roboto",
								fontStyle: 'normal',
								overflow: 'linebreak',
								textColor: 17,
								columnWidth: 'auto'
							},

							headerStyles: {
								font: "roboto-bold",
								fontStyle: "bold"
							}
						});

						pdf.save('Statistics.pdf');
					}

				</script>
				
			<?php else: ?>

				<article class="page_content">
					<p>Používateľ nemá zapísané žiadne zozonamy!</p>
					<p><a onclick="window.history.back();" class="button_a">Naspäť</a></p>
				</article>

			<?php endif; ?>

		<?php endif; ?>

	</section>
</main>