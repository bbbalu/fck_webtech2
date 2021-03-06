<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<?php

	// Manage subscribes
	if (isset($_GET['subscribe']))
	{
		$login->subscribe(($_GET['subscribe'] == 1) ? 1 : 0);
		header("Location: ".BASEPATH.'?p=news');
	}
	$is_subscribed = ($_SESSION['userdata']['wants_newsletters'] == 1);

	// Get existing newsletters
	$newsletters = $db->order_by('time', 'desc')->run('newsletters');
	
	// Manage making/editing of newsletters
	if (isset($_GET['id']) && $user_level == 2)
	{

		$opened_newsletter = $db->where('id =', $_GET['id'])->run('newsletters');
		if ($opened_newsletter->num_rows() == 1) {
			$newsletter_data = $opened_newsletter->row();
		}

		if (isset($_POST['save'])) {

			if (isset($newsletter_data))
			{
				$db->where('id =', $_GET['id'])->update('newsletters', array('name' => $_POST['name'], 'content' => $_POST['content']));
				$status = array('code' => '1', 'type' => "success", 'msg' => "Článok bol úspešne zmenený!");
			}
			else
			{
				$db->insert('newsletters', array('name' => $_POST['name'], 'content' => $_POST['content'], 'time' => time()));
				$status = array('code' => '1', 'type' => "success", 'msg' => "Článok bol úspešne uložený!");

				require_once('libraries/phpmailer/class.phpmailer.php');
		        require_once('libraries/phpmailer/class.smtp.php');

		        $userdata = $db->where('email =', $email)->run('users')->row();

				$mail = new PHPMailer(); // Passing `true` enables exceptions

				$needers = $db->where('wants_newsletters =', '1')->run('users');

				if ($needers->num_rows() > 0) {
					foreach ($needers->result() as $needer) {

						$mail->IsSMTP(); // enable SMTP
						$mail->SMTPDebug = 0; // debugging: 1 = errors and messages, 2 = messages only
						$mail->SMTPAuth = true; // authentication enabled
						$mail->Host = "smtp.zoznam.sk";
						$mail->Port = 587; // or 587
						$mail->CharSet = 'UTF-8';

					    $mail->Username = 'schoolproject112233@zoznam.sk'; // SMTP username
					    $mail->Password = 'Asdasd123'; // SMTP password
						
						$mail->SetFrom("schoolproject112233@zoznam.sk", "Run System");
						$mail->Subject = "Run System - Newsletter";
						$mail->AddAddress($needer->email);
					    $mail->isHTML(true); // Set email format to HTML

					    //Content
						$mail->Body = '<strong>'.$_POST['name'].'</strong><br/><br/>'.$_POST['content'].'<br/><br/>V prípade ak nechcete ďalej dostávať newslettery, prihláste sa do systému a na stránke aktuality sa môžete odhlásiť z odberu noviniek!<br/><br/>Run System';

					    $mail->send();
					}
				}

			}

			$_SESSION['msg'] = $status;
			header("Location: ".BASEPATH.'?p=news');
			session_write_close();
			exit();
		}

	}

	$is_subscribed = ($_SESSION['userdata']['wants_newsletters'] == 1);


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
		<h1 class="page_title">Novinky - Newsletters</h1>

		<article class="page_content">
			<strong>Prihlásenie na odber noviniek:</strong>&nbsp; &nbsp; &nbsp; &nbsp;
			<a class="button_a" href="<?php echo BASEPATH.'?p=news&subscribe='.($is_subscribed ? 0 : 1); ?>"><?php echo ($is_subscribed ? 'Odhlásiť sa z odberu noviniek' : 'Prihlásiť sa na odber noviniek'); ?></a>

			<?php if ($user_level == 2) echo '<br /><br /><a href="'.BASEPATH.'?p=news&id">Vytvoriť nový článok</a>'; ?>

		</article>


		<?php echo (isset($status) && $status['code'] != 0) ? '<div class="note '.$status['type'].'" id="send_status">'.$status['msg'].'</div>' : ''; ?>


		<?php if (isset($_GET['id']) && $user_level == 2): ?>
			
			<form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">

				<div class="form_row">
					<label for="name">Názov<span>*</span></label>
					<input type="text" name="name" id="name" required value="<?php echo (isset($newsletter_data)) ? $newsletter_data->name : ''; ?>" />
				</div>
				<div class="form_full">
					<label for="content">Obsah<span>*</span></label>
					<textarea name="content" id="content" required><?php echo (isset($newsletter_data)) ? $newsletter_data->content : ''; ?></textarea>
				</div>

				<div class="form_full">
					<input type="submit" name="save" value="Uložiť" class="button_a"><br /><br />
				</div>

			</form>

		<?php else: ?>

			<?php if (true || $is_subscribed || $user_level == 2): ?>
				
				<?php
					if ($newsletters->num_rows() > 0)
					{
						foreach ($newsletters->result() as $newsletter)
						{
							$edit = ($user_level == 2) ? ' (<a href="'.BASEPATH.'?p=news&id='.$newsletter->id.'">Modifikovať</a>)' : '';
							echo '<article class="page_content"><strong>'.$newsletter->name.'</strong>'.$edit.'<br /><p>'.nl2br($newsletter->content).'</p></article>';
						}
					}
					else
					{
						echo '<article class="page_content"><p>V systéme zatiaľ neexistujú žiadne novinky</p></article>';
					}
				?>

			<?php else: ?>

				<article class="page_content">
					<p>Nie ste prihlásený na odber noviniek!</p>
				</article>

			<?php endif; ?>

		<?php endif; ?>

	</section>
</main>