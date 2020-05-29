<?php

class AJAX{

	public $db;
	public $server_data;
	public $fraction_names;
	public $rang_names;
	public $sub_rang_names;

	public function __construct(){
		if(isset($_POST['type'])){
			session_start();
			require_once("../config/server_config.php");
			require_once("../config/fractions.php");
			$this->server_data 		= $server_config;
			$this->fraction_names = $fractions;
			$this->rang_names 		= $rangs;
			$this->sub_rang_names = $sub_rangs;
			$this->db_connect();
			if($_POST['type'] == "get_tools")	$this-> compile_tools_modal();
			if($_POST['type'] == "raise") 		$this->raise();
			if($_POST['type'] == "lower") 		$this->lower();
			if($_POST['type'] == "fire") 			$this->fire();
			if($_POST['type'] == 'blacklist')	$this->blacklist();
			if($_POST['type'] == "doprangchange") $this->changeDopRang();
		} else echo "error";
	}

	public function checkUsers($session_user, $query_user){
		$error = false;
		$session_user = $this->db_get($_SESSION['username']);
		$query_user = $this->db_get($_POST['username']);
		if(!$session_user || !$query_user) $error = true;
		elseif($session_user['Leader'] <= 0) $error = true;
		elseif($session_user['Leader'] != $query_user['Member']) $error = true;
		elseif($query_user['Leader'] == $session_user['Leader']) $error = true;
		return $error;
	}

	public function compile_tools_modal(){

		$session_user = $this->db_get($_SESSION['username']);
		$query_user = $this->db_get($_POST['username']);

		if($this->checkUsers($session_user, $query_user)) echo "error";
		else {
			$fraction 	= $query_user['Member'];
			$doprang 		= $query_user['DopRank'];
			$current_sub_rang = $this->sub_rang_names[$fraction][$doprang];
			echo '<div class="close" onclick="CloseModals();">
			<span></span>
			<span></span>
			</div><h1>Действия с игроком <br><b>' . $query_user['Names'] . '</b></h1>
			<div class="rang">
			<p>Изменить ранг</p>
			<button class="rise" onclick="Raise(this);">Повысить</button>
			<button class="lower" onclick="Lower(this);">Понизить</button>
			</div>
			<div class="doprang">
			<p>Выбрать подразделение</p>
			<div class="select" onclick="OpenCloseDopRang(this);">
			<div class="current">' . $current_sub_rang . '</div>
			<ul class="others" style="opacity: 0">';
			$sub_rangs 	= $this->sub_rang_names[$fraction];

			foreach($sub_rangs as $sub_rang){
				if($sub_rang != $current_sub_rang) echo '<li onclick="SelectDopRang(this);">' . $sub_rang . '</li>';
			}
			echo '</ul>
			</div>
			</div>
			<div class="other">
			<p>Другое</p>
			<button onclick="Fire(this);">Уволить</button>
			<button onclick="BlackList(this); Fire(this);">Уволить + занести в ЧС</button>
			</div>';
		}

	}

	public function raise(){

		$session_user = $this->db_get($_SESSION['username']);
		$query_user = $this->db_get($_POST['username']);

		if($this->checkUsers($session_user, $query_user)) echo "error";
		else {
			$fraction 				= $query_user['Member'];
			$current_rang 		=	$query_user['Rank'];
			$current_sub_rang = $query_user['DopRank'];
			$max_rang 				= count($this->rang_names[$fraction]) - 1;
			$new_rang					= $current_rang + 1;

			if($new_rang > $max_rang) $new_rang = $max_rang;

			$new_rang_name = $this->rang_names[$fraction][$new_rang];
			$current_sub_rang_name = $this->sub_rang_names[$fraction][$current_sub_rang];

			$result = $new_rang_name . '[' . $current_sub_rang_name . ']';

			$this->db_set($_POST['username'], "Rank", $new_rang);

			echo $result;
		}
	}

	public function lower(){

		$session_user = $this->db_get($_SESSION['username']);
		$query_user = $this->db_get($_POST['username']);

		if($this->checkUsers($session_user, $query_user)) echo "error";
		else {
			$fraction 				= $query_user['Member'];
			$current_rang 		=	$query_user['Rank'];
			$current_sub_rang = $query_user['DopRank'];
			$new_rang					= $current_rang - 1;

			if($new_rang < 0) $new_rang = 0;

			$new_rang_name = $this->rang_names[$fraction][$new_rang];
			$current_sub_rang_name = $this->sub_rang_names[$fraction][$current_sub_rang];

			$result = $new_rang_name . '[' . $current_sub_rang_name . ']';

			$this->db_set($_POST['username'], "Rank", $new_rang);

			echo $result;
		}
	}

	public function changeDopRang(){

		$session_user = $this->db_get($_SESSION['username']);
		$query_user = $this->db_get($_POST['username']);

		if($this->checkUsers($session_user, $query_user)) echo "error";
		else {
			$username 				= $_POST['username'];
			$fraction 				= $query_user['Member'];
			$new_doprang_name = $_POST['value'];
			$new_doprang 			= array_keys($this->sub_rang_names[$fraction], $new_doprang_name)[0];

			$this->db_set($username, "DopRank", $new_doprang);
			echo "OK";
		}

	}

	public function fire(){

		$session_user = $this->db_get($_SESSION['username']);
		$query_user = $this->db_get($_POST['username']);

		if($this->checkUsers($session_user, $query_user)) echo "error";
		else {
			$username = $_POST['username'];
			$this->db_set($username, "Rank", 0);
			$this->db_set($username, "Member", 0);
			$this->db_set($username, "DopRank", 0);
			echo true;
		}
	}

	public function blacklist(){

		$session_user = $this->db_get($_SESSION['username']);
		$query_user = $this->db_get($_POST['username']);

		if($this->checkUsers($session_user, $query_user)) echo "error";
		else {
			$username = $_POST['username'];
			$this->db_set($username, 'BlackList', 1);
			$this->db_set($username, 'BlackListDay', date("m.d.y"));
			echo "OK";
		}
	}

	// Подключение к БД
	public function db_connect(){
		$this->db = @new mysqli(
			$this->server_data['db_hostname'],
			$this->server_data['db_username'],
			$this->server_data['db_password'],
			$this->server_data['db_database']
		);

		if (mysqli_connect_errno()){
			die("failed to connect database");
		}

		$this->db->query("SET NAMES cp1251;");
		$this->db->query("SET SESSION character_set_server = 'utf8';");
	}

	// Получить всего пользователя из БД
	public function db_get($username){
		$result = $this->db->query("SELECT * FROM `players` WHERE `Names` = '$username'");
		$result = mysqli_fetch_assoc($result);
		return $result;
	}

	// Изменить свойство пользователя
	public function db_set($username, $col, $value){
		$this->db->query("UPDATE `players` SET `" . $col . "` = '" . $value . "' WHERE `Names` = '" . $username . "'");
	}
}

$ajax = new AJAX();

?>