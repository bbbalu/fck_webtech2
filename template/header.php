<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<header>
	<div class="container">
		<div class="logo_container">
			<a href="index.php" class="logo" title="Logo"></a>
			<div class="main_info">Webové technológie 2</div>
			<div class="sub_info"><?php echo TITLE; ?></div>
		</div>

		<div class="my_name">Tím č. 13<br/><span>2017 / 2018</span></div>
	</div>

	<div class="header_image"></div>

	<div class="container">
		<nav>
			<?php $url_tags = explode('/', $_SERVER['REQUEST_URI']); ?>
			<?php
				// User levels
				// 0 -> anonym - not logged in user, 1 -> logged in user, 2 -> logged in admin
				$menus_by_user_levels = array(
					0 => array(
						'index.php' => 'Domov',
						'index.php?p=register' => 'Registrácia',
						'index.php?p=login' => 'Prihlásenie'
					),
					1 => array(
						'index.php' => 'Trasy',
						'index.php?p=results&user=0' => 'Moje vysledky',
						'index.php?p=news' => 'Aktuality',
						'index.php?p=logout' => 'Odhlásenie'
					),
					2 => array(
						'index.php?p=users' => 'Používateľia',
						'index.php?p=teams' => 'Team management',
						'index.php' => 'Trasy',
						/*'index.php?p=results' => 'Výsledky',*/
						'index.php?p=results&user='.@$_SESSION['user_id'] => 'Moje vysledky',
						'index.php?p=news' => 'Aktuality',
						'index.php?p=logout' => 'Odhlásenie'
					)
				);
			?>
			<ul>
				<?php foreach ($menus_by_user_levels[$user_level] as $href => $link_text) echo '<li'.((end($url_tags) == $href) ? ' class="active"' : '').'><a href="'.$href.'">'.$link_text.'</a></li>'; ?>
			</ul>
		</nav>
	</div>
</header>