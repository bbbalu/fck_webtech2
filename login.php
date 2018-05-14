<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<?php
	if (isset($_POST['login'])) {
		$status = $login->login($_POST['email'], $_POST['password']);

		if (isset($status) && $status['code'] == 0) header("Location: index.php");
	}
?>

<main class="container">
	<section>

		<!-- Login page -->
		<h1 class="page_title">Prihlásenie</h1>

		<form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">

			<?php echo (isset($status) && $status['code'] != 0) ? '<div class="note '.$status['type'].'" id="send_status">'.$status['msg'].'</div>' : ''; ?>

			<div class="form_row">
				<label for="email">E-mail<span>*</span></label>
				<input type="text" name="email" id="email" placeholder="E-mail" required />
			</div>
			<div class="form_row">
				<label for="password">Heslo<span>*</span></label>
				<input type="text" name="password" id="password" required />
			</div>

			<div class="form_full">
				<input type="submit" name="login" value="Prihlásenie" class="button_a"><br /><br />
			</div>

		</form>

	</section>
</main>