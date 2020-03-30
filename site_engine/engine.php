<?

class engine
{
	// "Поддвижок" для обработки каждой из страниц
	public $page_engine;
	// Соддержимое тега body
	public $body;
	// Соддержимое тега head
	public $head;
	
	public $block_site = false; // блокировка сайта
	public $block_whitelist = array(); // список разрешенных ip при заблокированном сайте
	
	public $account_system_salt = "MtIWebzsEjfXriFU";
	public $rcon_password = "rcon";
	
	public $fraction_name;
	public $rang_name;
	public $sub_rang_name;

	public $ucp_main;

	// Ошибки
	public $auth_error = [];
	public $password_change_error = [];
	public $email_change_error = [];
	public $donate_error = [];

	// Составляем содержимое тега head
	public function compile_head(){
		$this->add_to_head('
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<link rel="stylesheet" href="../fonts/fonts.css">
		<link rel="stylesheet" href="../css/style.css">
		<title>GreenTech Roleplay</title>
		');
		
		return $this->head;
	}

	// Составляем содержимое тега body
	public function compile_body($page){

		// Активация блокировки если это необходимо
		if($this->block_site && !in_array($_SERVER['REMOTE_ADDR'], $this->block_whitelist)){
			$this->add_to_body('
				<center style="width: 100%; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); padding: 50px;">
				<h1 style="font-size: 5em; font-family: Proxima Nova Th; font-weight: bold">Сайт на технических работах</h4>
				<p style="font-size: 3em; font-family: Proxima Nova Th;">В настоящие время данный сайт доступен только для указанных IP-адресов. Возможно, он находится на рекострукции или технических работах. Пожалуйста, подождите, возможно скоро он снова будет доступен.</p>
				</center>');

			return $this->body;
		}

		// Обработка каждой из страниц
		if($page == "MAIN"){
			$this->page_engine = new Main();
			$this->page_engine->catch_auth_error();
			$this->page_engine->compile();
			$this->add_to_body($this->page_engine->get_html());
		}
		if($page == "DONATE"){
			$this->page_engine = new Donate();
			$this->page_engine->catch_auth_error();
			$this->page_engine->catch_donate_error();
			$this->page_engine->compile();
			$this->add_to_body($this->page_engine->get_html());
		}
		if($page == "UCP"){
			$this->page_engine = new UCP();
			$this->page_engine->change_email();
			$this->page_engine->change_password();
			$this->page_engine->compile();
			$this->add_to_body($this->page_engine->get_html());
		}

		return $this->body;
	}

	public function add_to_body($data){
		$this->body .= $data;
	}

	public function add_to_head($head){
		$this->head .= $head;
	}

	public function unitpay_handler($server_id){
		$this->db_connect($server_id);

		if(empty($_GET['method']) || empty($_GET['params']) || !is_array($_GET['params']))
		{
			return $this->unitpay_gen_error("invalid query");
		}

		$method = $_GET['method'];
		$params = $_GET['params'];

		$secret_key = $this->server_data[$server_id]['unitpay_secret_key'];

		if($params['signature'] != $this->unitpay_gen_sha256_sig($method, $params, $secret_key))
		{
			return $this->unitpay_gen_error("invalid signature");
		}

		if($method == "check")
		{
			if($this->unitpay_get_payment($params['unitpayId']))
			{
				return $this->unitpay_gen_error("payment already exists");
			}

			if(!$this->unitpay_create_payment($params['unitpayId'], $params['account'], floor($params['sum']), floor($params['sum'])))
			{
				return $this->unitpay_gen_error('unable to create payment database');
			}

			if(!$this->unitpay_is_account_exists($params['account']))
			{
				return $this->unitpay_gen_error('character not found');
			}

			return $this->unitpay_gen_success('CHECK is successful');
		}

		if($method == "pay")
		{
			$payment = $this->unitpay_get_payment($params['unitpayId']);

			if($payment && $payment->status == 1)
			{
				return $this->unitpay_gen_success('payment has already been paid');
			}

			if(!$this->unitpay_confirm_payment($params['unitpayId']))
			{
				return $this->unitpay_gen_error('unable to confirm payment database');
			}

			$this->db->query('
				UPDATE players SET
				Donate = Donate + '.(((int)floor($params['sum'])) * $this->donate_multiplier).'
				WHERE Names = \''.$this->db->real_escape_string($params['account']).'\'
				');

			return $this->unitpay_gen_success('PAY is successful');
		}

		return $this->unitpay_gen_error($method." not supported");
	}

	public function unitpay_gen_success($message){
		return json_encode(array(
			"jsonrpc" => "2.0",
			"result" => array(
				"message" => $message
			),
			'id' => 1,
		));
	}

	public function unitpay_gen_error($message){
		return json_encode(array(
			"jsonrpc" => "2.0",
			"error" => array(
				"code" => -32000,
				"message" => $message
			),
			'id' => 1
		));
	}

	public function unitpay_gen_sha256_sig($method, array $params, $secretKey){
		$delimiter = '{up}';

		ksort($params);
		unset($params['sign']);
		unset($params['signature']);

		return hash('sha256', $method.$delimiter.join($delimiter, $params).$delimiter.$secretKey);
	}

	public function unitpay_get_payment($unitpayId){ 
		$result = $this->db->query('SELECT * FROM unitpay_payments WHERE unitpayId = \''.$unitpayId.'\' LIMIT 1');

		return $result->fetch_object();
	}

	public function unitpay_create_payment($unitpayId, $account, $sum, $itemsCount){
		$query = '
		INSERT INTO
		unitpay_payments (unitpayId, account, sum, itemsCount, dateCreate, status)
		VALUES
		(
		"'.$this->db->real_escape_string($unitpayId).'",
		"'.$this->db->real_escape_string($account).'",
		"'.$this->db->real_escape_string($sum).'",
		"'.$this->db->real_escape_string($itemsCount * $this->donate_multiplier).'",
		NOW(),
		0
		)
		';

		return $this->db->query($query);
	}

	public function unitpay_is_account_exists($account){
		$result = $this->db->query('SELECT * FROM players WHERE Names = \''.$this->db->real_escape_string($account).'\' LIMIT 1');

		if($result->num_rows > 0)
		{
			return true;
		}

		return false;
	}

	public function unitpay_confirm_payment($unitpayId){
		$query = '
		UPDATE unitpay_payments SET
		status = 1,
		dateComplete = NOW()
		WHERE
		unitpayId = "'.$this->db->real_escape_string($unitpayId).'"
		LIMIT 1
		';

		return $this->db->query($query);
	}

	public function wlog($action, $params){
		$this->db->query('INSERT INTO `ucp_log`(`ip`, `ts`, `action`, `params`) VALUES(\''.$_SERVER['REMOTE_ADDR'].'\', \''.time().'\', \''.$action.'\', \''.json_encode($params).'\')');
		return 1;
	}

	public function rcon($server_id, $command){
		$ipdata = explode(":", $this->server_data[$server_id]['ip']);

		$ip = $ipdata[0];
		$port = strval($ipdata[1]);

		$sock = fsockopen('udp://'.$ip, $port, $iError, $sError, 2);

		if(!$sock)
		{
			return false;
		}

		socket_set_timeout($sock, 2);

		$packet = 'SAMP';
		$packet .= chr(strtok($ip, '.'));
		$packet .= chr(strtok('.'));
		$packet .= chr(strtok('.'));
		$packet .= chr(strtok('.'));
		$packet .= chr($port & 0xFF);
		$packet .= chr($port >> 8 & 0xFF);
		$packet .= 'p4150';

		fwrite($sock, $packet);

		if(fread($sock, 10))
		{
			if(fread($sock, 5) == 'p4150')
			{
				$packet = 'SAMP';
				$packet .= chr(strtok($ip, '.'));
				$packet .= chr(strtok('.'));
				$packet .= chr(strtok('.'));
				$packet .= chr(strtok('.'));
				$packet .= chr($port & 0xFF);
				$packet .= chr($port >> 8 & 0xFF);
				$packet .= 'x';

				$packet .= chr(strlen($this->rcon_password) & 0xFF);
				$packet .= chr(strlen($this->rcon_password) >> 8 & 0xFF);
				$packet .= $this->rcon_password;
				$packet .= chr(strlen($command) & 0xFF);
				$packet .= chr(strlen($command) >> 8 & 0xFF);
				$packet .= $command;

				fwrite($sock, $packet);

				$retarray = array();
				$mctime = microtime(true) + 1.0;

				while(microtime(true) < $mctime)
				{
					$tmp = substr(fread($sock, 128), 13);

					if(strlen($tmp))
					{
						$retarray[] = $tmp;
					}
					else
					{
						break;
					}
				}

				return $retarray;
			}
		}

		return false;
	}
}

?>
