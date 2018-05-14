<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<?php
	if (isset($_POST['register'])) {
		$status = $login->register($_POST);
	}
?>

<main class="container">
	<section>

		<!-- Login page -->
		<h1 class="page_title">Registrácia</h1>

		<form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">

			<?php echo (isset($status) && $status['code'] != 0) ? '<div class="note '.$status['type'].'" id="send_status">'.$status['msg'].'</div>' : ''; ?>

			<div class="form_row">
				<label for="lastname">Priezvisko<span>*</span></label>
				<input type="text" name="lastname" id="lastname" placeholder="Priezvisko" required />
			</div>
			<div class="form_row">
				<label for="firstname">Krstné meno<span>*</span></label>
				<input type="text" name="firstname" id="firstname" placeholder="Krstné meno" required />
			</div>
			<div class="form_row">
				<label for="email">E-mail<span>*</span></label>
				<input type="text" name="email" id="email" placeholder="E-mail" required />
			</div>
			<div class="form_row">
				<label for="password">Heslo<span>*</span></label>
				<input type="text" name="password" id="password" required />
			</div>
			<div class="form_row">
				<label for="school">Škola<span>*</span></label>
				<input type="text" name="school" id="school" placeholder="Škola" required />
			</div>
			<div class="form_row">
				<label for="school_address">Adresa školy<span>*</span></label>
				<input type="text" name="school_address" id="school_address" placeholder="Adresa školy" required />
			</div>
			<div class="form_row">
				<label for="address">Vaša adresa<span>*</span></label>
				<input type="text" name="address" id="address" placeholder="Vaša adresa" required />
			</div>
			<div class="form_row">
				<label for="zip_code">PSČ<span>*</span></label>
				<input type="text" name="zip_code" id="zip_code" placeholder="PSČ" required />
			</div>
			<div class="form_row">
				<label for="city">Mesto/Obec<span>*</span></label>
				<input type="text" name="city" id="city" placeholder="Mesto/Obec" required />
			</div>

			<div class="form_full">
				<input type="submit" name="register" value="Registrácia" class="button_a">
			</div>

		</form>

	</section>
</main>