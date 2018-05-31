<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<?php
	
	// This page can open only administrator
	if ($user_level != 2) header("Location ".BASEPATH);


	if (isset($_GET['delete']))
	{
		$db->where('id =', $_GET['delete'])->delete('teams');
		$db->where('team_id =', $_GET['delete'])->delete('user2team');
		$db->where('team_id =', $_GET['delete'])->delete('team2trip');

		$status = array('code' => '1', 'type' => "success", 'msg' => "Tím bol úspešne zmazaný!");

		$_SESSION['msg'] = $status;
		header("Location: ".BASEPATH.'?p=teams');
		session_write_close();
		exit();
	}

	if (isset($_GET['new']))
	{
		$team = $db->where('id =', $_GET['new'])->run('teams');
		$is_editing = ($team->num_rows() > 0);

		if ($is_editing)
		{
			$teamdata = $team->row();

			$teamdata->users = array();
			$ext_users = $db->where('team_id =', $teamdata->id)->run('user2team');
			if ($ext_users->num_rows() > 0) {
				foreach ($ext_users->result() as $extu) {
					$teamdata->users[] = $extu->user_id;
				}
			}

			$teamdata->trips = array();
			$ext_trips = $db->where('team_id =', $teamdata->id)->run('team2trip');
			if ($ext_trips->num_rows() > 0) {
				foreach ($ext_trips->result() as $extt) {
					$teamdata->trips[] = $extt->trip_id;
				}
			}
		}

		$all_users = $db->run('users')->result(); 
		$all_trips = $db->where('type =', 3)->run('trips')->result(); 

		if (isset($_POST['save']))
		{
			if ($is_editing)
			{
				// Update
				$db->where('id =', $teamdata->id)->update('teams', array('name' => $_POST['name']));
				$new_id = $teamdata->id;

				$db->where('team_id =', $teamdata->id)->delete('user2team');
				$db->where('team_id =', $teamdata->id)->delete('team2trip');
			}
			else
			{
				// New
				$db->insert('teams', array('name' => $_POST['name']));
				$new_id = $db->last_insert_id();
			}


			foreach ($_POST['users'] as $pusr) {
				$db->insert('user2team', array('user_id' => $pusr, 'team_id' => $new_id));
			}

			foreach ($_POST['trips'] as $ptrip) {
				$db->insert('team2trip', array('trip_id' => $ptrip, 'team_id' => $new_id));
			}

			$status = array('code' => '1', 'type' => "success", 'msg' => "Tím bol úspešne vytvorený!");

			$_SESSION['msg'] = $status;
			header("Location: ".BASEPATH.'?p=teams');
			session_write_close();
			exit();

		}

	}
	$teams = $db->run('teams')->result();

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
		<h1 class="page_title">Manažment tímov</h1>

		<?php echo (isset($status) && $status['code'] != 0) ? '<div class="note '.$status['type'].'" id="send_status">'.$status['msg'].'</div>' : ''; ?>


		

		<?php if (isset($_GET['new'])): ?>

			<article class="page_content">
				<h2 style="margin-top: 0;">Vytvorenie tímu</h2>

				<form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">

					<div class="form_full">
						<label for="name">Meno tímu<span>*</span></label>
						<input type="text" name="name" id="name" value="<?php echo ($is_editing) ? $teamdata->name : ''; ?>" required />
					</div>

					<hr>

					<h2 style="margin-top: 0;">Priradenie používatelov</h2>
					<div class="form_full user_check">
						<?php foreach ($all_users as $user): ?>
							<div style="display:inline-block; width: 23%;">
								<?php $checked = ($is_editing && in_array($user->id, $teamdata->users)) ? ' checked="checked"' : ''; ?>
								<input type="checkbox" value="<?php echo $user->id; ?>" id="usr<?php echo $user->id; ?>" <?php echo $checked; ?> name="users[]">
								<label for="usr<?php echo $user->id; ?>"><?php echo $user->firstname.' '.$user->lastname; ?></label>
							</div>
						<?php endforeach; ?>
					</div>

					<hr>

					<h2 style="margin-top: 0;">Priradenie trás</h2>
					
					<div class="form_full trip_check">
						<?php foreach ($all_trips as $trip): ?>
							<div style="display:inline-block; width: 23%;">
								<?php $checked = ($is_editing && in_array($trip->id, $teamdata->trips)) ? ' checked="checked"' : ''; ?>
								<input type="checkbox" value="<?php echo $trip->id; ?>" id="trp<?php echo $trip->id; ?>" <?php echo $checked; ?> name="trips[]">
								<label for="trp<?php echo $trip->id; ?>"><?php echo $trip->trip_name; ?></label>
							</div>
						<?php endforeach; ?>
					</div>

					<hr><br/>

					<div class="form_full">
						<input type="submit" name="save" value="Uložiť trasu" class="button_a">
					</div>
				</form>

			</article>

			<script type="text/javascript">
				$('.user_check input[type="checkbox"]').click(function(e) {
					if ($('.user_check input[type="checkbox"]:checked').length > 6) {
						alert("Je povolene priradiť max. 6 používateľov");
						e.preventDefault();
					}
				});


			</script>


		
		<?php else: ?>

			<article class="page_content">
				<a class="button_a" href="<?php echo BASEPATH; ?>?p=teams&new">Pridať nový tím</a>
			</article>

			<article class="page_content">
				<div class="timetable_table">
					<table class="sortable">

						<thead>
							<tr>
								<th>Id</th>
								<th>Meno</th>
								<th>Operácie</th>
							</tr>
						</thead>

						<tbody>
							<?php foreach ($teams as $team): ?>
								<tr>
									<td><?php echo $team->id ?></td>
									<td style="width: 70%;"><?php echo $team->name ?></td>
									<td>
										<a class="button_a" href="<?php echo BASEPATH.'?p=teams&new='.$team->id; ?>">Edit</a> 
										<a class="button_a" href="<?php echo BASEPATH.'?p=teams&delete='.$team->id; ?>">Zmazať</a> 
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>

					</table>
				</div>
			</article>

		<?php endif; ?>

	</section>
</main>