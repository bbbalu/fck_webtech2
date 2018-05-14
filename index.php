<?php
define('BASEPATH', 'index.php');

define('TITLE', 'Semestrálne zadanie');

// Include header
include('template/html_header.php');

// Load config + db manager
require_once('config.php');
require_once('libraries/db.php');
require_once('libraries/login.php');

// Initialize session
ob_start();
session_start();

// Initialize a new database manager + get data
$db = new db();
$login = new login();

// User levels
// 0 -> anonym - not logged in user, 1 -> logged in user, 2 -> logged in admin
$user_level = (!$login->is_logged_in() ? 0 : ($login->is_admin() ? 2 : 1));

?>

<body>

	<?php include('template/header.php'); ?>


	<?php
		if (isset($_GET['p']))
		{
			$file = dirname(__FILE__).DIRECTORY_SEPARATOR.str_replace('/', '', str_replace('.', '', $_GET['p'])).'.php';
			if (is_file($file))
			{
				include $file;
			}
			else
			{
				echo '<main class="container">';
					echo '<section>';

						echo '<h1 class="page_title">Zvoľena stránka neexistuje</h1>';

						echo '<article class="page_content">';
							echo 'Vami zvolena stránka neexistuje!';
						echo '</article>';

					echo '</section>';
				echo '</main>';
			}
		}
		else
		{
			include 'main_page.php';
		}

	?>

	<?php include('template/footer.php'); ?>

</body>
</html>