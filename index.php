<?php
define('BASEPATH', 'index.php');
define('PAGEURL', 'http://localhost/webtech2/sem_vlastne/');

define('TITLE', 'Semestrálne zadanie');

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

if (isset($_GET['ajax']))
{
	$file = dirname(__FILE__).DIRECTORY_SEPARATOR.str_replace('/', '', str_replace('.', '', $_GET['p'])).'.php';
	if (is_file($file))
	{
		include $file;
	}

	exit();
}

// Include header
include('template/html_header.php');

?>

<body>
	<script src="https://code.jquery.com/jquery-3.3.1.min.js" integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script>

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
			if ($user_level > 0)
			{
				include 'my_trips.php';
			}
			else
			{
				include 'main_page.php';
			}
		}

	?>

	<?php include('template/footer.php'); ?>

	<script src="js/sorttable.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.2.61/jspdf.min.js"></script>
</body>
</html>