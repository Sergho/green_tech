<?php

class Donate{

	public $db;
	public $server_data;
	public $auth_error = [];
	public $donate_error = [];
	public $account_system_salt = "MtIWebzsEjfXriFU";
	public $html;

	public $donate_multiplier = 1;

	// Конструктор
	public function __construct(){
		// Подключаем config файлы
		require_once("./config/server_config.php");
		// Применяем конфиги
		$this->server_data = $server_config;
		// Подключаемся к БД
		$this->db_connect();
	}

	// Составляем страницу доната
	public function compile(){
		$this->compile_darkness();
		$this->compile_logo();
		$this->compile_nav();
		$this->add_to_html('<div class="text">
			<div class="image-pig">
			<img src="../img/pig.png" alt="pig_money">
			</div>
			<h1>Пополнение средств</h1>
			<p>Основная идея социально–политических взглядов К.Маркса была в том, что созерцание традиционно понимает под собой либерализм. Александрийская школа экстремально иллюстрирует авторитаризм, изменяя привычную реальность</p>');

		// Вывод донат формы
		if((isset($_POST['username']) && isset($_POST['sum']))){
			if($this->donate_error) $this->compile_donate_error_form();
			else $this->compile_payment_confirm_form();
		} else $this->compile_donate_form_without_error();
		// Появление модального окна с ошибкой при платеже или успехе
		$this->compile_payment_error_or_success();
		// Подвал сайта
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
		if(isset($_POST['email_change']) || $this->auth_error || isset($_GET['type'])) $this->add_to_html('<style>body{overflow:hidden;}</style>');
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

	// Функция проверки доната на ошибки
	public function catch_donate_error(){
		// Проверяем форму доната при необходимости
		if(isset($_POST['username']) && isset($_POST['sum'])){
			// Записываем всё что нужно в переменные
			$username = $_POST['username'];
			$sum 			= $_POST['sum'];
			$agree 		= false;
			if(isset($_POST['agree'])) $agree = true;

			// Шаг 1 - проверка валидации формы
			if(!$agree) $this->donate_error[] = "Галочку не поставил";
			if(strlen($username) < 3 || strlen($username) > 20) $this->donate_error[] = "Допустимая длина никнейма: 3-20 символов";
			// Проверяем, сможет ли полученное значение суммы превратиться в число, если нет, то ошибка
			if(!ctype_digit($sum)) $this->donate_error[] = "Сумма доната не является числом";
			// Если да, то превращаем в число
			else $sum = strval($sum);
			if($sum < 1 || $sum > 10000) $this->donate_error[] = "Допустимая сумма платежа: от 1 до 10.000 рублей";

			if(!$this->donate_error){
				// Шаг 2 - Проверка логина на существование в БД

				// Получаем ID из БД через логин, если что-то получим, значит такой логин сущетвует
				$id_db = $this->db_get('players', "ID", $username);

				// Проверка
				if(!isset($id_db)) $this->donate_error[] = "Игрок с таким никнеймом не существует";
			}
		}
	}

	// Показываем затемноение при необходимости
	public function compile_darkness(){
		$this->add_to_html('
			<div id="donate-wrapper">
			<div class="darkness" onclick="DropdownToggle(this); CloseModals();" ');

		if(isset($_GET['type'])) $this->add_to_html('style="display: block; opacity: 1;"></div>');
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
			<li class="navbar-item active"><a href="donate.php">Донат</a></li>
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

	// Составляем форму с ошибкой
	public function compile_donate_error_form(){
		$username = $_POST['username'];
		$sum 			= $_POST['sum'];
		// Первая ошибка в массиве
		$error_first = array_shift($this->donate_error);
		// Ошибка непостановки галочки соглашения
		$tick_error = ($error_first == "Галочку не поставил") ? true : false;

		// Переменная, показывающая ошибку в поле с никнеймом
		$username_error = ($error_first == "Допустимая длина никнейма: 3-20 символов" || $error_first == "Игрок с таким никнеймом не существует") ? true : false;

		// Переменная, показывающая ошибку в поле с суммой
		$sum_error = ($error_first == "Сумма доната не является числом" || $error_first == "Допустимая сумма платежа: от 1 до 10.000 рублей") ? true : false;

		$this->add_to_html('
			<form action="donate.php" method="POST">
			<input type="text" name="username" ');
		// Проверяем причину ошибки, если причина в никнейме, то добавляем полю с никнеймом класс с ошибкой
		if($username_error) $this->add_to_html('class="error"');
		$this->add_to_html('placeholder="');
		// Добавляем в placeholder либо стандартный текст либо "Стандартный текст, раннее введенный текст"
		if($username_error) $this->add_to_html("Ник? " . $username);
		else $this->add_to_html("Ник");
		$this->add_to_html('" value="');
		// Изменяем value на текст ошибки или на раннее введенный текст
		if($username_error) $this->add_to_html($error_first);
		else $this->add_to_html($username);
		$this->add_to_html('"
			onclick="InputCloseError(this)" oninput="CheckInput(this);">
			<input type="text" name="sum" ');
		// Проверяем причину ошибки, если причина в сумме доната, то добавляем полю с суммой класс с ошибкой
		if($sum_error) $this->add_to_html('class="error"');
		$this->add_to_html(' placeholder="');
		// Добавляем в placeholder либо стандартный текст либо "Стандартный текст, раннее введенную сумму"
		if($sum_error) $this->add_to_html("Сумма, (Руб)? " . $sum);
		else $this->add_to_html("Сумма, (Руб)");
		$this->add_to_html('" value="');
		// Изменяем value на текст ошибки или на раннее введенную сумму
		if($sum_error) $this->add_to_html($error_first);
		else $this->add_to_html($sum);
		$this->add_to_html('" onclick="InputCloseError(this);" oninput="CheckInput(this);">
			<div class="agreement">
			<label class="confirm">
			<input type="checkbox" name="agree" value="confirm">
			<span class="visible ');
		// Если не поставлена галочка, выделяем поле с ней
		if($tick_error) $this->add_to_html("error");
		$this->add_to_html('" onclick="Tick(this);">
			<span class="tick"></span>
			</span>
			</label>
			<p>Я изучил и принял <a href="#">пользовательское соглашение</a></p>
			</div>
			<button type="submit">Продолжить</button>
			</form>
			</div>
			</header>');
	}

	// Составляем форму подтверждения платежа
	public function compile_payment_confirm_form(){
		$username = $_POST['username'];
		$sum 			= $_POST['sum'];
		$this->add_to_html('
			<form action="'.$this->server_data['unitpay_link'].'" method="post">
			<p>
			<b>Проверьте указанные данные</b><br/>
			сервер: GreenTech RolePlay #1<br/>
			никнейм: '.$username.'<br/>
			к оплате: '.$sum.' RUB<br/>
			будет зачислено: '.($sum * $this->donate_multiplier).' ДО '.(($this->donate_multiplier > 1) ? "<font color=\"red\">(акция \"x".$this->donate_multiplier." донат\")</font>" : "").'<br/>
			<br/>
			<b>Вы хотите перейти к оплате?</b>
			</p>
			<input type="hidden" name="desc" value="Покупка внутриигровой валюты на сервере GreenTech RolePlay #1 для аккаунта '.$username.'" />
			<input type="hidden" name="account" value="'.$username.'" />
			<input type="hidden" name="sum" value="'.$sum.'" />
			<button type="submit">Оплатить</button>
			</form>
			</div>
			</header>');
	}

	// Составляем форму без ошибки
	public function compile_donate_form_without_error(){
		// Просто выводим форму
		$this->add_to_html('
			<form action="donate.php" method="POST">
			<input type="text" name="username" placeholder="Ник"
			onclick="InputCloseError(this)" oninput="CheckInput(this);">
			<input type="text" name="sum" placeholder="Сумма, (Руб)" onclick="InputCloseError(this);" oninput="CheckInput(this);">
			<div class="agreement">
			<label class="confirm">
			<input type="checkbox" name="agree" value="confirm">
			<span class="visible" onclick="Tick(this);">
			<span class="tick"></span>
			</span>
			</label>
			<p>Я изучил и принял <a href="#">пользовательское соглашение</a></p>
			</div>
			<button type="submit">Продолжить</button>
			</form>
			</div>
			</header>');
	}

	// Показать ошибку при платеже или успех
	public function compile_payment_error_or_success(){
		// Если успешная оплата или ошибка при оплате
		if(isset($_GET['paymentId']) && isset($_GET['account']) && isset($_GET['type'])){
			// Создаем переменные хранящие GET параметры
			$type = $_GET['type'];

			if($type == "SUCCESS"){
				// Страница успешной оплаты

				// Добавляем оставшееся от страницы доната, открываем модальное окно ошибки
				$this->add_to_html('
					<div id="success" style="display: block; opacity: 1;">
					<div class="exit" onclick="CloseModal(this);">
					<span></span>
					<span></span>
					</div>
					<img src="../img/success.png" alt="success">
					<h1>Всё прошло успешно!</h1>
					<p>Структура политической науки, как бы это ни казалось парадоксальным, представляет собой гарант. Мажоритарная избирательная система противоречива</p>
					</div>');
				} else if($type == "ERROR"){
					// Страница ошибки при оплате

					// Добавляем оставшееся от страницы доната, открываем модальное окно ошибки
					$this->add_to_html('
						<div id="error" style="display: block; opacity: 1;">
						<div class="exit" onclick="CloseModal(this);">
						<span></span>
						<span></span>
						</div>
						<img src="../img/error.png" alt="error">
						<h1>Ошибка!</h1>
						<p>Гений неоднозначен. Гипотеза, как бы это ни казалось парадоксальным, верифицирует гений. Оферта создает современный дедуктивный метод</p>
						</div>
						</div>');
				}
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
	
	public function get_password_hash($password, $account_salt){
		return strtoupper(hash("sha256", $password."_".$account_salt."_".$this->account_system_salt));
	}

	// Добавить к общему шаблону
	public function add_to_html($data){
		$this->html .= $data;
	}

	// Вернуть весь шаблон движку
	public function get_html(){
		return $this->html;
	}

}

?>