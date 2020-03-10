<?

class engine
{
	// MySQL БД
	public $db;
	// Соддержимое тега body
	public $body;
	// Соддержимое тега head
	public $head;
	// Информация о сервере
	public $server_data;
	
	public $block_site = false; // блокировка сайта
	public $block_whitelist = array(); // список разрешенных ip при заблокированном сайте
	
	public $account_system_salt = "MtIWebzsEjfXriFU";
	public $rcon_password = "rcon";
	public $donate_multiplier = 1;
	
	public $fraction_name;
	public $rang_name;
	public $sub_rang_name;

	public $ucp_main;

	// Ошибки
	public $auth_error = [];
	public $password_change_error = [];
	public $email_change_error = [];
	public $donate_error = [];
	
	// Конструктор
	public function __construct(){
		// Подключаем config файлы
		require_once("./config/server_config.php");
		require_once("./config/fractions.php");
		require_once("./config/ucp_main.php");
		// Применяем конфиги
		$this->server_data 		= $server_config;
		$this->fraction_name 	= $fractions;
		$this->rang_name 			= $rangs;
		$this->sub_rang_name 	= $sub_rangs;
		$this->ucp_main_ooc 	= $ucp_main_ooc_params;
		$this->ucp_main_ic		= $ucp_main_ic_params;
	}

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
		// Работает только при соответствующих запросах
		$this->auth();
		$this->change_password();
		$this->change_email();
		// Активация блокировки если это необходимо
		if($this->block_site && !in_array($_SERVER['REMOTE_ADDR'], $this->block_whitelist)){
			$this->add_to_body('
				<center style="width: 100%; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); padding: 50px;">
				<h1 style="font-size: 5em; font-family: Proxima Nova Th; font-weight: bold">Сайт на технических работах</h4>
				<p style="font-size: 3em; font-family: Proxima Nova Th;">В настоящие время данный сайт доступен только для указанных IP-адресов. Возможно, он находится на рекострукции или технических работах. Пожалуйста, подождите, возможно скоро он снова будет доступен.</p>
				</center>');

			return $this->body;
		}

		// Вставляем определенную страницу
		if($page == "MAIN") $this->compile_main();
		if($page == "DONATE") $this->compile_donate();
		if($page == "UCP") $this->compile_ucp();


		if($page == "AGREEMENT"){
			$this->add_to_body('
				<section class="sa-text-area">
				<center>
				<div class="main-block">
				<div class="main-block-sub" style="width: 70%;">
				<p class="block-title">Пользовательское соглашение</p>
				');

			$this->add_to_body('
				<div class="alert alert-success" role="alert" style="text-align: left;">
				<h4 class="alert-heading">Принятие условий</h4>
				<p>- Настоящие правила являются документом, обязательным к ознакомлению каждому пользователю, обратившегося к донат услугам.</p>
				<p>- Если пользователь не согласен с каким-либо положением настоящих правил или ощущает вероятность негативных для себя последствий, ему рекомендуется отказаться от использования донат услуг.</p>
				<p>- Факт обращения инициации пользователем процесса использования донат услуг считается подтверждением того, что он ознакомлен и согласен с каждым пунктом настоящих правил.</p>
				</div>
				<div class="alert alert-success" role="alert" style="text-align: left;">
				<h4 class="alert-heading">Общие положения</h4>
				<p>- Администрация проекта не несет никакой ответственности за ущерб морального либо материального характера, который может нанести прямо либо опосредованно предлагаемый игровой сервер, а также за любые неточности, ошибки, дефекты и сбои работы игрового сервера, вне зависимости от причин их вызвавших.</p>
				<p>- Инициируя процесс использования донат услуг, пользователь подтверждает свое согласие не возлагать ответственность, возможные убытки и ущерб, связанные с пользованием игровым сервером, на его владельцев и администрацию.</p>
				<p>- В случае нанесение пользователем ущерба проекту, администрация проекта имеют право на удаление аккаунта нарушителя.</p>
				<p>- В случае несоответствия какого-либо положения настоящих правил требованиям действующего законодательства, оно считается замененным близким по содержанию положением действующего законодательства. При этом все остальные положения настоящих правил сохраняют свою силу.</p>
				<p>- В случае просьбы вернуть средства, администрация имеет право на блокировку аккаунта.</p>
				<p>- Администрация может сделать возврат средств, если со дня платежа прошло не более 7 дней.</p>
				</div>
				<div class="alert alert-success" role="alert" style="text-align: left;">
				<h4 class="alert-heading">Исключение гарантий</h4>
				<p>
				<p>- Администрация проекта имеет право на блокирование или удаление аккаунта без предупреждения пользователя.</p>
				<p>- Администрация проекта имеет право на удаление предоставленных Вам донат-услуг и/или на запрет их использования.</p>
				</div>
				');

			$this->add_to_body('
				</div>
				</div>
				</center>
				</section>
				');
		}

		$this->add_to_body('<div id="auth" ');
		// если ошибка то оставляем модальное окно открытым
		if($this->auth_error) $this->add_to_body('style="display: block; opacity: 1;"');
		$this->add_to_body('>
			<div class="exit" onclick="Auth();">
			<span></span>
			<span></span>
			</div>
			<h1>Авторизация</h1>
			<form action="./" method="POST">');
		// Выводим ошибку
		if(!empty($this->auth_error)) $this->add_to_body('<input type="text" placeholder="Ник? ' . $_POST['login'] . '" class="error" name="login" oninput="CheckInput(this);" onclick="InputCloseError(this);" value="' . $this->auth_error[0] . '">');
		else $this->add_to_body('<input type="text" placeholder="Ник" name="login" oninput="CheckInput(this);">');
		$this->add_to_body('<input type="password" placeholder="Пароль" name="password" oninput="CheckInput(this);">
			<img src="../img/eye.png" alt="show" onclick="ShowPassword(this, 1);" class="show">
			<a href="#">Забыли пароль?</a>
			<button type="submit">Войти</button>
			</form>
			</div>');

		// Добавляем скрипты
		$this->body .= '<script src="../js/jquery.js"></script>';
		$this->body .= '<script src="../js/script.js"></script>';

		// Делаем overflow hidden body при модальном окне
		if(isset($_POST['new_email']) || $this->auth_error || $this->password_change_error) $this->add_to_body('<style>body{overflow:hidden;}</style>');

		return $this->body;
	}

	// Показываем затемноение при необходимости
	public function compile_darkness($page){
		$this->add_to_body('
			<div id="' . $page . '-wrapper">
			<div class="darkness" onclick="DropdownToggle(this); CloseModals();" ');

		// Все модальные окна, при которых надо затемнять фон:
		// 1. Ошибка аутефикации
		// 2. Ошибка или успешный донат
		// 3. Смена пароля
		// 4. Смена мыла
		if(($this->auth_error) || isset($_GET['type']) || isset($_POST['old_password']) || isset($_POST['new_email'])) $this->add_to_body('style="display: block; opacity: 1;"></div>');
		else $this->add_to_body('></div>');
	}

	// Логотип сайта
	public function compile_logo(){
		$this->add_to_body('
			<header>
			<div class="logo">
			<a href="main.html">
			<img src="../img/logo.png" alt="logotype">
			</a>
			</div>');
	}

	// Навигация
	public function compile_nav($page){
		$this->add_to_body('<div class="nav">');
		$this->compile_dropdown();
		$this->compile_navbar($page);
		$this->add_to_body('</div>');
	}

	// Выпадающее меню
	public function compile_dropdown(){
		$this->add_to_body('
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
		if(isset($_SESSION['username'])) $this->add_to_body('<a href="./ucp.php">' . $_SESSION['username'] . '</a>');
		else $this->add_to_body('<a href="#" onclick="DropdownToggle(this); setTimeout(() => {Auth();}, 550);">Личный кабинет</a>');

		$this->add_to_body('</li></ul></div>');
	}

	// Навбар
	public function compile_navbar($page){
		$this->add_to_body('
			<div class="navbar">
			<ul class="navbar-menu">
			<li class="navbar-item ');
		if($page == "main") $this->add_to_body("active");
		$this->add_to_body('"><a href="main.php">Главная</a></li>
			<li class="navbar-item"><a href="#">Ставки BoxBet</a></li>
			<li class="navbar-item ');
		if($page == "donate") $this->add_to_body("active");
		$this->add_to_body('"><a href="donate.php">Донат</a></li>
			<li class="navbar-item"><a href="#">Gregtech FM</a></li>
			<li class="navbar-item"><a href="#">Форум</a></li>
			</ul>
			<div class="navbar-personal">');

		// Устанавливаем ссылку в кнопке ЛК в зависиммости от сессии (Просто меню)
		if(isset($_SESSION['username'])) $this->add_to_body('<a href="./ucp.php" class="lc">' . $_SESSION['username'] . '</a>');
		else $this->add_to_body('<a href="#" class="lc" onclick="Auth()">Личный кабинет</a>');

		$this->add_to_body('</div></div>');
	}

	// Составляем главную страницу
	public function compile_main(){
		$this->compile_darkness("main");
		$this->compile_logo();
		$this->compile_nav("main");
		$this->add_to_body('<div class="text">
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
	}

	// Составляем страницу доната
	public function compile_donate(){
		$this->compile_darkness("donate");
		$this->compile_logo();
		$this->compile_nav("donate");
		$this->add_to_body('<div class="text">
			<div class="image-pig">
			<img src="../img/pig.png" alt="pig_money">
			</div>
			<h1>Пополнение средств</h1>
			<p>Основная идея социально–политических взглядов К.Маркса была в том, что созерцание традиционно понимает под собой либерализм. Александрийская школа экстремально иллюстрирует авторитаризм, изменяя привычную реальность</p>');

		// Обработка доната
		$this->donate();
		// Подвал сайта
		$this->compile_footer();
	}

	// Составляем страницу личного кабинета
	public function compile_ucp(){
		$this->compile_darkness("ucp");
		$this->compile_logo();
		$this->compile_nav("ucp");
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
			$this->add_to_body('<div class="text">
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
			$this->add_to_body('<div class="help">
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
			$this->add_to_body('<li class="page leaders-page">
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
		$this->add_to_body('
			</div>
			</header>');
		// Добавляем окно смены пароля при ошибке
		if($this->password_change_error) $this->add_to_body('
			<div id="password_change" style="display: block; opacity: 1;">');
		else $this->add_to_body('<div id="password_change" style="display: none; opacity: 0;">');
		$this->add_to_body('<div class="exit" onclick="CloseModal(this);">
			<span></span>
			<span></span>
			</div>
			<h1>Смена пароля</h1>
			<p class="error">');
		// Добавляем ошибку при ее наличии
		if($this->password_change_error) $this->add_to_body($this->password_change_error[0]);
		else $this->add_to_body("&nbsp;");
		$this->add_to_body('</p>
			<form action="./ucp.php" method="POST">
			<input type="password" name="old_password" placeholder="Введите старый пароль">
			<input type="password" name="new_password" placeholder="Введите новый пароль">
			<input type="password" name="password_confirm" placeholder="Повторите новый пароль">
			<img src="../img/eye.png" alt="eye" class="show" onclick="ShowPassword(this, 0);">
			<img src="../img/eye.png" alt="eye" class="show" onclick="ShowPassword(this, 1);">
			<img src="../img/eye.png" alt="eye" class="show" onclick="ShowPassword(this, 2);">
			<button type="submit" name="">Сменить пароль</button>
			</form>
			</div>');
		// Окно успешной смены пароля
		if(!$this->password_change_error && isset($_POST['old_password'])) $this->add_to_body('<div id="success" style="display: block; opacity: 1;">
			<div class="exit" onclick="CloseModal(this);">
			<span></span>
			<span></span>
			</div>
			<img src="../img/success.png" alt="success">
			<h1>Пароль сменён успешно</h1>
			<p>Структура политической науки, как бы это ни казалось парадоксальным, представляет собой гарант. Мажоритарная избирательная система противоречива</p>
			</div>');
		// Добавляем окно смены мыла
		if(!empty($this->email_change_error)) $this->add_to_body('<div id="email_change" style="display: block; opacity: 1;">');
		else $this->add_to_body('<div id="email_change" style="display: none; opacity: 0;">');
		$this->add_to_body('<div class="exit" onclick="CloseModal(this);">
			<span></span>
			<span></span>
			</div>
			<h1>Смена E-mail</h1>
			<p class="error">');
		if(!empty($this->email_change_error)) $this->add_to_body($this->email_change_error[0]);
		else $this->add_to_body('&nbsp;');
		$this->add_to_body('</p>
			<form action="./ucp.php" method="POST">
		<input type="text" name="new_email" placeholder="Введите вашу почту"');
		if(!empty($this->email_change_error)) $this->add_to_body('value="' . $_POST['new_email'] . '"');
		$this->add_to_body('>
			<button type="submit">Отправить письмо</button>
			</form>
			</div>');
		// Окно успешной отправки письма
		if(isset($_POST['new_email'])) $this->add_to_body('<div id="success" style="display: block; opacity: 1;">
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
	}

	// Получаем и ПРЕОБРАЗУЕМ данные с БД на главную страницу
	public function get_ucp_main_response($username){
		$response = mysqli_fetch_assoc($this->db->query('SELECT * FROM `players` WHERE `Names` = "' . $username . '"'));

		if($response['Sex'] == 1) $response['Sex'] = "Мужской";
		else $response['Sex'] = "Женский";

		if($response['PhoneNumber'] == '0') $response['PhoneNumber'] = '-';

		$response['Job'] = $this->fraction_name[$response['Job']];

		if($response['Job'] != '-') $response['Rank'] = $rang_name[$response['Job']][$response['Rank']];
		else $response['Rank'] = '-';

		return $response;
	}

	// Выводим данные ooc
	public function compile_ucp_ooc($response){
		$username = $_SESSION['username'];

		$this->add_to_body('<ul class="ooc"><h2>OOC Информация</h2>');

		foreach ($this->ucp_main_ooc as $key => $value){
			if(substr($value, 0, 3) == "db_") $value = $response[substr($value, 3)];
			if($value == "username") $value = $username;
			$this->add_to_body('<li><div class="param">' . $key . ':</div>');
			$this->add_to_body('<div class="value">' . $value . '</div>');
			if($key == "Донат-очки") $this->add_to_body('<form action="./donate.php" method="GET"><button class="donate-btn"><img src="./img/plus.png" alt=""></button></form>');
			if($key == "Место появления") $this->add_to_body('<form action="./ucp.php" method="POST"><button name="spawn_reset" class="spawn_reset"><img src="./img/reset.png" alt=""></button></form>');
			$this->add_to_body('</li>');
		}

		$this->add_to_body('</ul>');
	}

	// Выводим данные ic
	public function compile_ucp_ic($response){

		$username = $_SESSION["username"];

		$this->add_to_body('<ul class="ic">
			<h2>IC Информация</h2>');
		foreach($this->ucp_main_ic as $param => $value){
			if(substr($value, 0, 3) == "db_") $value = $response[substr($value, 3)];
			$this->add_to_body('<li>');
			$this->add_to_body('<div class="param">' . $param . '</div>');
			$this->add_to_body('<div class="value">' . $value . ' </div>');
			$this->add_to_body('</li>');
		}
		$this->add_to_body("</ul>");
	}

	// Выводим данные о платежах
	public function compile_ucp_payments($response){
		$this->add_to_body('<div class="slider">
			<ul class="slides">
			<li class="slide" style="display: block; opacity: 1;">
			<ul>');
		foreach($response as $key => $payment){
			if($key % 17 == 0 && $key != 0) $this->add_to_body('</ul>
				</li>
				<li class="slide" style="display: none; opacity: 0;"><ul>');

			$num = $key + 1;
			$date = explode(" ", $payment['dateComplete'])[0];
			$date = explode("-", $date);
			$date = implode(".", array_reverse($date));
			$time = explode(" ", $payment['dateComplete'])[1];
			$time = substr($time, 0, 5);

			$this->add_to_body('<li>' . $num . '. Пополнение счёта ' . $date . " (" . $time . ') <span class="sum">+' . $payment['sum'] . "RUB</span></li>");
		}
		$this->add_to_body('</ul></li></ul><div class="prev disabled" onclick="SliderPrev(this);"></div>
			<p class="current">1</p>
			<div class="next ');
		if(count($response) <= 17) $this->add_to_body('disabled');
		$this->add_to_body('" onclick="SliderNext(this);"></div><a href="donate.php">Пополнить баланс</a>
			</div>
			</li>');
	}

	// выводим данные о лидерах организаций
	public function compile_ucp_leaders($response){
		$this->add_to_body('<li class="page leaders-page">
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
			$this->add_to_body('<li><div class="nick ' . $online . '">' . $leader['Names'] . '</div>');
			$this->add_to_body('<div class="organization">' . $this->fraction_name[$leader['Job']] . '</div>');
			$this->add_to_body('<div class="last-enter">' . $leader['pDay'] .'</div></li>');
		}
		$this->add_to_body('</ul></li></ul>
			<div class="prev disabled" onclick="SliderPrev(this);"></div>
			<div class="current">1</div>
			<div class="next ');
		if(count($response) <= 17) $this->add_to_body("disabled");
		$this->add_to_body('" onclick="SliderNext(this);"></div>
			</div>
			</li>');
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

	// Сброс точки спавна
	public function spawn_reset(){
		if(isset($_POST['spawn_reset'])){
			// Сбрасываем место спавна
			$this->db_set('Spawn', '2', $_SESSION['username']);
		}
	}

	// Составляем подвал
	public function compile_footer(){
		
		$this->add_to_body('<footer>
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

	// Функция авторизации
	public function auth(){
		// Проверяем форму авторизации если она задействована
		if(isset($_POST['login']) && isset($_POST['password'])){
			// Записываем логин и пароль в переменную
			$login = $_POST['login'];
			$password = $_POST['password'];

			// 1 шаг - проверка форм на валидность
			if(strlen($login) < 1 || strlen($password) < 1) $this->auth_error[] = "Заполните все поля";

			if(!$this->auth_error){
				// 2 шаг - проверка на то, существует ли такой пользователь

				// Подключаемся к БД
				$this->db_connect();

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
		if(isset($_POST['new_email'])){
			// Записываем все в переменные
			$new_email = $_POST['new_email'];
			if(!filter_var($new_email, FILTER_VALIDATE_EMAIL)) $this->email_change_error[] = "Некорректо введён адрес почты";
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
			echo $message;
		}
		// Пользователь перешел по ссылке восстановления почты
		if(isset($_GET['email_change'])){
			$this->db_connect();
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

	// Функция осуществления доната
	public function donate(){
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
			if($sum < -1 || $sum > 10000) $this->donate_error[] = "Допустимая сумма платежа: от 1 до 10.000 рублей";

			if(!$this->donate_error){
				// Шаг 2 - Проверка логина на существование в БД

				// Подклюяаемся к БД
				$this->db_connect();

				// Получаем ID из БД через логин, если что-то получим, значит такой логин сущетвует
				$id_db = $this->db_get('players', "ID", $username);

				// Проверка
				if(!isset($id_db)) $this->donate_error[] = "Игрок с таким никнеймом не существует";
			}
			if(!$this->donate_error){
				// 3 шаг - Вывод страницы подтверждения платежа
				$this->add_to_body('
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
			} else {
				// Вывод ошибки

				// Первая ошибка в массиве
				$error_first = array_shift($this->donate_error);
				// Ошибка непостановки галочки соглашения
				$tick_error = ($error_first == "Галочку не поставил") ? true : false;

				// Переменная, показывающая ошибку в поле с никнеймом
				$username_error = ($error_first == "Допустимая длина никнейма: 3-20 символов" || $error_first == "Игрок с таким никнеймом не существует") ? true : false;

				// Переменная, показывающая ошибку в поле с суммой
				$sum_error = ($error_first == "Сумма доната не является числом" || $error_first == "Допустимая сумма платежа: от 1 до 10.000 рублей") ? true : false;

				$this->add_to_body('
					<form action="donate.php" method="POST">
					<input type="text" name="username" ');
				// Проверяем причину ошибки, если причина в никнейме, то добавляем полю с никнеймом класс с ошибкой
				if($username_error) $this->add_to_body('class="error"');
				$this->add_to_body('placeholder="');
				// Добавляем в placeholder либо стандартный текст либо "Стандартный текст, раннее введенный текст"
				if($username_error) $this->add_to_body("Ник? " . $username);
				else $this->add_to_body("Ник");
				$this->add_to_body('" value="');
				// Изменяем value на текст ошибки или на раннее введенный текст
				if($username_error) $this->add_to_body($error_first);
				else $this->add_to_body($username);
				$this->add_to_body('"
					onclick="InputCloseError(this)" oninput="CheckInput(this);">
					<input type="text" name="sum" ');
				// Проверяем причину ошибки, если причина в сумме доната, то добавляем полю с суммой класс с ошибкой
				if($sum_error) $this->add_to_body('class="error"');
				$this->add_to_body(' placeholder="');
				// Добавляем в placeholder либо стандартный текст либо "Стандартный текст, раннее введенную сумму"
				if($sum_error) $this->add_to_body("Сумма, (Руб)? " . $sum);
				else $this->add_to_body("Сумма, (Руб)");
				$this->add_to_body('" value="');
				// Изменяем value на текст ошибки или на раннее введенную сумму
				if($sum_error) $this->add_to_body($error_first);
				else $this->add_to_body($sum);
				$this->add_to_body('" onclick="InputCloseError(this);" oninput="CheckInput(this);">
					<div class="agreement">
					<label class="confirm">
					<input type="checkbox" name="agree" value="confirm">
					<span class="visible ');
				// Если не поставлена галочка, выделяем поле с ней
				if($tick_error) $this->add_to_body("error");
				$this->add_to_body('" onclick="Tick(this);">
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
		} else {
			// Просто выводим форму
			$this->add_to_body('
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
	}

	// Функция обработки страницы статуса доната
	public function donate_status(){
		// Если успешная оплата или ошибка при оплате
		if(isset($_GET['paymentId']) && isset($_GET['account']) && isset($_GET['type'])){
			// Создаем переменные хранящие GET параметры
			$type = $_GET['type'];

			if($type == "SUCCESS"){
				// Страница успешной оплаты

				// Добавляем оставшееся от страницы доната, открываем модальное окно ошибки
				$this->add_to_body('
					<form>
					<input type="text" class="" placeholder="Ник" onclick="InputCloseError(this);" oninput="CheckInput(this);">
					<input type="text" class="" placeholder="Сумма (Руб)" onclick="InputCloseError(this);" oninput="CheckInput(this);">
					<div class="agreement">
					<label class="confirm">
					<input type="checkbox">
					<span class="visible" onclick="Tick(this);">
					<span class="tick"></span>
					</span>
					</label>
					<p>Я изучил и принял <a href="#">пользовательское соглашение</a></p>
					</div>
					<button type="submit">Продолжить</button>
					</form>
					</div>
					</header>
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
					$this->add_to_body('
						<form>
						<input type="text" class="" placeholder="Ник" onclick="InputCloseError(this);" oninput="CheckInput(this);">
						<input type="text" class="" placeholder="Сумма (Руб)" onclick="InputCloseError(this);" oninput="CheckInput(this);">
						<div class="agreement">
						<label class="confirm">
						<input type="checkbox">
						<span class="visible" onclick="Tick(this);">
						<span class="tick"></span>
						</span>
						</label>
						<p>Я изучил и принял <a href="#">пользовательское соглашение</a></p>
						</div>
						<button type="submit">Продолжить</button>
						</form>
						</div>
						</header>
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

	public function add_to_body($data){
		$this->body .= $data;
	}

	public function add_to_head($head){
		$this->head .= $head;
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

	public function db_connect_ret($server_id){
		$db = @new mysqli(
			$this->server_data[$server_id]['db_hostname'],
			$this->server_data[$server_id]['db_username'],
			$this->server_data[$server_id]['db_password'],
			$this->server_data[$server_id]['db_database']
		);

		if(mysqli_connect_errno()){
			die("failed to connect database");
		}

		$db->query("SET NAMES cp1251;");
		$db->query("SET SESSION character_set_server = 'utf8';");

		return $db;
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

	public function get_password_hash($password, $account_salt){
		return strtoupper(hash("sha256", $password."_".$account_salt."_".$this->account_system_salt));
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
