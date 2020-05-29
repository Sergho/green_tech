<?php

class UCP{

	public $db;

	public $server_data;
	public $fraction_names;
	public $rang_names;
	public $sub_rang_names;
	public $ucp_main_ooc;
	public $ucp_main_ic;
	public $account_system_salt = "MtIWebzsEjfXriFU";

	public $password_change_error = [];
	public $email_change_error = [];

	public $session_flag;
	public $help;

	public $html;

	// Конструктор
	public function __construct(){
		// Подключаем config файлы
		require_once("./config/server_config.php");
		require_once("./config/fractions.php");
		require_once("./config/ucp_main.php");
		// Применяем конфиги
		$this->server_data 		= $server_config;
		$this->fraction_names = $fractions;
		$this->rang_names 		= $rangs;
		$this->sub_rang_names = $sub_rangs;
		$this->ucp_main_ooc 	= $ucp_main_ooc_params;
		$this->ucp_main_ic		= $ucp_main_ic_params;
		// Подключаемся к БД
		$this->db_connect();
	}

	// Функция смены пароля
	public function change_password(){
		// Проверяем форму изменения пароля если она задействована
		if(isset($_POST['old_password']) && isset($_POST['new_password']) && isset($_POST['password_confirm'])){
			// Записываем всё что нужно в переменные
			$old 			= $_POST['old_password'];
			$new 			= $_POST['new_password'];
			$confirm 	= $_POST['password_confirm'];
			$username = $_SESSION['username'];

			// 1 шаг - проверка форм на валидность
			if(strlen($old) < 1 || strlen($new) < 1 || strlen($confirm) < 1) $this->password_change_error[] = "Заполните все поля";
			if($new != $confirm) $this->password_change_error[] = "Пароли не совпадают";

			if(!$this->password_change_error){
				// 2 шаг - проверка совместимости старого пароля с текущем паролем из БД

				// Подключаемся к БД
				$this->db_connect();

				// Пароль и "Соль" из БД
				$db_password 	= $this->db_get('players', 'Pass', $username);
				$db_salt			= $this->db_get('players', 'salt', $username);

				// Проверяем
				if($this->get_password_hash($old, $db_salt) != $db_password) $this->password_change_error[] = "Старый пароль введен неверно";
			}
			if(!$this->password_change_error){
				// 3 шаг - собственно сама смена пароля

				// "Соль" из БД
				$db_salt = $this->db_get('players', 'salt', $username);

				// Хеш нового пароля
				$hash = $this->get_password_hash($new, $db_salt);

				// Записываем данные в логи
				$this->ucp_log("password_changed", ["ID" => $_SESSION['ID'], "Username" => $_SESSION['username'], "Server_id" => 1]);

				// Меняем пароль
				$this->db_set('Pass', $hash, $username);
			}
		}
	}

	// Функция смены мыла
	public function change_email(){
		// От пользователя поступил запрос на смену почты
		if(isset($_POST['email_change'])){
			// Записываем все в переменные
			$new_email = $_POST['new_email'];
			if(!filter_var($new_email, FILTER_VALIDATE_EMAIL)) $this->email_change_error[] = "Некорректо введён адрес почты";

			if(!$this->email_change_error){
				// Ключ подтверждения смены почты
				$submit_key = $this->generate_key(24);
				// Ссылка для подтверждения
				$link = $this->server_data['address'] . '/ucp.php?email_change=' . $submit_key;

				// Чтобы потом воспользоваться при переходе по ссылке из письма
				$_SESSION['email_submit_key'] = $submit_key;
				$_SESSION['new_email'] = $new_email;

				// Формируем сообщение
				$message = "Здравствуйте!\n\r\n\r";
				$message .= "Вы запросили изменение e-mail к аккаунту ".$_SESSION['username'] . " на сервере GreenTech RolePlay #1, перейдите по " . $link . " для дальнейших действий.\n\r";
				$message .= "Если вдруг вы не запрашивали это, проигнорируйте это письмо.\n\r\n\r";
				$message .= "С Уважением, администрация GreenTech RolePlay.";
				// Отправить письмо
				mail("sergche04@gmail.com", "Изменение Email", $message, "From: admin@greentech-rp.ru");
			}
		}
		// Пользователь перешел по ссылке восстановления почты
		if(isset($_GET['email_change'])){
			// Ключ подтверждения смены почты из GET
			$submit_key_get = $_GET['email_change'];
			// Ключ подтверждения смены почты из сессии
			$submit_key_session  = $_SESSION['email_submit_key'];

			if($submit_key_get == $submit_key_session){
				$this->db_set("Email", $_SESSION['new_email'], $_SESSION['username']);
				
				unset($_SESSION['new_email']);
				unset($_SESSION['email_submit_key']);

				// Записываем данные в логи
				$this->ucp_log("email_changed", ["ID" => $_SESSION['ID'], "Username" => $_SESSION['username'], "Server_id" => 1]);

				header("Location: ../ucp.php");
			}
		}
	}

	// Составляем страницу личного кабинета
	public function compile(){
		$this->compile_darkness();
		$this->compile_logo();
		$this->compile_nav();

		$this->log_out_check();

		// Если активирована сессия, то отрисовываем контент ЛК
		if($this->session_flag && $this->help == ""){
			// Проверяем на сброс места спавна
			$this->spawn_reset_check();

			// Отрисовываем контент ЛК

			$this->compile_main();
			$this->compile_property();
			$this->compile_payments();
			$this->compile_leaders();
			$this->compile_skins();
			$this->compile_help();
			$this->compile_leader();
		}
		$this->add_to_html('
			</div>
			</header>');

		$this->compile_password_change_modal();
		$this->compile_password_change_success_modal();
		$this->compile_email_change_modal();
		$this->compile_email_change_success_modal();
		$this->compile_footer();

		$this->compile_scripts();
	}

	// Показываем затемноение при необходимости
	public function compile_darkness(){
		$this->add_to_html('
			<div id="ucp-wrapper">
			<div class="darkness" onclick="DropdownToggle(this); CloseModals();" ');

		if(isset($_POST['password_change']) || isset($_POST['email_change'])) $this->add_to_html('style="display: block; opacity: 1;"></div>');
		else $this->add_to_html('></div>');
	}

	// Логотип сайта
	public function compile_logo(){
		$this->add_to_html('
			<header>
			<div class="logo">
			<a href="main.php">
			<img src="../img/logo.png" alt="logotype">
			</a>
			</div>');
	}

	// Навигация
	public function compile_nav(){
		$this->add_to_html('<div class="nav">');
		$this->compile_dropdown();
		$this->compile_navbar();
		$this->add_to_html('</div>');
	}

	// Выпадающее меню
	public function compile_dropdown(){
		$this->add_to_html('
			<div class="dropdown">
			<div class="dropdown-burger" onclick="DropdownToggle(this);">
			<span></span>
			<span></span>
			<span></span>
			</div>
			<ul class="dropdown-menu">
			<li class="dropdown-item"><a href="main.php">Главная</a></li>
			<li class="dropdown-item"><a href="#">Ставки BoxBet</a></li>
			<li class="dropdown-item"><a href="donate.php">Донат</a></li>
			<li class="dropdown-item"><a href="#">Gregtech FM</a></li>
			<li class="dropdown-item"><a href="#">Форум</a></li>
			<li class="dropdown-item lc">');

		// Устанавливаем ссылку в кнопке ЛК в зависиммости от сессии (Выпадающее меню)
		if(isset($_SESSION['username'])) $this->add_to_html('<a href="./ucp.php">' . $_SESSION['username'] . '</a>');
		else $this->add_to_html('<a href="#" onclick="DropdownToggle(this); setTimeout(() => {Auth();}, 550);">Личный кабинет</a>');

		$this->add_to_html('</li></ul></div>');
	}

	// Навбар
	public function compile_navbar(){
		$this->add_to_html('
			<div class="navbar">
			<ul class="navbar-menu">
			<li class="navbar-item"><a href="main.php">Главная</a></li>
			<li class="navbar-item"><a href="#">Ставки BoxBet</a></li>
			<li class="navbar-item"><a href="donate.php">Донат</a></li>
			<li class="navbar-item"><a href="#">Gregtech FM</a></li>
			<li class="navbar-item"><a href="#">Форум</a></li>
			</ul>
			<div class="navbar-personal">');

		// Устанавливаем ссылку в кнопке ЛК в зависиммости от сессии (Просто меню)
		if(isset($_SESSION['username'])) $this->add_to_html('<a href="./ucp.php" class="lc">' . $_SESSION['username'] . '</a>');
		else $this->add_to_html('<a href="#" class="lc" onclick="Auth()">Личный кабинет</a>');

		$this->add_to_html('</div></div>');
	}

	// Главная вкладка
	public function compile_main(){
		$username = $_SESSION['username'];
		$response = $this->get_main_response();
		$this->add_to_html('<div class="text">
			<h1>Личный кабинет ' . $username . '</h1>
			<div class="stats">
			<h1>Статистика</h1>
			<ul class="pages-nav">
			<li class="active" onclick="PageSwitch(this);">Главное</li>
			<li class="" onclick="PageSwitch(this);">Имущество</li>
			<li class="" onclick="PageSwitch(this);">Платежи</li>
			<li class="" onclick="PageSwitch(this);">Лидеры</li>
			</ul>
			<ul class="pages">
			<li class="page main-page current">');

		$this->compile_ooc($response);
		$this->compile_ic($response);
		$this->add_to_html('<div class="help">
			<button onclick="OpenPasswordChangeMenu();">Сменить пароль</button>
			<button onclick="OpenEmailChangeMenu();">Сменить Email</button>
			</div>
			</li>');
	}

	// Вкладка Имущество
	public function compile_property(){
		$this->add_to_html('<li class="page property-page">
			<ul class="houses">
			<span>Список домов</span>
			<div class="empty">Дома отсутствуют</div>
			</ul>
			<ul class="businesses">
			<span>Список бизнесов</span>
			<div class="empty">Бизнесы отсутствуют</div>
			</ul>
			<ul class="cars">
			<span>Список автомобилей</span>
			<div class="empty">Автомобили отсутствуют</div>
			</ul>
			</li>');
	}

	// Выводим данные ooc
	public function compile_ooc($response){
		$username = $_SESSION['username'];

		$this->add_to_html('<ul class="ooc"><h2>OOC Информация</h2>');

		foreach ($this->ucp_main_ooc as $key => $value){
			if(substr($value, 0, 3) == "db_") $value = $response[substr($value, 3)];
			if($value == "username") $value = $username;
			$this->add_to_html('<li><div class="param">' . $key . ':</div>');
			$this->add_to_html('<div class="value">' . $value . '</div>');
			if($key == "Донат-очки") $this->add_to_html('<form action="./donate.php" method="GET"><button class="donate-btn"><span></span><span></span></button></form>');
			if($key == "Место появления") $this->add_to_html('<form action="./ucp.php" method="POST"><button name="spawn_reset" class="spawn_reset"><img src="./img/reset.svg" alt=""></button></form>');
			$this->add_to_html('</li>');
		}

		$this->add_to_html('</ul>');
	}

	// Выводим данные ic
	public function compile_ic($response){

		$username = $_SESSION["username"];

		$this->add_to_html('<ul class="ic">
			<h2>IC Информация</h2>');
		foreach($this->ucp_main_ic as $param => $value){
			if(substr($value, 0, 3) == "db_") $value = $response[substr($value, 3)];
			$this->add_to_html('<li>');
			$this->add_to_html('<div class="param">' . $param . '</div>');
			$this->add_to_html('<div class="value">' . $value . ' </div>');
			$this->add_to_html('</li>');
		}
		$this->add_to_html("</ul>");
	}

	// Выводим данные о платежах
	public function compile_payments(){
		$response = $this->get_payments();
		$this->add_to_html('<li class="page payments-page">
			<div class="sort">
			<p>Сортировать:<span class="enabled" onclick="ActivateFilter(this);">Сначала новые</span>/<span class="" onclick="ActivateFilter(this);">Сначала старые</span></p>
			</div>
			<div class="slider">
			<ul class="slides">
			<li class="slide" style="display: block; opacity: 1;">
			<ul>');
		foreach($response as $key => $payment){
			if($key % 17 == 0 && $key != 0) $this->add_to_html('</ul>
				</li>
				<li class="slide" style="display: none; opacity: 0;"><ul>');

			$num = $key + 1;
			$date = explode(" ", $payment['dateComplete'])[0];
			$date = explode("-", $date);
			$date = implode(".", array_reverse($date));
			$time = explode(" ", $payment['dateComplete'])[1];
			$time = substr($time, 0, 5);

			$this->add_to_html('<li>' . $num . '. Пополнение счёта ' . $date . " (" . $time . ') <span class="sum">+' . $payment['sum'] . "RUB</span></li>");
		}
		$this->add_to_html('</ul></li></ul><div class="prev disabled" onclick="SliderPrev(this);"></div>
			<p class="current">1</p>
			<div class="next ');
		if(count($response) <= 17) $this->add_to_html('disabled');
		$this->add_to_html('" onclick="SliderNext(this);"></div><a href="donate.php">Пополнить баланс</a>
			</div>
			</li>');
	}

	// выводим данные о лидерах организаций
	public function compile_leaders(){
		$response = $this->get_leaders();
		$this->add_to_html('<li class="page leaders-page">
			<div class="slider">
			<ul class="slides">
			<li class="slide" style="display: flex; opacity: 1;">
			<div class="h">
			<div class="nick">Nick_Name</div>
			<div class="organization">Организация</div>
			<div class="last-enter">Последний вход</div>
			</div>
			<ul>');
		foreach($response as $leader){
			$online = $leader['Online'] == 1 ? "online" : "";
			$this->add_to_html('<li><div class="nick ' . $online . '">' . $leader['Names'] . '</div>');
			$this->add_to_html('<div class="organization">' . $this->fraction_names[$leader['Job']] . '</div>');
			$this->add_to_html('<div class="last-enter">' . $leader['pDay'] .'</div></li>');
		}
		$this->add_to_html('</ul></li></ul>
			<div class="prev disabled" onclick="SliderPrev(this);"></div>
			<div class="current">1</div>
			<div class="next ');
		if(count($response) <= 17) $this->add_to_html("disabled");
		$this->add_to_html('" onclick="SliderNext(this);"></div>
			</div>
			</li>
			</ul>
			</div>');
	}

	// Выводим скины
	public function compile_skins(){
		$response = $this->get_main_response();
		$this->add_to_html('<div class="skin">
			<div class="slider">
			<ul class="slides">
			<li class="slide" style="display: none; opacity: 0;">
			<h1>Скин (обычный)</h1>
			<p>' . $response['Char'] . 'id</p>
			<img src="../img/skins/default/skin' . $response['Char'] . '.png" alt="skin">
			</li>
			<li class="slide" style="display: block; opacity: 1;">
			<h1>Скин (мод-пак)</h1>
			<p>' . $response['Char'] . 'id</p>
			<img src="../img/skins/pack/skin' . $response['Char'] . '.png" alt="skin">
			</li>
			</ul>
			<div class="prev" onclick="SliderPrev(this);"></div>
			<p class="current" style="display: none">2</p>
			<div class="next disabled" onclick="SliderNext(this);"></div>
			</div>
			</div>');
	}

	// Кнопки помощи
	public function compile_help(){
		$this->add_to_html('<div class="help">
			<button onclick="OpenPasswordChangeMenu();">Сменить пароль</button>
			<button onclick="OpenEmailChangeMenu();">Сменить Email</button>
			<a href="./ucp.php?help=log_out">Выйти</a>
			</div>');
	}

	// Инструменты лидера организации
	public function compile_leader(){

		$username 	= $_SESSION['username'];
		$db_leader 	= $this->db_get("players", "Leader", $username);

		if($db_leader > 0){
			$this->add_to_html('<div class="leader">
				<h1>Управление фракцией <br>"<b>');
			$this->add_to_html($this->fraction_names[$db_leader]);
			$this->add_to_html('</b>"</h1>
				<p>Данная панель предназначена для руководителей фракций и их заместителей. Она создана для облегчённого взаимодействия с персоналом. В ней доступно повышение и понидение звания (ранга), изменение должности и увольнение сотрудников.</p>
				<div class="leaders">
				<div class="h">
				<div class="nick">Игровой ник</div>
				<div class="last-seen">Последний вход</div>
				<div class="rank">Звание[Подразделение]</div>
				</div>');

			// Участники фракции
			$participants = $this->get_participants($db_leader);

			$this->add_to_html('<ul>');

			foreach($participants as $participant){
				$this->add_to_html("<li onclick='OpenTools(this);'>");
				// Select
				if($participant['Leader'] == $db_leader) $this->add_to_html('<div class="select disabled">');
				else $this->add_to_html('<div class="select">');
				$this->add_to_html('<span class="visible">
					<span class="outer"></span>
					<span class="inner"></span>
					</span>
					</div>');
				$this->add_to_html('<div class="info">');
				// Nickname
				$this->add_to_html('<div class="nick ');
				if($participant['Online'] == 1) $this->add_to_html("online");
				$this->add_to_html('">');
				$this->add_to_html($participant['Names']);
				$this->add_to_html("</div>");
				// Last seen
				$this->add_to_html('<div class="last-seen">');
				$this->add_to_html($participant['pDay']);
				$this->add_to_html('</div>');
				// Rang & SubRang
				$fraction_index = $participant['Member'];
				$rang_index 		= $participant['Rank'];
				$sub_rang_index = $participant['DopRank'];
				$rang = $this->rang_names[$fraction_index][$rang_index];
				$sub_rang = $this->sub_rang_names[$fraction_index][$sub_rang_index];
				$this->add_to_html('<div class="rank">');
				$this->add_to_html($rang . '[' . $sub_rang . ']');
				$this->add_to_html('</div>');
				$this->add_to_html("</li>");
			}
			$this->add_to_html('</div>');
			$this->add_to_html('</ul>');
		}
	}

	// Окно смены пароля
	public function compile_password_change_modal(){
		if($this->password_change_error) $this->add_to_html('
			<div id="password_change" style="display: block; opacity: 1;">');
		else $this->add_to_html('<div id="password_change" style="display: none; opacity: 0;">');
		$this->add_to_html('<div class="exit" onclick="CloseModal(this);">
			<span></span>
			<span></span>
			</div>
			<h1>Смена пароля</h1>
			<p class="error">');
		// Добавляем ошибку при ее наличии
		if($this->password_change_error) $this->add_to_html($this->password_change_error[0]);
		else $this->add_to_html("&nbsp;");
		$this->add_to_html('</p>
			<form action="./ucp.php" method="POST">
			<input type="password" name="old_password" placeholder="Введите старый пароль">
			<input type="password" name="new_password" placeholder="Введите новый пароль">
			<input type="password" name="password_confirm" placeholder="Повторите новый пароль">
			<img src="../img/eye.png" alt="eye" class="show" onclick="ShowPassword(this, 0);">
			<img src="../img/eye.png" alt="eye" class="show" onclick="ShowPassword(this, 1);">
			<img src="../img/eye.png" alt="eye" class="show" onclick="ShowPassword(this, 2);">
			<button type="submit" name="password_change">Сменить пароль</button>
			</form>
			</div>');
	}

	// Окно смены пароля (Успех)
	public function compile_password_change_success_modal(){
		if(!$this->password_change_error && isset($_POST['old_password'])) $this->add_to_html('<div id="success" style="display: block; opacity: 1;">
			<div class="exit" onclick="CloseModal(this);">
			<span></span>
			<span></span>
			</div>
			<img src="../img/success.png" alt="success">
			<h1>Пароль сменён успешно</h1>
			<p>Структура политической науки, как бы это ни казалось парадоксальным, представляет собой гарант. Мажоритарная избирательная система противоречива</p>
			</div>');
	}

	// Окно смены мыла
	public function compile_email_change_modal(){
		if(!empty($this->email_change_error)) $this->add_to_html('<div id="email_change" style="display: block; opacity: 1;">');
		else $this->add_to_html('<div id="email_change" style="display: none; opacity: 0;">');
		$this->add_to_html('<div class="exit" onclick="CloseModal(this);">
			<span></span>
			<span></span>
			</div>
			<h1>Смена E-mail</h1>
			<p class="error">');
		if(!empty($this->email_change_error)) $this->add_to_html($this->email_change_error[0]);
		else $this->add_to_html('&nbsp;');
		$this->add_to_html('</p>
			<form action="./ucp.php" method="POST">
			<input type="text" name="new_email" placeholder="Введите вашу почту"');
		if(!empty($this->email_change_error)) $this->add_to_html('value="' . $_POST['new_email'] . '"');
		$this->add_to_html('>
			<button type="submit" name="email_change">Отправить письмо</button>
			</form>
			</div>');
	}

	// Окно смены мыла (Успех)
	public function compile_email_change_success_modal(){
		if(isset($_POST['new_email']) && !$this->email_change_error) $this->add_to_html('<div id="success" style="display: block; opacity: 1;">
			<div class="exit" onclick="CloseModal(this);">
			<span></span>
			<span></span>
			</div>
			<img src="../img/mail.png" alt="success">
			<h1>Письмо с подтверждением отправлено</h1>
			<p>Структура политической науки, как бы это ни казалось парадоксальным, представляет собой гарант. Мажоритарная избирательная система противоречива</p>
			</div>');
	}

	// Скрипты
	public function compile_scripts(){
		$this->add_to_html('
			<script src="../js/jquery.js"></script>
			<script src="../js/script.js"></script>');
	}

	// Запрет прокрутки страницы при особых условиях
	public function check_deny_body_overflow(){
		if(isset($_POST['email_change']) || $this->password_change_error || $this->email_change_error) $this->add_to_html('<style>body{overflow:hidden;}</style>');
	}

	// Проверяем нужно ли выходить из аккаунта и если нужно, то выходим
	public function log_out_check(){
		$this->session_flag = isset($_SESSION['username']);
		$this->help 				= isset($_GET['help']) ? $_GET['help'] : "";

		// Выйти из личного кабинета
		if($this->session_flag && $this->help == "log_out"){

			// Записать данные в логи
			$this->ucp_log("logout", ["ID" => $_SESSION['ID'], "Username" => $_SESSION['username'], "Server_id" => 1]);

			// Стираем сессию авторизации
			unset($_SESSION['username']);
			unset($_SESSION['ID']);

			// Перенаправляем на главную страницу
			header("Location: ./main.php");
		} else $this->help = "";
	}

	// Составляем подвал
	public function compile_footer(){
		$this->add_to_html('<footer>
			<div class="top">
			<div class="socials">
			<div class="image">
			<a href="#" class="blur"><img src="../img/vk_blue.png" alt="VK"></a>
			<a href="#" class="hover"><img src="../img/vk.png" alt="VK"></a>
			</div>
			<div class="image">
			<a href="#" class="blur"><img src="../img/youtube_blue.png" alt="YOUTUBE"></a>
			<a href="#" class="hover"><img src="../img/youtube.png" alt="YOUTUBE"></a>
			</div>
			</div>
			<div class="scroll-top">
			<span class="chevron" onclick="ScrollTop()"></span>
			</div>
			</div>
			<div class="bottom">
			<h1>GreenTech RolePlay © 2012-2020</h1>
			<span>Made by <a href="https://vk.com/s4rgh0">Sergey Chernyshov</a></span>
			</div>
			</footer>');
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

	// Получить данные из БД $get - поле, которое хотим получить у $username
	public function db_get($table, $get, $username, $order = "ID"){
		// Защита от SQL инъекций
		$get = $this->db->real_escape_string($get);
		$username = $this->db->real_escape_string($username);
		// Получаем данные
		$result = $salt_db = $this->db->query('SELECT `' . $get . '` FROM `' . $table . '` WHERE `Names` = ' . '"' . $username . '" ORDER BY `' . $order . '` DESC');
		$result = mysqli_fetch_assoc($result)[$get];
		return $result;
	}

	// Сменить данные из БД $set - поле у $username, в котором нужно установить значение $value
	public function db_set($set, $value, $username){
		// Защита от SQL инъекций
		$set = $this->db->real_escape_string($set);
		$value = $this->db->real_escape_string($value);
		$username = $this->db->real_escape_string($username);
		// Изменяем
		$this->db->query('UPDATE `' . $this->server_data['db_database'] . '` SET `' . $set . '` = "' . $value . '" WHERE `Names` = "' . $username . '"');
	}

	// Сброс точки спавна при необходимости
	public function spawn_reset_check(){
		if(isset($_POST['spawn_reset'])){
			// Сбрасываем место спавна
			$this->db_set('Spawn', '2', $_SESSION['username']);
		}
	}

	// Получаем и ПРЕОБРАЗУЕМ данные с БД на главную страницу
	public function get_main_response(){
		$username = $_SESSION['username'];
		$response = mysqli_fetch_assoc($this->db->query('SELECT * FROM `players` WHERE `Names` = "' . $username . '"'));

		if($response['Sex'] == 1) $response['Sex'] = "Мужской";
		else $response['Sex'] = "Женский";

		if($response['PhoneNumber'] == '0') $response['PhoneNumber'] = '-';

		$response['Job'] = $this->fraction_names[$response['Job']];

		if($response['Job'] != '-') $response['Rank'] = $rang_names[$response['Job']][$response['Rank']];
		else $response['Rank'] = '-';

		return $response;
	}

	// Получаем и преобразуем данные с БД на страницу платежей
	public function get_payments(){
		$username = $_SESSION['username'];
		$response = $this->db->query("SELECT * FROM unitpay_payments WHERE `account` = '" . $username . "' AND `status` = '1' ORDER BY `id` DESC");
		$result = [];
		while($test = mysqli_fetch_assoc($response)){
			$result[] = $test;
		}
		return $result;
	}

	// Получаем и преобразуем данные с БД на страницу лидеров
	public function get_leaders(){
		$response = $this->db->query("SELECT * FROM players WHERE Leader > 0 ORDER BY Leader");
		$result = [];
		while($leader = mysqli_fetch_assoc($response)){
			$result[] = $leader;
		}
		return $result;
	}

	// Получаем всех участников фракции
	public function get_participants($fraction_id){
		$response = $this->db->query("SELECT * FROM players WHERE `Member` = $fraction_id");
		$result = [];
		while($participant = mysqli_fetch_assoc($response)){
			$result[] = $participant;
		}
		return $result;
	}

	// Генерация случайного ключа
	public function generate_key($len){
		$chars = array(
			'a','b','c','d','e','f',
			'g','h','i','j','k','l',
			'm','n','o','p','r','s',
			't','u','v','x','y','z',
			'A','B','C','D','E','F',
			'G','H','I','J','K','L',
			'M','N','O','P','R','S',
			'T','U','V','X','Y','Z',
			'1','2','3','4','5','6',
			'7','8','9','0','-','_'
		);

		$key = "";

		for($i = 0; $i < $len; $i++)
		{
			$key .= $chars[rand(0, count($chars) - 1)];
		}

		return $key;
	}

	public function get_password_hash($password, $account_salt){
		return strtoupper(hash("sha256", $password."_".$account_salt."_".$this->account_system_salt));
	}

	// Добавляем данные в лог
	public function ucp_log($action, $params){
		$params = json_encode($params);
		$ip 		= $_SERVER['REMOTE_ADDR'];
		$time 	= time();
		$response = $this->db->query("INSERT INTO `ucp_log`(`ip`, `ts`, `action`, `params`) VALUES('" . $ip . "', $time, '" . $action . "', '" . $params . "')");
		return 1;
	}

	// Добавить к общему шаблону
	public function add_to_html($data){
		$this->html .= $data;
	}

	// Вернуть общий шаблон для основного движка
	public function get_html(){
		return $this->html;
	}
}

?>