<?php
if($page == "TRANSFER"){
	$this->add_to_template('
		<section class="sa-text-area">
		<center>
		<div class="main-block">');

	if(isset($_SESSION['auth']) && $_SESSION['auth'] == "YES"){
		$this->add_to_template('<div class="main-block-sub-ucp">');
		$this->add_to_template('<p class="block-title" style="margin-bottom: 10px;">Личный кабинет</p>');
		$this->add_to_template('<h4>Перенос аккаунта: '.$_SESSION['name'].'</h4>');

		$this->db_connect($_SESSION['srv']);

		$response = $this->db->query("SELECT * FROM players WHERE Names = '".$_SESSION['name']."'");

		if($_SESSION['srv'] == 1 && $response->num_rows){
			$server_id = $_SESSION['srv'];
			$username = $_SESSION['name'];

			$data = $response->fetch_assoc();

			if($data['Bank'] < 0){
				$data['Bank'] = 0;
			}

			if($data['transfer_complete'] < 2)
			{
				$allowed = true;
				$report_data = array();

						// ========

				$first_srv = $this->db_connect_ret(0);

				if($first_srv->query("SELECT * FROM players WHERE Names = '".$username."'")->num_rows)
				{
					$allowed = false;
				}

				$first_srv->close();

						// ========

				$report_data[] = 'Аккаунт с никнеймом '.$username.' <b><font color="green">будет</font></b> перенесен со второго на первый сервер';

						// ========

				$houses_data = $this->db->query("SELECT * FROM houses WHERE hOwner = '".$username."'");

				for($i = 0; $i < $houses_data->num_rows; $i++)
				{
					$house_data = $houses_data->fetch_assoc();

					$report_data[] = 'Дом/квартира #'.$house_data['hID'].' перенесен <b><font color="red">не будет</font></b>';
				}

						// ========

				$garages_data = $this->db->query("SELECT * FROM garage WHERE Owner = '".$username."'");

				for($i = 0; $i < $garages_data->num_rows; $i++)
				{
					$garage_data = $garages_data->fetch_assoc();

					$report_data[] = 'Гараж #'.$garage_data['ID'].' перенесен <b><font color="red">не будет</font></b>';
				}

						// ========

				$bizz_data = $this->db->query("SELECT * FROM bizz WHERE Owner = '".$username."'");

				for($i = 0; $i < $bizz_data->num_rows; $i++)
				{
					$biz_data = $bizz_data->fetch_assoc();

					$report_data[] = 'Бизнес #'.$biz_data['ID'].' перенесен <b><font color="red">не будет</font></b>';
				}

						// ========

				$cars_data = $this->db->query("SELECT * FROM cars WHERE Owner = '".$username."'");

				for($i = 0; $i < $cars_data->num_rows; $i++)
				{
					$car_data = $cars_data->fetch_assoc();

					$report_data[] = 'Машина #'.$car_data['ID'].' ('.$car_data['Model'].') <b><font color="green">будет</font></b> перенесена со второго на первый сервер';
				}

						// ========

				$cars_data = $this->db->query("SELECT * FROM cars WHERE 2Owner = '".$username."'");

				for($i = 0; $i < $cars_data->num_rows; $i++)
				{
					$car_data = $cars_data->fetch_assoc();

					$report_data[] = 'Машина #'.$car_data['ID'].' ('.$car_data['Model'].') перенесена <b><font color="red">не будет</font></b> (вы со-владелец)';
				}

						// ========

				if($data['transfer_complete'] == 1)
				{
					$nickname = $data['transfer_nickname'];

					$this->db->query("UPDATE players SET transfer_complete = 2 WHERE Names = '".$username."'");

					$first_srv = $this->db_connect_ret(0);

							// ========

					$transfer_data = $data;

					unset($transfer_data['ID']);
					unset($transfer_data['transfer_complete']);
					unset($transfer_data['transfer_nickname']);
					unset($transfer_data['Names']);

					$transfer_data['housenalog'] = 0;
					$transfer_data['bizznalog'] = 0;

					$query_list = "`Names`";
					$query_data = "'".$nickname."'";

					$keys = array_keys($transfer_data);

					for($i = 0; $i < sizeof($keys); $i++)
					{
						$query_list .= ",`".$keys[$i]."`";
						$query_data .= ",'".$transfer_data[$keys[$i]]."'";
					}

					$first_srv->query("INSERT INTO `players`(".$query_list.") VALUES(".$query_data.")");

							// ========

					$cars_data = $this->db->query("SELECT * FROM cars WHERE Owner = '".$username."'");

					for($idx = 0; $idx < $cars_data->num_rows; $idx++)
					{
						$transfer_data = $cars_data->fetch_assoc();

						unset($transfer_data['ID']);
						unset($transfer_data['Owner']);

						$transfer_data['2Owner'] = 'No-Body';

						$query_list = "`Owner`";
						$query_data = "'".$nickname."'";

						$keys = array_keys($transfer_data);

						for($i = 0; $i < sizeof($keys); $i++)
						{
							$query_list .= ",`".$keys[$i]."`";
							$query_data .= ",'".$transfer_data[$keys[$i]]."'";
						}

						$first_srv->query("INSERT INTO `cars`(".$query_list.") VALUES(".$query_data.")");
					}

							// ========

					$first_srv->close();

					$_SESSION['auth'] = "YES";
					$_SESSION['srv'] = 0;
					$_SESSION['name'] = $nickname;

					$this->add_header('<meta http-equiv="refresh" content="10;URL=ucp.php">');
					$this->add_to_template('<div class="alert alert-success" role="alert"><b>Успешно!</b><br/><br/>Ваш аккаунт перенесен на первый сервер с никнеймом <b>'.$nickname.'</b><br/>Вы будете переброшены на страницу Личного Кабинета через 10 секунд</div>');
				}
				else
				{
					$form = true;

					$this->add_to_template("<h5><b>Вы собираетесь выполнить перенос аккаунта на первый сервер</b></h5>");

					if(isset($_POST['submit']))
					{
						$nickname = $username;

						if(!$allowed && isset($_POST['nickname']))
						{
							$nickname = trim($_POST['nickname']);
						}

						if(strlen($nickname) >= 3 && strlen($nickname) <= 20)
						{
							if(preg_match("#^[aA-zZ0-9\-_\]\[\$\=\(\)\@\.]+$#", $nickname))
							{
								$already_exists = false;
								$first_srv = $this->db_connect_ret(0);

								if($first_srv->query("SELECT * FROM players WHERE Names = '".$nickname."'")->num_rows)
								{
									$already_exists = true;
								}

								$first_srv->close();

								if(!$already_exists)
								{
									$form = false;

									$this->add_header('<meta http-equiv="refresh" content="5;URL=transfer.php">');
									$this->add_to_template('<div class="alert alert-info" role="alert"><b>Пожалуйста, подождите...</b><br/><br/>Ваш аккаунт переносится на первый сервер.<br/>Ни в коем случае не закрывайте эту вкладку или браузер.</div>');

									$this->db->query("UPDATE players SET transfer_complete = 1, transfer_nickname = '".$nickname."' WHERE Names = '".$username."'");
								}
								else
								{
									$this->add_to_template('<div class="alert alert-danger" role="alert"><b>Ошибка: </b>аккаунт с указанным никнеймом уже существует на первом сервере</div>');
								}
							}
							else
							{
								$this->add_to_template('<div class="alert alert-danger" role="alert"><b>Ошибка: </b>новый никнейм содержит запрещенные символы</div>');
							}
						}
						else
						{
							$this->add_to_template('<div class="alert alert-danger" role="alert"><b>Ошибка: </b>неверная длина нового никнейма</div>');
						}
					}

					if($form)
					{
						$this->add_to_template('<div id="ucp_main_left" class="ucp-main-left" style="width: 70%;">');
						$this->add_to_template('
							<table class="table" width="100%" style="border-left: 1px solid #eeeeee; font-size: 10pt;">
							<tr><td><b>Условия и примечания:</b></td></tr>
							<tr>
							<td>
							<b>1. </b>Недвижимость (дом, квартира, бизнес) не переносятся.
							<br/><b>2. </b>Вам будет предложено изменение никнейма в том случае, если на первом сервере уже будет аккаунт с текущим никнеймом.
							</td>
							</tr>
							</table>
							');

						$this->add_to_template('
							<table class="table" width="100%" style="border-left: 1px solid #eeeeee; font-size: 10pt;"><tr><td><b>Результат проверки:</b></td></tr><tr><td>
							');

						if($allowed)
						{
							$this->add_to_template('Аккаунт <b><font color="green">допускается</font></b> к переносу на первый сервер.');
						}
						else
						{
							$this->add_to_template('Аккаунт <b><font color="green">допускается</font></b> к переносу на первый сервер, но <b><font color="#DF5A3E;">требуется</font></b> изменение никнейма.');
						}

						$this->add_to_template('<br/><br/><b>Будут выполнены следующие действия:</b><br/>');

						for($i = 0; $i < sizeof($report_data); $i++)
						{
							$this->add_to_template('<b>'.($i + 1).'. </b>'.$report_data[$i].'.<br/>');
						}

						$this->add_to_template('</td></tr></table>');
						$this->add_to_template('</div>');

						$this->add_to_template('<div id="ucp_main_right" class="ucp-main-right">');
						$this->add_to_template('
							<table class="table" width="100%" style="border-left: 1px solid #eeeeee; font-size: 10pt;">
							<tr><td><b>Аккаунт</b></td><td style="text-align: right;">#'.$data['ID'].'</td></tr>
							<tr><td><b>Имя</b></td><td style="text-align: right;">'.$username.'</td></tr>
							<tr><td><b>E-mail</b></td><td style="text-align: right;">'.$data['Email'].'</td></tr>
							<tr><td><b>Уровень</b></td><td style="text-align: right;">'.$data['Level'].'</td></tr>
							</table>
							');

						$this->add_to_template('<form method="post">');

						if(!$allowed)
						{
							$this->add_to_template('
								<div class="form-group row" style="width: 95%;">
								<input class="form-control" type="input" name="nickname" placeholder="Новый никнейм">
								</div>
								');
						}

						$this->add_to_template('
							<label class="form-check-label">
							<input type="checkbox" class="form-check-input" required>
							Я подтверждаю перенос
							</label>
							');

						$this->add_to_template('<input style="width: 100%;" type="submit" name="submit" class="btn btn-primary" value="Выполнить перенос"/>');

						$this->add_to_template('</form>');

						$this->add_to_template('</div>');
						$this->add_to_template('</div>');
					}
				}
			}
			else
			{
				$this->add_to_template('
					<div class="alert alert-danger" role="alert">
					<b>Ошибка: </b>данный аккаунт уже перенесен.<br/><br/>Никнейм на первом сервере: <b>'.$data['transfer_nickname'].'</b> 
					</div>
					');
			}
		}

		$this->add_to_template('</div>');
	}
	else
	{
		$this->add_to_template('<div class="main-block-sub-login">');
		$this->add_to_template('<p class="block-title" style="margin-bottom: 10px;">Личный кабинет</p>');
		$this->add_to_template('<h4>Перенос аккаунта</h4>');
		$this->add_to_template('<div class="alert alert-danger" role="alert"><b>Ошибка: </b>для доступа к этой странице необходимо авторизироваться<br/><br/><a href="/ucp.php" class="btn btn-danger btn-sm">Авторизироваться</a></div>');
	}

	$this->add_to_template('
		</div>
		</div>
		</center>
		</section>
		');
}