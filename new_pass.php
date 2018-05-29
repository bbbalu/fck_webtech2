<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<?php

	// It hash is not isset
	if (!isset($_GET['hash'])) header("Location: "+BASEPATH);

	// Find user
	$us = $db->where('mail_hash = ', $_GET['hash'])->run('users');
	if ($us->num_rows() != 1) header("Location: "+BASEPATH);
	
	// New password was posted 
	if (isset($_POST['new_password']) && isset($_POST['password']) && isset($_POST['password2'])) {

		if (strlen($_POST['password']) < 7)
		{
			$status = array('code' => '2', 'type' => "error", 'msg' => "Heslo musí byt dlhšie ako 6 znakov!");
		}
		elseif ($_POST['password'] != $_POST['password2'])
		{
			$status = array('code' => '2', 'type' => "error", 'msg' => "Heslá musia byt rovnaké!");
		}
		else
		{
			$status = $login->edit_pass($us->row()->email, $_POST['password']);
		}

	}
	
	if (isset($status) && $status['code'] == 0) header("Location: index.php");
?>

<main class="container">
	<section>

		<!-- Login page -->
		<h1 class="page_title">Zmena hesla</h1>

		<form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">

			<?php echo (isset($status) && $status['code'] != 0) ? '<div class="note '.$status['type'].'" id="send_status">'.$status['msg'].'</div>' : ''; ?>

			<?php if (!isset($status['code']) || $status['code'] != '1'): ?>

				<div class="form_row">
					<label for="password">Heslo<span>*</span></label>
					<input type="password" name="password" id="password" required />
				</div>
				<div class="form_row">
					<label for="password2">Opakujte heslo<span>*</span></label>
					<input type="password" name="password2" id="password2" required />
				</div>

				<div class="form_full">
					<input type="submit" name="new_password" value="Uložiť heslo" class="button_a"><br /><br />
				</div>

			<?php endif; ?>

		</form>

	</section>
</main>