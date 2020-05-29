/*	------- Открытие и закрытие выпадающего меню -------	*/

function DropdownToggle(elem){
	// Кнопка меню (три полосочки)
	const burger 		= document.querySelector("header .nav .dropdown-burger");
	// Блок затемнения фона
	const darkness 	= document.querySelector(".darkness");
	// Само выпадающее меню
	const menu			= document.querySelector("header .nav .dropdown-menu");

	if(elem == burger){
		// Открываем менюшку
		document.body.style.overflow = "hidden"; // Убираем прокрутку страницы
		darkness.style.display 	= "block";
		menu.style.transform 		= "translate(0, 0)";
		setTimeout(function(){darkness.style.opacity	= "1";}, 50);
	} else {
		// Закрываем менюшку
		document.body.style.overflow = "visible"; // Убираем прокрутку страницы
		menu.style.transform = "translate(-101%, 0)";
		darkness.style.opacity = "0";
		setTimeout(function(){darkness.style.display = "none";}, 550);
	}

}
/*	--------------------- Прокрутка вверх ------------------	*/
function ScrollTop(){
	// Прокручиваем в начало
	$('html, body').animate({scrollTop: 0}, 1000);
}

function ScrollHTP(){
	$('html, body').animate({scrollTop: $("#start").offset().top}, 1000);
}

/*	---- Открытие и закрытие модального окна авторизации ----	*/

function Auth(){		// login
	// Окно авторизации
	const modal = document.querySelector("#auth");
	// Темнота
	const darkness = document.querySelector(".darkness");
	// Крестик выхода
	const exit = document.querySelector(".exit");

	if(darkness.style.display != "block"){
		document.body.style.overflow = "hidden"; // Убираем прокрутку страницы
		darkness.style.display = "block";
		modal.style.display = "block";
		ScrollTop();
		setTimeout(function(){darkness.style.opacity = "1";}, 50);
		setTimeout(function(){modal.style.opacity = "1";}, 50);
	} else {
		document.body.style.overflow = "visible"; // Убираем прокрутку страницы
		modal.style.opacity = "0";
		darkness.style.opacity = "0";
		setTimeout(function(){darkness.style.display = "none";}, 550);
		setTimeout(function(){modal.style.display = "none";}, 550);
	}

}

/* --------------- Галочка в форме	--------------------- */

function Tick(elem){
	// Чекбокс
	const chbox = document.querySelector("header form .confirm input");
	// Галочка
	const tick  = document.querySelector("header form .confirm .visible .tick");

	if(chbox.checked) tick.style.opacity = "0";
	else tick.style.opacity = "1";
	console.log(chbox.checked);
}
function CheckInput(input){		// Функция проверки заполнения поля ввода
	if(input.value.length > 0) input.classList.add("filled");
	else input.classList.remove("filled");
}
function ShowPassword(eye, input_index){		// Функция скрытия и показывания пароля
	const input = eye.parentNode.children[input_index];

	if(input.type == "password"){
		input.type = "text";
		eye.style.opacity = "1";
	} else {
		input.type = "password";
		eye.style.opacity = "0.5";
	}
}

/* --------------- Переключение вкладок в разделе stats --------------------- */

function PageSwitch(page_nav){

	// Ниже будет много раз упомянуто слово новый. Здесь новый значит тот, который станет активным после нажатия

	const nav 				= document.querySelector(".pages-nav");			// Блок навиации
	const nav_items		= nav.children;															// Все элементы навигации
	const pages				= document.querySelector(".pages");					// Блок вкладок
	const pages_items	= pages.children;														// Все вкладки
	let new_index;																								// Индекс новой вкладки

	for(let i = 0; i < nav_items.length; i++){
		if(nav_items[i] == page_nav) new_index = i;
	}

	const new_nav_item	=	page_nav;										// Новый элемент навигации
	const new_pages_item 			= pages.children[new_index];	// Новая вкладка

	// Действия

	// Делаем все элементы навигации неактивными
	for(i = 0; i < nav_items.length; i++){
		nav_items[i].classList.remove("active");
	}
	// Делаем новый элемент активным
	new_nav_item.classList.add("active");

	// Скрываем все вкладки
	for(i = 0; i < pages_items.length; i++){
		pages_items[i].classList.remove("current");
	}
	// Делаем новый элемент активным
	new_pages_item.classList.add("current");

}

/* --------------- Слайдеры --------------------- */

function SliderNext(btn){

	const slider 				= btn.parentNode;											// Весь слайдер
	const prev					= slider.querySelector(".prev");			// Кнопка "Назад"
	const next 					= btn;																// Кнопка "Вперед"
	const current 			= slider.querySelector(".current");		// Элемент, показывающий номер текущего слайда
	const slides 				= slider.querySelectorAll(".slide");	// Все слайды
	const slides_count 	= slides.length;											// Количество слайдов

	let current_index	 	= current.innerHTML;									// Номер текущего слайда

	const current_slide	= slides[current_index - 1];					// Текущий слайд
	if(next.classList.contains("disabled")) return;
	const next_slide		= slides[current_index];							// Следующий слайд

	// Действия со всем этим

	// Скрываем текущий слайд
	current_slide.style.opacity = "0";
	setTimeout(() => {
		current_slide.style.display = "none";
		// Показываем следующий слайд
		next_slide.style.display = "flex";
		setTimeout(() => {next_slide.style.opacity = "1";}, 50);
	}, 350);
	// Изменяем номер текущей страницы
	current.innerHTML = ++current_index;
	// Меняем классы для кнопок
	if(current_index > 1) prev.classList.remove("disabled");
	else prev.classList.add("disabled");
	if(current_index < slides_count) next.classList.remove("disabled");
	else next.classList.add("disabled");

}

function SliderPrev(btn){

	const slider 				= btn.parentNode;											// Весь слайдер
	const prev					= btn;																// Кнопка "Назад"
	const next 					= slider.querySelector(".next");			// Кнопка "Вперед"
	const current 			= slider.querySelector(".current");		// Элемент, показывающий номер текущего слайда
	const slides 				= slider.querySelectorAll(".slide");	// Все слайды
	const slides_count 	= slides.length;											// Количество слайдов

	let current_index	 	= current.innerHTML;									// Номер текущего слайда

	const current_slide	= slides[current_index - 1];					// Текущий слайд
	if(prev.classList.contains("disabled")) return;
	const prev_slide		= slides[current_index - 2];					// Предыдущий слайд

	// Действия со всем этим

	// Скрываем текущий слайд
	current_slide.style.opacity = "0";
	setTimeout(() => {
		current_slide.style.display = "none";
		// Показываем предыдущий слайд
		prev_slide.style.display = "flex";
		setTimeout(() => {prev_slide.style.opacity = "1";}, 50);
	}, 350);
	// Изменяем номер текущей страницы
	current.innerHTML = --current_index;
	// Меняем классы для кнопок
	if(current_index > 1) prev.classList.remove("disabled");
	else prev.classList.add("disabled");
	if(current_index < slides_count) next.classList.remove("disabled");
	else next.classList.add("disabled");

}

// Активация фильтров платежей

function ActivateFilter(filter){
	// Массив всех платежей
	let payments 				= Array.from(document.querySelectorAll(".payments-page .slide ul li"));

	// Массив всех слайдов с платежами
	const blocks				= document.querySelectorAll(".payments-page .slide ul");

	// Сначала старые - 1; Сначала новые - 0
	const sort_type			=	filter.innerHTML == "Сначала новые" ? false : true;

	// Все слайды платежей
	const slides 				= document.querySelectorAll(".payments-page .slide");

	// Элемент, показывающий номер текущего слайда
	const current 			= document.querySelector(".payments-page .current");

	// Кнопки вперед и назад
	const prev					= document.querySelector(".payments-page .prev");
	const next 					= document.querySelector(".payments-page .next");

	// Убираем все слайды
	slides.forEach((slide) => {
		slide.style.opacity = "0";
		setTimeout(() => {
			slide.style.display = "none";
		}, 350);
	});

	// Возвращаемся на первый слайд
	current.innerHTML = "1";

	// Корректируем кнопки
	if(slides.length > 1){
		prev.classList.add("disabled");
		next.classList.remove("disabled");
	}

	// Сортировка платежей
	payments.sort((a, b) => {
		// Превращаем DOM элементы в строки, сразу нафиг обрезаем денежное значение платежа, оно нам не важно
		let a_text = a.innerHTML.split(" <")[0];
		let b_text = b.innerHTML.split(" <")[0];
		// Дата платежа в текстовом формате
		let date_a = a_text.split(" ")[3];
		let date_b = b_text.split(" ")[3];
		// Время платежа в текстовом формате
		let time_a = a_text.split(" ")[4];
		let time_b = b_text.split(" ")[4];
		// Убираем скобочки, только будут мешать
		time_a = time_a.substring(1, time_a.length - 1);
		time_b = time_b.substring(1, time_b.length - 1);
		// Переведем дату и время в целочисленную переменную для удобного сравнения
		date_a = date_a.split(".");
		date_b = date_b.split(".");

		time_a = time_a.split(":");
		time_b = time_b.split(":");

		date_a = date_a.reverse();
		date_b = date_b.reverse();

		int_time_a = date_a.join("") + time_a.join("");
		int_time_b = date_b.join("") + time_b.join("");

		// Сравниваем те самые целочисленные переменные, та, что больше - новее

		if(sort_type){
			if(int_time_a < int_time_b) return -1;
			else return 1;
		} else {
			if(int_time_a < int_time_b) return 1;
			else return -1;
		}

	});

	// Убираем порядковый номер из каждого платежа, т.к порядок будет новый
	for(let i = 0; i < payments.length; i++){
		payments[i] = payments[i].innerHTML.split(". ")[1];
	}
	setTimeout(() => {

	// Внедряем платежи в слайды
	for(let i = 0; i < slides.length; i++){
		slides[i].querySelector("ul").innerHTML = "";
			// 17 платежей может быть максимально на одном слайде
			for(let j = 0; j < 17; j++){
				if(i * 17 + j >= payments.length) break;
				let elem = document.createElement("li");
				elem.innerHTML = (i * 17 + j + 1) + ". " + payments[i * 17 + j];
				slides[i].querySelector("ul").append(elem);
			}
		}
	}, 350);

	// Показываем первый слайд
	setTimeout(() => {
		slides[0].style.display = "flex";
		setTimeout(() => {slides[0].style.opacity = "1";}, 50);
	}, 400);

}

/* Убираем ошибку в поле ввода */

function InputCloseError(input){
	// Проверяем текстовое поле на соддержание класса ошибки
	if(input.classList.contains("error")){
		// Удаляем класс
		input.classList.remove("error");
		// Получаем новый placeholder и text
		let placeholder = input.placeholder.split("? ")[0];
		let text = input.placeholder.split("? ")[1];
		// Применяем их к полю
		input.value = text;
		input.placeholder = placeholder;
		// Проверим на введенные данные, чтобы оставить поле активным если что
		if(input.value.length > 0) input.classList.add("filled");
	}
}

/* Скрытие модального окна */

function CloseModal(close){
	// Модальное окно
	const modal = close.parentNode;
	// Темнота
	const darkness = document.querySelector('.darkness');

	// Закрываем модальное окно
	modal.style.opacity = "0";
	setTimeout(() => {modal.style.display = "none"}, 300);
	// Закрываем темноту
	darkness.style.opacity = "0";
	setTimeout(() => {darkness.style.display = "none"}, 300);
	// overflow убираем
	document.body.style.overflow = "visible";
}

// Закрыть все модальные окна

function CloseModals(){
	const auth = document.querySelector("#auth");
	const pass_change = document.querySelector("#password_change");
	const email_change = document.querySelector("#email_change");
	const error = document.querySelector("#error");
	const success = document.querySelector("#success");
	const agreement = document.querySelector("#agreement");
	const leader_tools = document.querySelector("#leader_actions");
	let ticks		= document.querySelectorAll(".leader .leaders .select");

	const darkness = document.querySelector(".darkness");

	// Закрываем все
	if(auth != undefined){
		auth.style.opacity = "0";
		setTimeout(() => {auth.style.display = "none"}, 300);
	}
	if(pass_change != undefined){
		pass_change.style.opacity = "0";
		setTimeout(() => {pass_change.style.display = "none"}, 300);
	}
	if(email_change != undefined){
		email_change.style.opacity = "0";
		setTimeout(() => {email_change.style.display = "none"}, 300);
	}
	if(success != undefined){
		success.style.opacity = "0";
		setTimeout(() => {success.style.display = "none"}, 300);
	}
	if(error != undefined){
		error.style.opacity = "0";
		setTimeout(() => {error.style.display = "none"}, 300);
	}
	if(agreement != undefined){
		agreement.style.opacity = "0";
		setTimeout(() => {agreement.style.display = "none"}, 300);
	}
	if(leader_tools != undefined){
		leader_tools.style.opacity = "0";
		setTimeout(() => {leader_tools.parentNode.removeChild(leader_tools);}, 300);
	}
	if(ticks.length > 0){
		ticks.forEach((tick) => {
			tick.classList.remove("active");
		});
	}


	// Скрываем темноту

	darkness.style.opacity = "0";
	setTimeout(() => {darkness.style.display = "none"}, 300);

}

// Открываем окно смены пароля

function OpenPasswordChangeMenu(){
	const modal = document.querySelector("#password_change");
	const darkness = document.querySelector(".darkness");

	// Показываем всё

	ScrollTop();
	modal.style.display = "block";
	darkness.style.display = "block";
	setTimeout(() => {modal.style.opacity = "1";}, 50);
	setTimeout(() => {darkness.style.opacity = "1";}, 50);

	// запрещаем прокрутку

	document.body.style.overflow = "hidden";
}

// Открываем окно смены мыла

function OpenEmailChangeMenu(){
	const modal = document.querySelector("#email_change");
	const darkness = document.querySelector(".darkness");

	// Показываем всё

	ScrollTop();
	modal.style.display = "block";
	darkness.style.display = "block";
	setTimeout(() => {modal.style.opacity = "1";}, 50);
	setTimeout(() => {darkness.style.opacity = "1";}, 50);

	// запрещаем прокрутку

	document.body.style.overflow = "hidden";
}

// Открываем окно пользовательского соглашения
function OpenAgreement(){
	const modal			= document.querySelector("#agreement");
	const darkness	= document.querySelector(".darkness");

	ScrollTop();
	darkness.style.display = "block";
	setTimeout(() => {darkness.style.opacity = "1"}, 50);
	modal.style.display = "block";
	setTimeout(() => {modal.style.opacity = "1"}, 50);

	document.body.overflow = "hidden";
}

function OpenTools(obj){
	const username = obj.querySelector(".info .nick").innerHTML;
	const data = {
		type: "get_tools",
		username: username
	};

	$.ajax({
		type: "POST",
		url: "../site_engine/ajax_handler.php",
		data: data,
		success: (callback) => {
			if(callback != "error"){

				// Close all other modals
				const modals = document.querySelectorAll("#leader_actions");
				modals.forEach((modal) => {
					modal.parentNode.removeChild(modal);
				});

				// Unselect all ticks
				let ticks	= document.querySelectorAll(".leader .leaders .select");
				ticks.forEach((tick) => {
					tick.classList.remove("active");
				});

				const root = document.querySelector(".leader .leaders");
				let modal = document.createElement("div");
				modal.id = "leader_actions";
				modal.innerHTML = callback;
				modal.style.opacity = "0";

				Select(obj);
				root.appendChild(modal);
				setTimeout(() => {modal.style.opacity = "1"}, 50);
				if(window.innerWidth < 1024){
					darkness = document.querySelector(".darkness");
					darkness.style.display = "block";
					setTimeout(() => {darkness.style.opacity = "1";}, 50);
				}
			}
		}
	});
}

function Select(obj){
	let visible = obj.querySelector(".select");
	visible.classList.add("active");
}

function OpenCloseDopRang(obj){
	const others = obj.querySelector(".others");
	const opened = others.style.opacity == "0" ? false : true;

	if(!opened){
		obj.style.borderRadius = "5px 5px 0 0";
		obj.style.borderBottom = "1px solid rgba(0, 0, 0, 0.0)";
		others.style.display = "block";
		setTimeout(() => {others.style.opacity = "1";}, 50);
	} else {
		obj.style.borderRadius = "5px";
		obj.style.border = "1px solid #E8E9F0";
		others.style.opacity = "0";
		setTimeout(() => {others.style.display = "none";}, 550);
	}
}

function SelectDopRang(obj){
	const root 			= obj.parentNode.parentNode.parentNode.parentNode;
	const username 	= root.querySelector("h1 b").innerHTML;
	const new_value = obj.innerHTML;
	const data = {
		type: "doprangchange",
		username: username,
		value: new_value
	}
	$.ajax({
		url: "../site_engine/ajax_handler.php",
		type: "POST",
		data: data,
		success: (response) => {
			if(response != "error"){
				// Change text in main window
				let nicks = root.parentNode.querySelectorAll("li .nick");

				nicks.forEach((nick, key) => {
					if(nick.innerHTML == username){
						let rang_elem = nick.parentNode.querySelector(".rank");
						const rang = rang_elem.innerHTML.split("[")[0];
						rang_elem.innerHTML = rang + "[" + new_value + "]";
					}
				});
				// Change text in #leader_actions select
				let current = root.querySelector(".doprang .current");
				let others	= root.querySelectorAll(".doprang .others li");

				others.forEach((other) => {
					if(other.innerHTML == new_value) other.innerHTML = current.innerHTML;
				});
				current.innerHTML = new_value;
			}
		}
	});
}

// AJAX

function Raise(btn){

	let modal = btn.parentNode.parentNode;
	const username = modal.querySelector("h1 b").innerHTML;
	const data = {
		type: "raise",
		username: username
	}
	$.ajax({
		type: "POST",
		url: "../site_engine/ajax_handler.php",
		data: data,
		success: (response) => {
			const nicks = modal.parentNode.querySelectorAll("li .nick");
			let current_index;

			nicks.forEach((nick, index) => {
				if(nick.innerHTML == username) current_index = index;
			});

			let rang = modal.parentNode.querySelectorAll("li .rank")[current_index];

			rang.innerHTML = response;

		}
	});
}

function Lower(btn){

	let modal = btn.parentNode.parentNode;
	const username = modal.querySelector("h1 b").innerHTML;
	const data = {
		type: "lower",
		username: username
	}
	$.ajax({
		type: "POST",
		url: "../site_engine/ajax_handler.php",
		data: data,
		success: (response) => {
			const nicks = modal.parentNode.querySelectorAll("li .nick");
			let current_index;

			nicks.forEach((nick, index) => {
				if(nick.innerHTML == username) current_index = index;
			});

			let rang = modal.parentNode.querySelectorAll("li .rank")[current_index];

			rang.innerHTML = response;

		}
	});
}

function Fire(btn){
	let modal 			= btn.parentNode.parentNode;
	const username 	= modal.querySelector("h1 b").innerHTML;
	const data = {
		type: "fire",
		username: username
	}
	$.ajax({
		type: "POST",
		url: "../site_engine/ajax_handler.php",
		data: data,
		success: (response) => {
			if(response != "error"){
				const nicks = modal.parentNode.querySelectorAll("li .nick");
				let current_index;
				nicks.forEach((nick, index) => {
					if(nick.innerHTML == username) current_index = index;
				});
				const line = modal.parentNode.querySelectorAll("ul li")[current_index];
				line.style.opacity = "0";
				setTimeout(() => {
					line.style.display = "none";
				}, 800);
				CloseModals();
			}
		}
	});
}

function BlackList(btn){
	let modal 			= btn.parentNode.parentNode;
	const username 	= modal.querySelector("h1 b").innerHTML;

	const data = {
		type: "blacklist",
		username: username
	}
	console.log(data);

	$.ajax({
		type: "POST",
		url: "../site_engine/ajax_handler.php",
		data: data,
		success: (response) => {
			console.log(response);
		}
	});

}