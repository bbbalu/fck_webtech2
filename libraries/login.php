<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
* Stupid login
* @Author: Balázs Nagy
* @Version: 0.1
*/

class Login
{
	private $db = null;
	private $enabled_fields = array('lastname', 'firstname', 'email', 'school', 'school_address', 'address', 'zip_code', 'city');

	function __construct($config = false)
	{
		require_once('libraries/db.php');
		$this->db = new db();
	}

	public function login($email, $password)
	{

		// Get user data
		$user = $this->db->where('email =', $email)->where('password =', md5($password))->run('users');

		if ($user->num_rows() != 1) {
			$status = array('code' => '2', 'type' => "error", 'msg' => "Nesprávne prihlasovacie meno alebo heslo");
		} else {
			$logged_in_user = $user->row_array();

			if ($logged_in_user['verificated'] == '1')
			{
				$_SESSION['user_id'] = $logged_in_user['id'];
				$_SESSION['userdata'] = $logged_in_user;

				// Add info about login attempt to DB
				$this->db->insert("history", array('user_id' => $logged_in_user['id'], 'time' => time(), 'login_type' => 0));

				// Return a status info
				$status = array('code' => '0', 'type' => "success", 'msg' => "Úspešné prihlásenie!");
			}
			elseif ($logged_in_user['verificated'] == '2')
			{
				header("Location: index.php?p=new_pass&hash=".$logged_in_user['mail_hash']);
			}
			else
			{
				// If user in not verificated by mail
				$status = array('code' => '2', 'type' => "error", 'msg' => "Používateľský účet nie je overený cez mail!");
			}
		}

		return $status;
	}

	public function register($userdata)
	{
		// Validate data fields
		$fields_are_valid = true;
		$post_field_keys = array_keys($userdata);
		$post_field_keys[] = 'register';

		foreach ($this->enabled_fields as $field) $fields_are_valid &= in_array($field, $post_field_keys);

		if ($fields_are_valid)
		{
			if ($this->db->where('email =', $userdata['email'])->run('users')->num_rows() == 0)
			{

				// Register
				$reg_data = array(
					'lastname' => $userdata['lastname'],
					'firstname' => $userdata['firstname'],
					'email' => $userdata['email'],
					'school' => $userdata['school'],
					'school_address' => $userdata['school_address'],
					'address' => $userdata['address'],
					'zip_code' => preg_replace("/[^0-9,.]/", "", $userdata['zip_code']),
					'city' => $userdata['city'],
					'password' => '',
					'verificated' => 0,
					'mail_hash' => md5(rand(9,99999).time().rand(0,99999)),
					'wants_newsletters' => 0,
					'active' => 0,
					'is_admin' => 0,
					'roles' => ''
				);

				$this->db->insert('users', $reg_data);

				// Inform user to set new password
				$this->send_password_mail('bbbalu@live.com');

				$status = array('code' => '1', 'type' => "success", 'msg' => "Úspešná registrácia!");
			}
			else
			{
				$status = array('code' => '2', 'type' => "error", 'msg' => "Táto e-mailová adresa je už evidovaná v systeme!");
			}
		}
		else
		{
			$status = array('code' => '2', 'type' => "error", 'msg' => "Systemová chyba počas registrácii, kontaktujte administrátora!");
		}

		return $status;
	}

	public function logout()
	{
		unset($_SESSION['user_id']);
		unset($_SESSION['userdata']);

		// Return a status info
		$status = array('code' => '0', 'type' => "success", 'msg' => "Úspešné odhlásenie!");

		return $status;
	}

	public function has_role($role_name)
	{
		$result = false;

		if (isset($_SESSION['userdata']['roles']) && isset($_SESSION['userdata']['is_admin']))
		{
			$roles = explode(',', $_SESSION['userdata']['roles']);
			$result = in_array($role_name, $roles);
		}

		return $result;
	}

	public function is_logged_in()
	{
		return isset($_SESSION['user_id']) && $_SESSION['user_id'] != 0 && isset($_SESSION['userdata']) && is_array($_SESSION['userdata']);
	}

	public function is_admin()
	{
		$result = false;

		if (isset($_SESSION['userdata']['roles']) && isset($_SESSION['userdata']['is_admin']))
		{
			$result = ($_SESSION['userdata']['is_admin'] == 1);
		}

		return $result;
	}

	public function edit_pass($email, $pass)
	{
		$this->db->where('email =', $email)->update('users', array('password' => md5($pass), 'mail_hash' => '', 'verificated' => '1'));
		$status = array('code' => '1', 'type' => "success", 'msg' => "Heslo bolo úspešne zmenené!");
		return $status;
	}

	public function send_password_mail($email)
	{
		require_once('libraries/phpmailer/class.phpmailer.php');
        require_once('libraries/phpmailer/class.smtp.php');

        $userdata = $this->db->where('email =', $email)->run('users')->row();

		$mail = new PHPMailer(true); // Passing `true` enables exceptions

		try {

			$mail->IsSMTP(); // enable SMTP
			$mail->SMTPDebug = 0; // debugging: 1 = errors and messages, 2 = messages only
			$mail->SMTPAuth = true; // authentication enabled
			$mail->Host = "smtp.zoznam.sk";
			$mail->Port = 587; // or 587
			$mail->CharSet = 'UTF-8';

		    $mail->Username = 'schoolproject112233@zoznam.sk'; // SMTP username
		    $mail->Password = 'Asdasd123'; // SMTP password
			
			$mail->SetFrom("schoolproject112233@zoznam.sk", "Run System");
			$mail->Subject = "Run System - Nastavte nové heslo";
			$mail->AddAddress($email);
		    $mail->isHTML(true); // Set email format to HTML

		    //Content
			$mail->Body = 'Overte Vašu mail adresu a nastavte si nové heslo kliknutim na nasledujúci odkaz: <br/><a href="'.PAGEURL.'/index.php?p=new_pass&hash='.$userdata->mail_hash.'">'.PAGEURL.'/index.php?p=new_pass&hash='.$userdata->mail_hash.'</a><br/><br/>Run System';

		    $mail->send();

		    return true;

		} catch (Exception $e) {
			$status = array('code' => '2', 'type' => "error", 'msg' => "E-mail nebolo možné odoslať!");
			return $status;
		}

	}

	function subscribe($state) {
		$this->db->where('id =', $_SESSION['user_id'])->update('users', array('wants_newsletters' => $state));
		$_SESSION['userdata']['wants_newsletters'] = $state;
	}


	function get_my_active_trip() {
		return $this->db->where('id =', $_SESSION['user_id'])->run('users')->row()->active_trip;
	}

	function active_trip($trip_id) {
		$this->db->where('id =', $_SESSION['user_id'])->update('users', array('active_trip' => $trip_id));
		return true;
	}
}

