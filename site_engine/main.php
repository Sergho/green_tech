<?php

class Main{

	public $db;
	public $server_data;
	public $auth_error = [];
	public $account_system_salt = "MtIWebzsEjfXriFU";

	public $html;

	// Конструктор
	public function __construct(){
		// Подключаем config файлы
		require_once("./config/server_config.php");
		// Применяем конфиги
		$this->server_data = $server_config;
		// Подключаемся к БД
		$this->db_connect();
	}

	// Функция проверки ошибки авторизации
	public function catch_auth_error(){
		// Проверяем форму авторизации если она задействована
		if(isset($_POST['login']) && isset($_POST['password'])){
			// Записываем логин и пароль в переменную
			$login = $_POST['login'];
			$password = $_POST['password'];

			// 1 шаг - проверка форм на валидность
			if(strlen($login) < 1 || strlen($password) < 1) $this->auth_error[] = "Заполните все поля";

			if(!$this->auth_error){
				// 2 шаг - проверка на то, существует ли такой пользователь

				// ID пользователя из БД
				$id_db = $this->db_get('players', 'ID', $login);

				// Если получили ID, значит такой пользователь существует
				if(!isset($id_db)) $this->auth_error[] = "Указаный игрок не найден";
			}
			if(!$this->auth_error){
				// 3 шаг - проверка совпадения пароля из формы с БД

				// Пароль и "Соль" из БД
				$password_db 	= $this->db_get('players', 'Pass', $login);
				$salt_db 			= $this->db_get('players', 'salt', $login);

				// Проверка cовместимости паролей
				if($password_db != $this->get_password_hash($password, $salt_db)) $this->auth_error[] = "Неверный пароль";
			}	
			if(!$this->auth_error){
				// 4 шаг - сама авторизация

				// Переводим пользователя на страницу ЛК
				$_SESSION['username'] = $login;
				header('Location: ./ucp.php');
			}
		}
	}

	// Составляем главное соддержимое
	public function compile(){
		$this->compile_darkness("main");
		$this->compile_logo();
		$this->compile_nav();
		$this->add_to_html('<div class="text">
			<h1>Начни играть в CRMP на проекте GreenTech RolePlay прямо сейчас!</h1>
			<p>Как легко получить из самых общих соображений, кампос-серрадос представляет собой холодный взрыв. Несмотря на внутренние противоречия, вещество мгновенно</p>
			<a href="#" class="start">Как начать играть?</a>
			<a href="crmp://' . $this->server_data["ip"] . '" class="connect">Подключиться к серверу</a>
			</div>
			</header>
			<div id="online">
			<div class="avatar">
			<span class="point"></span>
			</div>
			<div class="people-online">
			<h1>125 человек</h1>
			<span>Онлайн</span>
			</div>
			</div>
			<div id="about">
			<div class="card">
			<div class="image"></div>
			<div class="text">
			<h1>О нашем проекте</h1>
			<p>Первый игровой мод был на основе Gamer, разработка длилась с 22 сентября 2012 года по июнь месяц 2014 года. Развитие сервера было очень стремительным, уже за 2 месяца после открытия сервер имел онлайн 50+, что не могло радовать основателей сервера. Над модом трудились 2 человека, это Юра Чемериский и Влад Мальцев. Влад Мальцев, второй разработчик игрового мода GreenTech RolePlay который присоеденился к проекту весной 2013 года.</p>
			<p>После чего все 3 основателя добились онлайна в 110/110 человек. Сервер имел множество проблем, после чего потерял свой онлайн до 3-5 человек в день. Через некоторое время он снова открылся, с новым игровым модом, но долго на этой основе сервер не проработал из за лагов, и слетов аккаунтов.</p>
			<p>Был передан в другие руки, после чего была опять смена мода на старый. И потом опять же на новый, после исправления всех лагов и багов, мод наконец то начал быть играбельным. Мод стремительно развивался, а сам проект помалу загибался. Сервер закрывался несколько раз, но недавно был восстановлен, и сейчас продолжает свою работу. Игровой мод сильно изменился, и стал более качественным</p>
			</div>
			</div>
			</div>
			<div id="start">
			<ul class="actions">
			<li class="game">
			<span class="number">1</span>
			<div class="text">
			<h1>Скачать <u>игру</u></h1>
			<p>Бессознательное, на первый взгляд, самопроизвольно. Субтехника, в том числе, вразнобой отталкивает стимул. Показательный пример – арпеджированная фактура сонорна. Код просветляет стресс</p>
			</div>
			<a href="">Скачать GTA Criminal Russia</a>
			</li>
			<li class="multiplayer">
			<span class="number">2</span>
			<div class="text">
			<h1>Скачать <u>мультиплеер</u></h1>
			<p>Бессознательное, на первый взгляд, самопроизвольно. Субтехника, в том числе, вразнобой отталкивает стимул. Показательный пример – арпеджированная фактура сонорна. Код просветляет стресс</p>
			</div>
			<a href="">Скачать CR-MP 0.3e</a>
			</li>
			<li class="modes">
			<span class="number">3</span>
			<div class="text">
			<h1>Скачать <u>пакет модификаций</u></h1>
			<p>Наши исследования позволяют сделать вывод о том, что сознание символизирует латентный онтогенез речи</p>
			</div>
			<a href="">Скачать мод-пак</a>
			</li>
			<li class="connect">
			<span class="number">4</span>
			<div class="text">
			<h1>Скачать <u>пакет модификаций</u></h1>
			<p>Запустите клиент CR-MP (с ярлыка или через Пуск). Введите никнейм в поле “Nick_Name” (Это будет имя вашего персонажа на сервере), Нажмите *оранжевая галочка* и вставьте наш IP-адрес: 194.61.44.20:8904 . Выберите сервер и нажмите *зелёная стрелочка*</p>
			</div>
			<a href="">Смотреть видео-урок</a>
			</li>
			</ul>
			<div class="bg">
			<img src="../img/start-background.png" alt="background">
			</div>
			</div>');

		$this->compile_footer();

		$this->add_to_html('<div id="auth" ');
		// если ошибка то оставляем модальное окно открытым
		if($this->auth_error) $this->add_to_html('style="display: block; opacity: 1;"');
		$this->add_to_html('>
			<div class="exit" onclick="Auth();">
			<span></span>
			<span></span>
			</div>
			<h1>Авторизация</h1>
			<form action="./" method="POST">');
		// Выводим ошибку
		if(!empty($this->auth_error)) $this->add_to_html('<input type="text" placeholder="Ник? ' . $_POST['login'] . '" class="error" name="login" oninput="CheckInput(this);" onclick="InputCloseError(this);" value="' . $this->auth_error[0] . '">');
		else $this->add_to_html('<input type="text" placeholder="Ник" name="login" oninput="CheckInput(this);">');
		$this->add_to_html('<input type="password" placeholder="Пароль" name="password" oninput="CheckInput(this);">
			<img src="../img/eye.png" alt="show" onclick="ShowPassword(this, 1);" class="show">
			<a href="#">Забыли пароль?</a>
			<button type="submit">Войти</button>
			</form>
			</div>');

		// Добавляем скрипты
		$this->html .= '<script src="../js/jquery.js"></script>';
		$this->html .= '<script src="../js/script.js"></script>';

		// Делаем overflow hidden body при модальном окне
		if(isset($_POST['email_change']) || $this->auth_error) $this->add_to_html('<style>body{overflow:hidden;}</style>');
	}

	// Показываем затемноение при необходимости
	public function compile_darkness(){
		$this->add_to_html('
			<div id="main-wrapper">
			<div class="darkness" onclick="DropdownToggle(this); CloseModals();" ');

		if($this->auth_error) $this->add_to_html('style="display: block; opacity: 1;"></div>');
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
			<li class="navbar-item active"><a href="main.php">Главная</a></li>
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

	// Преобразование пароля при авторизации
	public function get_password_hash($password, $account_salt){
		return strtoupper(hash("sha256", $password."_".$account_salt."_".$this->account_system_salt));
	}

	// Добавить к общему шаблону
	public function add_to_html($data){
		$this->html .= $data;
	}
	// Вернуть общий шаблон
	public function get_html(){
		return $this->html;
	}
}

?>