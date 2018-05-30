<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<?php

	// Defaultné heslo
	$default_password = '12345678';

	// This page can open only administrator
	if ($user_level != 2) header("Location ".BASEPATH);

	// Set user levels
	if (isset($_GET['to_admin'])) {
		$db->where('id =', $_GET['to_admin'])->update('users', array('is_admin' => 1));
		$status = array('code' => '1', 'type' => "success", 'msg' => "Používateľ bol nastavený ako admin!");
		header("Location: ".BASEPATH.'?p=users');
		session_write_close();
		exit();
	}

	if (isset($_GET['to_user'])) {
		$db->where('id =', $_GET['to_user'])->update('users', array('is_admin' => 0));
		$status = array('code' => '1', 'type' => "success", 'msg' => "Používateľ bol nastavený ako user!");
		header("Location: ".BASEPATH.'?p=users');
		session_write_close();
		exit();
	}


	if (isset($_GET['import'])) {
		$ignore_rows = 1;
		$error = 0;

		if (isset($_POST['save'])) {

			$name = $_FILES["upl_file"]["name"];
			$ext = end((explode(".", $name)));


			if ($_FILES['upl_file']['error'] == 0 && strtolower($ext) == 'csv') {

				if (($handle = fopen($_FILES["upl_file"]["tmp_name"], "r")) !== FALSE) {
					while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {

						// Ignore some rows
						if ($ignore_rows-- > 0) continue;

						// Every row must have 9 columns
						if (count($data) != 9)
						{
							$error = 1;
							$status = array('code' => '2', 'type' => "error", 'msg' => "Chybný formát csv exportu!");
							break;
						}

						$sql_data = array(
							//'id' => iconv('windows-1250', 'UTF-8//TRANSLIT', $data[0]),
							'lastname' => iconv('windows-1250', 'UTF-8//TRANSLIT', $data[1]),
							'firstname' => iconv('windows-1250', 'UTF-8//TRANSLIT', $data[2]),
							'email' => iconv('windows-1250', 'UTF-8//TRANSLIT', $data[3]),
							'school' => iconv('windows-1250', 'UTF-8//TRANSLIT', $data[4]),
							'school_address' => iconv('windows-1250', 'UTF-8//TRANSLIT', $data[5]),
							'address' => iconv('windows-1250', 'UTF-8//TRANSLIT', $data[6]),
							'zip_code' => iconv('windows-1250', 'UTF-8//TRANSLIT', $data[7]),
							'city' => iconv('windows-1250', 'UTF-8//TRANSLIT', $data[8]),
							'password' => md5($default_password),
							'verificated' => 2,
							'mail_hash' => md5(rand(9,99999).time().rand(0,99999)),
							'roles' => 0
						);

						var_dump($sql_data);

						$db->insert('users', $sql_data);
						$status = array('code' => '1', 'type' => "success", 'msg' => "Import bol úspešne vykonaný!");
					}

					fclose($handle);
				}
				else
				{
					$error = 1;
					$status = array('code' => '2', 'type' => "error", 'msg' => "Chybný obsah súboru!");
				}

			}
			else
			{
				$error = 1;
				$status = array('code' => '2', 'type' => "error", 'msg' => "Chybný súbor, alebo chybný formát!");
			}

			$_SESSION['msg'] = $status;
			header("Location: ".BASEPATH.'?p=users');
			session_write_close();
			exit();
		}
	}
	elseif (isset($_GET['add'])) {


	}
	else
	{
		// Get all users
		$users = $db->run('users');
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
		<h1 class="page_title">Management používateľov</h1>

		<?php echo (isset($status) && $status['code'] != 0) ? '<div class="note '.$status['type'].'" id="send_status">'.$status['msg'].'</div>' : ''; ?>

		<?php if (isset($_GET['import'])): ?>
			
			<form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post" enctype="multipart/form-data">

				<div class="form_row">
					<label for="upl_file"><strong>Import zo súbora</strong><span>*</span></label>
					<input type="file" name="upl_file" id="upl_file" required />
				</div>

				<div class="form_full">
					<input type="submit" name="save" value="Uložiť" class="button_a">&nbsp;&nbsp;
					<a href="<?php echo BASEPATH; ?>?p=users" class="button_a">Späť</a>
				</div>

			</form>

		<?php elseif (isset($_GET['add'])): ?>


		<?php else: ?>

			<article class="page_content">

				<a class="button_a" href="<?php echo BASEPATH; ?>?p=users&import">Importovať používateľov</a>&nbsp;
				<!--a class="button_a" href="<?php echo BASEPATH; ?>?p=users&add">Pridať používateľa</a-->

			</article>

			<article class="page_content">
				<div class="timetable_table">
					<table class="sortable">

						<thead>
							<tr>
								<th>Id</th>
								<th>Meno</th>
								<th>Priezvisko</th>
								<th>E-mail</th>
								<th>Adresa</th>
								<th>Operácie</th>
							</tr>
						</thead>

						<tbody>
							<?php foreach ($users->result() as $usr): ?>
								<tr>
									<td><?php echo $usr->id ?></td>
									<td><?php echo $usr->firstname ?></td>
									<td><?php echo $usr->lastname ?></td>
									<td><?php echo $usr->email ?></td>
									<td><?php echo $usr->city.', '.$usr->address.', '.$usr->zip_code; ?></td>
									<td>
										<a class="button_a" href="<?php echo BASEPATH.'?p=results&user='.$usr->id; ?>">Zobraziť</a> 
										<?php echo ($usr->is_admin == 0) ? '<a class="button_a" href="'.BASEPATH.'?p=users&to_admin='.$usr->id.'">To admin</a>' : ''; ?>
										<?php echo ($usr->is_admin == 1) ? '<a class="button_a" href="'.BASEPATH.'?p=users&to_user='.$usr->id.'">To user</a>' : ''; ?>
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