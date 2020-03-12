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

				header("Location: ../ucp.php");
			}
		}
	}

	// Составляем страницу личного кабинета
	public function compile(){
		$this->compile_darkness("ucp");
		$this->compile_logo();
		$this->compile_nav();
		// Основная отрисовка
		$session = isset($_SESSION['username']);
		$help = isset($_GET['help']) ? $_GET['help'] : "";	// Тип операции с личным кабинетом
		// Проверяем сессию на авторизованный аккаунт
		$this->db_connect();

		// Выйти из личного кабинета
		if($session && $help == "log_out"){
			// Стираем сессию авторизации
			unset($_SESSION['username']);
			// Перенаправляем на главную страницу
			header("Location: ./main.php");
		}
		// Чтобы пользователь намеренно не менял GET запрос, делаем его пустым, если он отличается от всех шаблонов
		else $help = "";
			// Если активирована сессия, то отрисовываем контент ЛК
			if($session && $help == ""){
			// Проверяем на сброс места спавна
			$this->spawn_reset();
			// Все переменные, которые нам будут нужны для ЛК
			// Имя пользователя
			$username = $_SESSION['username'];
			$response_main = $this->get_ucp_main_response($username);
			$response_payments = $this->complete_ucp_payments($username);
			$response_leaders = $this->complete_ucp_leaders($username);
			// Отрисовываем контент ЛК
			// Для теста вставляем все остальное
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

			$this->compile_ucp_ooc($response_main);
			$this->compile_ucp_ic($response_main);
			$this->add_to_html('<div class="help">
				<button onclick="OpenPasswordChangeMenu();">Сменить пароль</button>
				<button onclick="OpenEmailChangeMenu();">Сменить Email</button>
				</div>
				</li>
				<li class="page property-page">
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
				</li>
				<li class="page payments-page">
				<div class="sort">
				<p>Сортировать:<span class="enabled" onclick="ActivateFilter(this);">Сначала новые</span>/<span class="" onclick="ActivateFilter(this);">Сначала старые</span></p>
				</div>');
			$this->compile_ucp_payments($response_payments);
			$this->compile_ucp_leaders($response_leaders);
			$this->add_to_html('<li class="page leaders-page">
				<div class="slider">
				<ul class="slides">
				<li class="slide" style="display: flex; opacity: 1;">
				<div class="h">
				<div class="nick">Nick_Name</div>
				<div class="organization">Организация</div>
				<div class="last-enter">Последний вход</div>
				</div>
				<ul>
				<li>
				<div class="nick online">Ruslan_Budagov</div>
				<div class="organization">УМВД</div>
				<div class="last-enter">21.08.2019</div>
				</li>
				<li>
				<div class="nick online">Roman_Samarin</div>
				<div class="organization">УМВД</div>
				<div class="last-enter">21.08.2019</div>
				</li>
				<li>
				<div class="nick online">Sergey_Snegirev</div>
				<div class="organization">УМВД</div>
				<div class="last-enter">21.08.2019</div>
				</li>
				<li>
				<div class="nick online">Denis_Bilkov</div>
				<div class="organization">УМВД</div>
				<div class="last-enter">21.08.2019</div>
				</li>
				<li>
				<div class="nick online">Sergey_Ivanov</div>
				<div class="organization">УМВД</div>
				<div class="last-enter">21.08.2019</div>
				</li>
				<li>
				<div class="nick online">Jon_Town</div>
				<div class="organization">Пра-во</div>
				<div class="last-enter">21.08.2019</div>
				</li>
				<li>
				<div class="nick">Maxim_Perfilev</div>
				<div class="organization">Пра-во</div>
				<div class="last-enter">21.08.2019</div>
				</li>
				<li>
				<div class="nick online">Vadim_Roslin</div>
				<div class="organization">ФСИН</div>
				<div class="last-enter">21.08.2019</div>
				</li>
				<li>
				<div class="nick">Gleb_Shapranov</div>
				<div class="organization">Прокуратура</div>
				<div class="last-enter">21.08.2019</div>
				</li>
				<li>
				<div class="nick online">Sergey_Snegirev</div>
				<div class="organization">УМВД</div>
				<div class="last-enter">21.08.2019</div>
				</li>
				<li>
				<div class="nick online">Denis_Bilkov</div>
				<div class="organization">УМВД</div>
				<div class="last-enter">21.08.2019</div>
				</li>
				<li>
				<div class="nick online">Sergey_Ivanov</div>
				<div class="organization">УМВД</div>
				<div class="last-enter">21.08.2019</div>
				</li>
				<li>
				<div class="nick online">Jon_Town</div>
				<div class="organization">Пра-во</div>
				<div class="last-enter">21.08.2019</div>
				</li>
				<li>
				<div class="nick">Maxim_Perfilev</div>
				<div class="organization">Пра-во</div>
				<div class="last-enter">21.08.2019</div>
				</li>
				<li>
				<div class="nick online">Vadim_Roslin</div>
				<div class="organization">ФСИН</div>
				<div class="last-enter">21.08.2019</div>
				</li>
				<li>
				<div class="nick">Gleb_Shapranov</div>
				<div class="organization">Прокуратура</div>
				<div class="last-enter">21.08.2019</div>
				</li>
				<li>
				<div class="nick online">Jon_Town</div>
				<div class="organization">Пра-во</div>
				<div class="last-enter">21.08.2019</div>
				</li>
				</ul>
				</li>
				<li class="slide" style="display: none; opacity: 0;">
				<div class="h">
				<div class="nick">Nick_Name</div>
				<div class="organization">Организация</div>
				<div class="last-enter">Последний вход</div>
				</div>
				<ul>
				<li>
				<div class="nick online">Ruslan_Budagov</div>
				<div class="organization">УМВД</div>
				<div class="last-enter">21.08.2019</div>
				</li>
				<li>
				<div class="nick online">Roman_Samarin</div>
				<div class="organization">УМВД</div>
				<div class="last-enter">21.08.2019</div>
				</li>
				<li>
				<div class="nick online">Sergey_Snegirev</div>
				<div class="organization">УМВД</div>
				<div class="last-enter">21.08.2019</div>
				</li>
				<li>
				<div class="nick online">Denis_Bilkov</div>
				<div class="organization">УМВД</div>
				<div class="last-enter">21.08.2019</div>
				</li>
				<li>
				<div class="nick online">Sergey_Ivanov</div>
				<div class="organization">УМВД</div>
				<div class="last-enter">21.08.2019</div>
				</li>
				<li>
				<div class="nick online">Jon_Town</div>
				<div class="organization">Пра-во</div>
				<div class="last-enter">21.08.2019</div>
				</li>
				<li>
				<div class="nick">Maxim_Perfilev</div>
				<div class="organization">Пра-во</div>
				<div class="last-enter">21.08.2019</div>
				</li>
				<li>
				<div class="nick online">Vadim_Roslin</div>
				<div class="organization">ФСИН</div>
				<div class="last-enter">21.08.2019</div>
				</li>
				<li>
				<div class="nick">Gleb_Shapranov</div>
				<div class="organization">Прокуратура</div>
				<div class="last-enter">21.08.2019</div>
				</li>
				<li>
				<div class="nick online">Sergey_Snegirev</div>
				<div class="organization">УМВД</div>
				<div class="last-enter">21.08.2019</div>
				</li>
				<li>
				<div class="nick online">Denis_Bilkov</div>
				<div class="organization">УМВД</div>
				<div class="last-enter">21.08.2019</div>
				</li>
				<li>
				<div class="nick online">Sergey_Ivanov</div>
				<div class="organization">УМВД</div>
				<div class="last-enter">21.08.2019</div>
				</li>
				<li>
				<div class="nick online">Jon_Town</div>
				<div class="organization">Пра-во</div>
				<div class="last-enter">21.08.2019</div>
				</li>
				<li>
				<div class="nick">Maxim_Perfilev</div>
				<div class="organization">Пра-во</div>
				<div class="last-enter">21.08.2019</div>
				</li>
				<li>
				<div class="nick online">Vadim_Roslin</div>
				<div class="organization">ФСИН</div>
				<div class="last-enter">21.08.2019</div>
				</li>
				<li>
				<div class="nick">Gleb_Shapranov</div>
				<div class="organization">Прокуратура</div>
				<div class="last-enter">21.08.2019</div>
				</li>
				<li>
				<div class="nick online">Jon_Town</div>
				<div class="organization">Пра-во</div>
				<div class="last-enter">21.08.2019</div>
				</li>
				</ul>
				</li>
				</ul>
				<div class="prev disabled" onclick="SliderPrev(this);"></div>
				<div class="current">1</div>
				<div class="next" onclick="SliderNext(this);"></div>
				</div>
				</li>
				</ul>
				</div>
				<div class="skin">
				<div class="slider">
				<ul class="slides">
				<li class="slide" style="display: none; opacity: 0;">
				<h1>Скин (обычный)</h1>
				<p>' . $response_main['Char'] . 'id</p>
				<img src="../img/skins/default/skin' . $response_main['Char'] . '.png" alt="skin">
				</li>
				<li class="slide" style="display: block; opacity: 1;">
				<h1>Скин (мод-пак)</h1>
				<p>' . $response_main['Char'] . 'id</p>
				<img src="../img/skins/pack/skin' . $response_main['Char'] . '.png" alt="skin">
				</li>
				</ul>
				<div class="prev" onclick="SliderPrev(this);"></div>
				<p class="current" style="display: none">2</p>
				<div class="next disabled" onclick="SliderNext(this);"></div>
				</div>
				</div>
				<div class="help">
				<button onclick="OpenPasswordChangeMenu();">Сменить пароль</button>
				<button onclick="OpenEmailChangeMenu();">Сменить Email</button>
				<a href="./ucp.php?help=log_out">Выйти</a>
				</div>');
		}
		$this->add_to_html('
			</div>
			</header>');
		// Добавляем окно смены пароля при ошибке
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
		// Окно успешной смены пароля
		if(!$this->password_change_error && isset($_POST['old_password'])) $this->add_to_html('<div id="success" style="display: block; opacity: 1;">
			<div class="exit" onclick="CloseModal(this);">
			<span></span>
			<span></span>
			</div>
			<img src="../img/success.png" alt="success">
			<h1>Пароль сменён успешно</h1>
			<p>Структура политической науки, как бы это ни казалось парадоксальным, представляет собой гарант. Мажоритарная избирательная система противоречива</p>
			</div>');
		// Добавляем окно смены мыла
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
		// Окно успешной отправки письма
		if(isset($_POST['new_email']) && !$this->email_change_error) $this->add_to_html('<div id="success" style="display: block; opacity: 1;">
			<div class="exit" onclick="CloseModal(this);">
			<span></span>
			<span></span>
			</div>
			<img src="../img/mail.png" alt="success">
			<h1>Письмо с подтверждением отправлено</h1>
			<p>Структура политической науки, как бы это ни казалось парадоксальным, представляет собой гарант. Мажоритарная избирательная система противоречива</p>
			</div>');
		// Подвал сайта
		$this->compile_footer();

		// Добавляем скрипты
		$this->html .= '<script src="../js/jquery.js"></script>';
		$this->html .= '<script src="../js/script.js"></script>';

		// Делаем overflow hidden body при модальном окне
		if(isset($_POST['email_change']) || $this->password_change_error || $this->email_change_error) $this->add_to_html('<style>body{overflow:hidden;}</style>');
	}

	// Показываем затемноение при необходимости
	public function compile_darkness($page){
		$this->add_to_html('
			<div id="' . $page . '-wrapper">
			<div class="darkness" onclick="DropdownToggle(this); CloseModals();" ');

		if(isset($_POST['password_change']) || isset($_POST['email_change'])) $this->add_to_html('style="display: block; opacity: 1;"></div>');
		else $this->add_to_html('></div>');
	}

	// Логотип сайта
	public function compile_logo(){
		$this->add_to_html('
			<header>
			<div class="logo">
			<a href="main.html">
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

	// Выводим данные ooc
	public function compile_ucp_ooc($response){
		$username = $_SESSION['username'];

		$this->add_to_html('<ul class="ooc"><h2>OOC Информация</h2>');

		foreach ($this->ucp_main_ooc as $key => $value){
			if(substr($value, 0, 3) == "db_") $value = $response[substr($value, 3)];
			if($value == "username") $value = $username;
			$this->add_to_html('<li><div class="param">' . $key . ':</div>');
			$this->add_to_html('<div class="value">' . $value . '</div>');
			if($key == "Донат-очки") $this->add_to_html('<form action="./donate.php" method="GET"><button class="donate-btn"><img src="./img/plus.png" alt=""></button></form>');
			if($key == "Место появления") $this->add_to_html('<form action="./ucp.php" method="POST"><button name="spawn_reset" class="spawn_reset"><img src="./img/reset.png" alt=""></button></form>');
			$this->add_to_html('</li>');
		}

		$this->add_to_html('</ul>');
	}

	// Выводим данные ic
	public function compile_ucp_ic($response){

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
	public function compile_ucp_payments($response){
		$this->add_to_html('<div class="slider">
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
	public function compile_ucp_leaders($response){
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
			</li>');
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
			<h1>GreenTech RolePlay © 2012-2019</h1>
			<span>Made by Kipper Studio</span>
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

	// Сброс точки спавна
	public function spawn_reset(){
		if(isset($_POST['spawn_reset'])){
			// Сбрасываем место спавна
			$this->db_set('Spawn', '2', $_SESSION['username']);
		}
	}

	// Получаем и ПРЕОБРАЗУЕМ данные с БД на главную страницу
	public function get_ucp_main_response($username){
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
	public function complete_ucp_payments($username){
		$response = $this->db->query("SELECT * FROM unitpay_payments WHERE `account` = '" . $username . "' AND `status` = '1' ORDER BY `id` DESC");
		$result = [];
		while($test = mysqli_fetch_assoc($response)){
			$result[] = $test;
		}
		return $result;
	}

	// Получаем и преобразуем данные с БД на страницу лидеров
	public function complete_ucp_leaders(){
		$response = $this->db->query("SELECT * FROM players WHERE Leader > 0 ORDER BY Leader");
		$result = [];
		while($leader = mysqli_fetch_assoc($response)){
			$result[] = $leader;
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