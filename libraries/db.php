<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
* DB manager
* @Author: Balázs Nagy
* @Version: 0.2
*/

class db
{
	
	private $error_messages = array (
		"1045" => "Nebolo možné overiť totožnosť užívateľa. \n Zadali ste správne užívateľské meno a heslo?",
		"2005" => "Nebolo možné kontaktovať MySQL server.",
		"2013" => "Nepodarilo sa pripojenie k MySQL. Overťe, či bol správne zadaný názov databázy."
	);

	private $connection = null;
	private $query_result = null;

	private $sql = '';
	private $select_data = '*';
	private $join_data = '';
	private $where_data = '';
	private $order_data = '';

	private $bind_data = array();

	function __construct($config = false)
	{
		if (!$config) {
			// Initialize from config file
			require(__DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'config.php');
			@$this->connection = new mysqli($dbconfig['hostname'], $dbconfig['username'], $dbconfig['password'], $dbconfig['dbname']);
		} else {
			// Initialize from constructor 
			@$this->connection = new mysqli($config['hostname'], $config['username'], $config['password'], $config['dbname']);
		}

		// If was detected a connection error
		if ($this->connection->connect_error) {
			$mysql_error_number = $this->connection->connect_errno;
			$error_feedback = 'Vyskytla chyba (' . $mysql_error_number . ') '. $this->connection->connect_error;

			// If we have this message in slovak
			if (in_array($mysql_error_number, array_keys($this->error_messages))) {
				$error_feedback = 'Vyskytla chyba (' . $mysql_error_number . ') '. $this->error_messages[$mysql_error_number];
			}

			// Exit application
			exit($error_feedback);
		}

		$this->connection->set_charset("utf8");
	}

	public function initialize($value='')
	{
		$this->sql = '';
		$this->select_data = '*';
		$this->join_data = '';
		$this->where_data = '';
		$this->order_data = '';

		$this->bind_data = array();
	}

	public function select($fileds, $associative = true)
	{
		if ($this->select_data == '*') $this->select_data = '';

		$select_begin = (!empty($this->select_data)) ? ', ' : '';

		if (is_array($fileds))
		{
			if ($associative)
			{
				$select_tmp = array();
				foreach ($fileds as $new_name => $real_path) $select_tmp[] = $real_path.' AS '.$new_name;
				$this->select_data .= $select_begin.implode(', ', $select_tmp);
			}
			else $this->select_data .= $select_begin.implode(', ', $fileds);
		}
		else
			$this->select_data .= $select_begin.$fileds;

		return $this;
	}

	public function join($table, $on, $type = "INNER")
	{
		$this->join_data .= ' '.$type.' JOIN '.$table.' ON '.$on;

		return $this;
	}

	public function where($wh_input, $in_value = false)
	{
		if (is_array($wh_input))
		{
			if (count($wh_input) > 0)
			{
				$wg_begin = (!empty($this->where_data)) ? ' AND ' : '';
				$this->where_data .= $wg_begin.implode(' ? AND ', array_keys($wh_input)).' ?';
				foreach ($wh_input as $key => $value) $this->bind_data[] = (string)$value;
			}
		}
		else
		{
			if (!empty($wh_input))
			{
				$wg_begin = (!empty($this->where_data)) ? ' AND ' : '';
				$this->where_data .= $wg_begin.$wh_input.' ?';
				$this->bind_data[] = $in_value;
			}
		}

		return $this;
	}

	public function order_by($order_by, $order_type = 'ASC')
	{
		if (!empty($order_by))
		{
			$order_begin = (!empty($this->order_data)) ? ', ' : '';
			$this->order_data .= $order_begin.$order_by.' '.$order_type;
		}

		return $this;
	}

	public function run($table)
	{
		$where = (!empty($this->where_data)) ? ' WHERE '.$this->where_data : '';
		$order = (!empty($this->order_data)) ? ' ORDER BY '.$this->order_data : '';

		$query = "SELECT ".$this->select_data." FROM ".$table.$this->join_data.$where.$order;

		$params = array(str_repeat("s", count($this->bind_data)));
		foreach ($this->bind_data as $key => $v) $params[] = &$this->bind_data[$key];

		//var_dump($query);

		$stmt = $this->connection->stmt_init();
		if(!$stmt->prepare($query))
		{
			die("Failed to prepare statement: ". $this->connection->error);
		}
		else
		{
			if (count($params) > 1)
				call_user_func_array(array($stmt, 'bind_param'), $params);
			
			if(!$stmt->execute()) die($stmt->error);
		}

		$this->query_result = $stmt->get_result();
		$this->initialize();

		return $this;
	}


	public function delete($table)
	{
		$where = (!empty($this->where_data)) ? ' WHERE '.$this->where_data : '';
		$query = "DELETE FROM ".$table.$where;

		$params = array(str_repeat("s", count($this->bind_data)));
		foreach ($this->bind_data as $key => $v) $params[] = &$this->bind_data[$key];

		//var_dump($query);

		$stmt = $this->connection->stmt_init();
		if(!$stmt->prepare($query))
		{
			die("Failed to prepare statement: ". $this->connection->error);
		}
		else
		{
			if (count($params) > 1)
				call_user_func_array(array($stmt, 'bind_param'), $params);
			
			if(!$stmt->execute()) die($stmt->error);
		}

		$this->initialize();

		return $this;
	}


	public function update($table, $data)
	{
		$where = (!empty($this->where_data)) ? ' WHERE '.$this->where_data : '';

		$sets = array(); $sets_val = array();
		foreach ($data as $key => $value) {
			$sets[] = $key.'=?';
			$sets_val[] = $value;
		}

		$this->bind_data = array_merge($sets_val, $this->bind_data);

		$query = "UPDATE ".$table." SET ".implode(', ', $sets).$where;

		$params = array(str_repeat("s", count($this->bind_data)));
		foreach ($this->bind_data as $key => $v) $params[] = &$this->bind_data[$key];

		//var_dump($query);

		$stmt = $this->connection->stmt_init();
		if(!$stmt->prepare($query))
		{
			die("Failed to prepare statement: ". $this->connection->error);
		}
		else
		{
			if (count($params) > 1)
				call_user_func_array(array($stmt, 'bind_param'), $params);
			
			if(!$stmt->execute()) die($stmt->error);
		}

		$this->initialize();

		return $this;
	}


	public function insert($table, $data)
	{

		$insert_q = array();
		$this->bind_data = array();
		foreach ($data as $key => $value) {
			$insert_q[] = '?';
			$this->bind_data[] = $value;
		}

		$query = "INSERT INTO ".$table." (".implode(', ', array_keys($data)).") VALUES (".implode(', ', $insert_q).')';

		$params = array(str_repeat("s", count($this->bind_data)));
		foreach ($this->bind_data as $key => $v) $params[] = &$this->bind_data[$key];

		//var_dump($query);

		$stmt = $this->connection->stmt_init();
		if(!$stmt->prepare($query))
		{
			die("Failed to prepare statement: ". $this->connection->error);
		}
		else
		{
			if (count($params) > 1)
				call_user_func_array(array($stmt, 'bind_param'), $params);
			
			if(!$stmt->execute()) die($stmt->error);
		}

		$this->initialize();

		return $this;
	}

	public function query($query)
	{
		$this->query_result = mysqli_query($this->connection, $query);

		return $this;
	}

	public function result()
	{
		$res = array();
		while($row = $this->query_result->fetch_object()) {
			$res[] = $row;
		}

		return $res;
	}

	public function result_array()
	{
		$res = array();
		while($row = $this->query_result->fetch_array(MYSQLI_ASSOC)) {
			$res[] = $row;
		}

		return $res;
	}

	public function row()
	{
		return $this->query_result->fetch_object();
	}

	public function row_array()
	{
		return $this->query_result->fetch_array(MYSQLI_ASSOC);
	}

	public function num_rows()
	{
		return $this->query_result->num_rows;
	}

	public function last_insert_id()
	{
		return $this->query_result->insert_id;
	}

	public function close()
	{
		return $this->connection->close();
	}

	public function escape_string($str)
	{
		return $this->connection->real_escape_string($str);
	}
}

