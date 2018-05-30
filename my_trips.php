<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<?php

?>

<main class="container">
	<section>

		<!-- Login page -->
		<h1 class="page_title">Trasy</h1>

		<article class="page_content">
			
		</article>

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