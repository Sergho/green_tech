<?php
$this->add_to_template('
                <section class="sa-text-area">
                <center>
                <div class="main-block">
                ');
if (isset($_SESSION['auth']) && $_SESSION['auth'] == "YES")
{
    $this->add_to_template('<div class="main-block-sub-ucp">');
    $this->add_to_template('<p class="block-title" style="margin-bottom: 10px;">Личный кабинет</p>');
    if (isset($_GET['a']))
    {
        if ($_GET['a'] == "admin" && isset($_GET['p']))
        {
            $server_id = $_SESSION['srv'];
            $this->db_connect($server_id);
            $response = $this
                ->db
                ->query("SELECT * FROM players WHERE Names = '" . $_SESSION['name'] . "'");
            if ($response->num_rows)
            {
                $data = $response->fetch_assoc();
                if ($data['Admin'] > 0)
                {
                    $this->add_to_template('<h4>Панель администратора (сервер №' . ($server_id + 1) . ')</h4>');
                    $this->add_to_template('<div class="ucp-block-left" style="padding: 10px;">');
                    $this->add_to_template('
                                    <div class="ucp-menu-main">
                                    <form method="post" action="ucp.php" class="ucp-menu-btn-exit"><input style="margin-bottom: 2px;" type="submit" name="page_main" class="btn btn-danger btn-sm btn-block" value="Назад в UCP"/></form>
                                    </div>
                                    ');
                    $this->add_to_template('<div class="ucp-menu-pages">');
                    $this->add_to_template('
                                    <a href="ucp.php?a=admin&p=main" style="margin-bottom: 2px;" class="btn btn-primary btn-sm btn-block">Главное</a>
                                    <a href="ucp.php?a=admin&p=ucplogs" style="margin-bottom: 2px;" class="btn btn-primary btn-sm btn-block">История UCP</a>
                                    <a href="ucp.php?a=admin&p=players" style="margin-bottom: 2px;" class="btn btn-primary btn-sm btn-block">Игроки</a>
                                    ');
                    $this->add_to_template('</div>');
                    $this->add_to_template('<br/>');
                    $this->add_to_template('</div>');
                    $this->add_to_template('<div class="ucp-block-right" style="padding: 10px;">');
                    switch ($_GET['p'])
                    {
                        case 'main':
                            {
                                    $this->add_to_template('<h5><b>Главное</b></h5>');
                                    $this->add_to_template('
                                            <table class="table" width="100%" style="border-left: 1px solid #eeeeee; font-size: 10pt;">
                                            <tr><td><b>Сервер</b></td><td style="text-align: right;">GreenTech RolePlay #' . ($server_id + 1) . '</td></tr>
                                            <tr><td><b>Аккаунт</b></td><td style="text-align: right;">#' . $data['ID'] . '</td></tr>
                                            <tr><td><b>Никнейм</b></td><td style="text-align: right;">' . $data['Names'] . '</td></tr>
                                            <tr><td><b>Уровень администратора</b></td><td style="text-align: right;">' . $data['Admin'] . '</td></tr>
                                            </table>
                                            ');
                                break;
                            }
                        case 'ucplogs':
                            {
                                $this->add_to_template('<h5><b>История UCP</b></h5>');
                                $this->add_to_template('<table class="table" width="100%" style="font-size: 10pt;">');
                                $this->add_to_template('<tr><td><b>ip</b></td><td style="text-align: center;"><b>никнейм</b></td><td style="text-align: center;"><b>дата</b></td><td style="text-align: right;"><b>действие</b></td></tr>');
                                $start_id = 0;
                                if (isset($_GET['s']))
                                {
                                    $start_id = strval($_GET['s']);
                                }
                                $response = $this
                                    ->db
                                    ->query('SELECT * FROM `ucp_log` ORDER BY `id` DESC LIMIT ' . ($start_id * 30) . ',30');
                                if ($response)
                                {
                                    for ($i = 0;$i < $response->num_rows;$i++)
                                    {
                                        $log_data = $response->fetch_assoc();
                                        $action_name = "неизвестно";
                                        $action_data = json_decode($log_data['params']);
                                        for ($a = 0;$a < sizeof($this->ucp_log_action_names);$a++)
                                        {
                                            if ($log_data['action'] == $this->ucp_log_action_names[$a][0])
                                            {
                                                $action_name = $this->ucp_log_action_names[$a][1];
                                            }
                                        }
                                        $this->add_to_template('<tr><td>' . $log_data['ip'] . '</td><td style="text-align: center;">' . $action_data[2] . '</td><td style="text-align: center;">' . date("d.m.Y H:m:s", $log_data['ts']) . '</td><td style="text-align: right;">' . $action_name . ' <a href="ucp.php?a=admin&p=ucplogdata&i=' . $log_data['id'] . '" class="btn-primary btn-sm">подробнее</a></td></tr>');
                                    }
                                }
                                $this->add_to_template('</table>');
                                $this->add_to_template('<table style="border: none;" width="100%">');
                                $this->add_to_template('<tr>');
                                if ($start_id > 0)
                                {
                                    $this->add_to_template('<td style="padding: 5px; text-align: left;"><b><a class="btn btn-primary btn-sm" href="ucp.php?a=admin&p=ucplogs&s=' . ($start_id - 1) . '">назад</a></b></td>');
                                }
                                $this->add_to_template('<td style="padding: 5px; text-align: right;"><b><a class="btn btn-primary btn-sm" href="ucp.php?a=admin&p=ucplogs&s=' . ($start_id + 1) . '">вперед</a></b></td>');
                                $this->add_to_template('</tr>');
                                $this->add_to_template('</table>');
                                break;
                            }
                        case 'ucplogdata':
                            {
                                if (isset($_GET['i']))
                                {
                                    $this->add_to_template('<h5><b>История UCP</b></h5>');
                                    $response = $this
                                        ->db
                                        ->query('SELECT * FROM `ucp_log` WHERE `id` = \'' . strval($_GET['i']) . '\'');
                                    if ($response)
                                    {
                                        $log_data = $response->fetch_assoc();
                                        $action_name = "неизвестно";
                                        $action_data = json_decode($log_data['params']);
                                        for ($a = 0;$a < sizeof($this->ucp_log_action_names);$a++)
                                        {
                                            if ($log_data['action'] == $this->ucp_log_action_names[$a][0])
                                            {
                                                $action_name = $this->ucp_log_action_names[$a][1];
                                            }
                                        }
                                        $this->add_to_template('
                                                    <table class="table" width="100%" style="border-left: 1px solid #eeeeee; font-size: 10pt;">
                                                    <tr><td><a href="ucp.php?a=admin&p=ucplogs" class="btn-primary btn-sm">назад</a></td><td style="text-align: right;"></td></tr>
                                                    <tr><td><b>ID</b></td><td style="text-align: right;">' . $log_data['id'] . '</td></tr>
                                                    <tr><td><b>Аккаунт</b></td><td style="text-align: right;">' . $action_data[2] . '</td></tr>
                                                    <tr><td><b>Действие</b></td><td style="text-align: right;">' . $action_name . '</td></tr>
                                                    </table>
                                                    ');
                                    }
                                }
                                break;
                            }
                        case 'players':
                            {
                                $this->add_to_template('<h5><b>Игроки</b></h5>');
                                $this->add_to_template('<p>Данный раздел предназначен для управления игроками, находящиеся на сервере. Получение списка игроков может занять до 30 секунд</p>');
                                $this->add_to_template('<p><a class="btn btn-primary btn-sm" href="ucp.php?a=admin&p=plist">Получить список игроков</a></p>');
                                break;
                            }
                        case 'plist':
                            {
                                $this->add_to_template('<h5><b>Список игроков</b></h5>');
                                $players = $this->rcon($server_id, "players");
                                if ($players === false)
                                {
                                    $this->add_to_template('<div class="alert alert-danger" role="alert"><strong>Ошибка: </strong>не удалось получить список игроков, возможно сервер недоступен</div>');
                                }
                                $this->add_to_template('<table class="table" width="100%" style="font-size: 10pt;">');
                                $this->add_to_template('<tr><td><b>ID</b></td><td style="text-align: center;"><b>никнейм</b></td><td style="text-align: center;"><b>IP</b></td><td style="text-align: right;"><b>действия</b></td></tr>');
                                for ($i = 0;$i < count($players);$i++)
                                {
                                    $players[$i] = str_ireplace("\n", "", $players[$i]);
                                    $players[$i] = str_ireplace("\r", "", $players[$i]);
                                    $players[$i] = str_ireplace("\t", " ", $players[$i]);
                                    $players[$i] = str_ireplace("  ", " ", $players[$i]);
                                    $players[$i] = str_ireplace("   ", " ", $players[$i]);
                                    $players[$i] = str_ireplace("    ", " ", $players[$i]);
                                }
                                for ($i = 1;$i < count($players);$i++)
                                {
                                    $player_data = explode(" ", $players[$i]);
                                    $this->add_to_template('<tr><td>' . $player_data[0] . '</td><td style="text-align: center;">' . $player_data[1] . '</td><td style="text-align: center;">' . $player_data[3] . '</td><td style="text-align: right;"><a href="ucp.php?a=admin&p=pkick&i=' . $player_data[0] . '" class="btn-primary btn-sm">кик</a> <a href="ucp.php?a=admin&p=pban&i=' . $player_data[0] . '" class="btn-primary btn-sm">бан</a> <a href="ucp.php?a=admin&p=pinfo&i=' . $player_data[1] . '" class="btn-primary btn-sm">инфо</a></td></tr>');
                                }
                                $this->add_to_template('</table>');
                                break;
                            }
                        case 'pkick':
                            {
                                $result = $this->rcon($server_id, "kick " . strval($_GET['i']));
                                $this->add_to_template('<div class="alert alert-info" role="alert"><strong>Результат: </strong>' . $result[0] . '</div>');
                                break;
                            }
                        }
                        $this->add_to_template('</div>');
                    }
                }
            }
        }
        else
        {
            $this->add_to_template('<h4>' . $_SESSION['name'] . ' (сервер №' . ($_SESSION['srv'] + 1) . ')</h4>');
            $this->db_connect($_SESSION['srv']);
            $response = $this
                ->db
                ->query("SELECT * FROM players WHERE Names = '" . $_SESSION['name'] . "'");
            if ($response->num_rows)
            {
                $server_id = $_SESSION['srv'];
                $username = $_SESSION['name'];
                $data = $response->fetch_assoc();
                if ($data['Bank'] < 0)
                {
                    $data['Bank'] = 0;
                }
                if (isset($_POST['logout']))
                {
                    unset($_SESSION['auth']);
                    unset($_SESSION['name']);
                    unset($_SESSION['srv']);
                    $this->add_header('<meta http-equiv="refresh" content="0;URL=ucp.php">');
                    $this->wlog("logout", array(
                        $data['ID'],
                        $server_id,
                        $data['Names']
                    ));
                }
                if (isset($_POST['change_email_final']) && isset($_POST['new_email']))
                {
                    $row = $response->fetch_assoc();
                    $_POST['new_email'] = trim($_POST['new_email']);
                    if ($_POST['new_email'] == $data['Email'])
                    {
                        $_POST['change_email'] = 1;
                        $this->add_to_template('
                                    <div class="alert alert-danger" role="alert">
                                    <b>Ошибка: </b>указанный e-mail совпадает с текущим
                                    </div>
                                    ');
                    }
                    else
                    {
                        $_SESSION['ce_new_email'] = $_POST['new_email'];
                        $_SESSION['ce_key'] = $this->generate_key(24);
                        $link = "http://greentech-rp.ru/ucp.php?ce=" . $_SESSION['ce_key'];
                        $message = "Здравствуйте!\n\r\n\r";
                        $message .= "Вы запросили изменение e-mail к аккаунту " . $_SESSION['name'] . " на сервере GreenTech RolePlay #" . ($server_id + 1) . ", перейдите по " . $link . " для дальнейших действий.\n\r";
                        $message .= "Если вдруг вы не запрашивали это, проигнорируйте это письмо.\n\r\n\r";
                        $message .= "С Уважением, администрация GreenTech RolePlay.";
                        mail($_POST['new_email'], "Изменение email", $message, "From: admin@greentech-rp.ru");
                        $this->add_to_template('
                                    <div class="alert alert-info" role="alert">
                                    <b>Отлично! </b>На указанный e-mail было выслано письмо с дальнейшими указаниями
                                    </div>
                                    ');
                    }
                }
                if (isset($_GET['ce']))
                {
                    if (isset($_SESSION['ce_new_email']) && isset($_SESSION['ce_key']))
                    {
                        if ($_SESSION['ce_key'] == $_GET['ce'])
                        {
                            $this->wlog("email_changed", array(
                                $data['ID'],
                                $server_id,
                                $data['Names'],
                                $_SESSION['ce_key'],
                                $_SESSION['ce_new_email'],
                                $data['Email']
                            ));
                            $data['Email'] = $_SESSION['ce_new_email'];
                            $this
                                ->db
                                ->query("UPDATE players SET Email = '" . $_SESSION['ce_new_email'] . "' WHERE Names = '" . $_SESSION['name'] . "'");
                            $this->add_to_template('
                                        <div class="alert alert-success" role="alert">
                                        <b>Успешно! </b>Электронный адрес вашего аккаунта изменен на ' . $_SESSION['ce_new_email'] . '
                                        </div>
                                        ');
                            unset($_SESSION['ce_new_email']);
                            unset($_SESSION['ce_key']);
                        }
                    }
                }
                if (isset($_POST['change_password_final']) && isset($_POST['old_password']) && isset($_POST['new_password']) && isset($_POST['new_password_sub']))
                {
                    $_POST['old_password'] = trim($_POST['old_password']);
                    $_POST['new_password'] = trim($_POST['new_password']);
                    $_POST['new_password_sub'] = trim($_POST['new_password_sub']);
                    if ($_POST['new_password_sub'] == $_POST['new_password'])
                    {
                        if ($_POST['old_password'] == $_POST['new_password'])
                        {
                            $_POST['change_password'] = 1;
                            $this->add_to_template('
                                        <div class="alert alert-danger" role="alert">
                                        <b>Ошибка: </b>указанные пароли совпадают
                                        </div>
                                        ');
                        }
                        else
                        {
                            if (strlen($_POST['new_password']) < 6 || strlen($_POST['new_password']) > 32)
                            {
                                $_POST['change_password'] = 1;
                                $this->add_to_template('
                                            <div class="alert alert-danger" role="alert">
                                            <b>Ошибка: </b>допустимая длина нового пароля от 6 до 32 символов
                                            </div>
                                            ');
                            }
                            else
                            {
                                $old_pass_hash = $this->get_password_hash($_POST['old_password'], $data['salt']);
                                $new_pass_hash = $this->get_password_hash($_POST['new_password'], $data['salt']);
                                if ($old_pass_hash == $data['Pass'])
                                {
                                    $data['Pass'] = $new_pass_hash;
                                    $this
                                        ->db
                                        ->query("UPDATE players SET Pass = '" . $new_pass_hash . "' WHERE Names = '" . $_SESSION['name'] . "'");
                                    $this->add_to_template('
                                                <div class="alert alert-success" role="alert">
                                                <b>Успешно! </b>Пароль вашего аккаунта изменен
                                                </div>
                                                ');
                                    unset($_SESSION['auth']);
                                    unset($_SESSION['name']);
                                    unset($_SESSION['srv']);
                                    $this->add_header('<meta http-equiv="refresh" content="2;URL=ucp.php">');
                                    $this->wlog("password_changed", array(
                                        $data['ID'],
                                        $server_id,
                                        $data['Names']
                                    ));
                                }
                                else
                                {
                                    $_POST['change_password'] = 1;
                                    $this->add_to_template('
                                                <div class="alert alert-danger" role="alert">
                                                <b>Ошибка: </b>текущий пароль не совпадает
                                                </div>
                                                ');
                                }
                            }
                        }
                    }
                    else
                    {
                        $_POST['change_password'] = 1;
                        $this->add_to_template('
                                    <div class="alert alert-danger" role="alert">
                                    <b>Ошибка: </b>новые пароли не совпадают
                                    </div>
                                    ');
                    }
                }
                if (isset($_GET['uninvite']))
                {
                    if ($data['Leader'] > 0)
                    {
                        $response = $this
                            ->db
                            ->query("SELECT * FROM players WHERE ID = '" . strval($_GET['uninvite']) . "'");
                        if ($response->num_rows)
                        {
                            $pdata = $response->fetch_assoc();
                            if ($pdata['ID'] == $data['ID'])
                            {
                                $_POST['page_leader'] = 1;
                                $this->add_to_template('
                                            <div class="alert alert-danger" role="alert">
                                            <b>Ошибка: </b>вы не можете исключить себя
                                            </div>
                                            ');
                            }
                            else if ($pdata['Member'] != $data['Leader'])
                            {
                                $_POST['page_leader'] = 1;
                                $this->add_to_template('
                                            <div class="alert alert-danger" role="alert">
                                            <b>Ошибка: </b>query malformed
                                            </div>
                                            ');
                            }
                            else if ($pdata['Leader'] == $data['Leader'])
                            {
                                $_POST['page_leader'] = 1;
                                $this->add_to_template('
                                            <div class="alert alert-danger" role="alert">
                                            <b>Ошибка: </b>вы не можете исключить лидера
                                            </div>
                                            ');
                            }
                        }
                        else
                        {
                            $_POST['page_leader'] = 1;
                            $this->add_to_template('
                                        <div class="alert alert-danger" role="alert">
                                        <b>Ошибка: </b>query malformed
                                        </div>
                                        ');
                        }
                    }
                }
                if (isset($_GET['spawn']) && $_GET['spawn'] == "reset")
                {
                    if ($data['Spawn'] < 5)
                    {
                        $data['Spawn'] = 2;
                        $this
                            ->db
                            ->query("UPDATE players SET Spawn = '2' WHERE ID = '" . $data['ID'] . "'");
                        $this->add_to_template('
                                    <div class="alert alert-success" role="alert">
                                    <b>Успешно! </b>Вы сбросили своё место появления
                                    </div>
                                    ');
                    }
                }
                if (isset($_POST['uninvite_final']) && isset($_POST['uninvite_id']))
                {
                    if ($data['Leader'] > 0)
                    {
                        $user_id = strval($_POST['uninvite_id']);
                        $response = $this
                            ->db
                            ->query("SELECT * FROM players WHERE ID = '" . $user_id . "'");
                        if ($response->num_rows)
                        {
                            $pdata = $response->fetch_assoc();
                            if ($pdata['ID'] != $data['ID'])
                            {
                                if ($pdata['Member'] == $data['Leader'])
                                {
                                    if ($pdata['Leader'] != $data['Leader'])
                                    {
                                        $this
                                            ->db
                                            ->query("UPDATE players SET Member = '0', Rank = '0', Spawn = '2' WHERE ID = '" . $user_id . "'");
                                        $this->add_to_template('
                                                    <div class="alert alert-success" role="alert">
                                                    <b>Успешно! </b>Вы исключили игрока ' . $pdata['Names'] . ' из своей фракции
                                                    </div>
                                                    ');
                                        $this->wlog("fraction_uninvate", array(
                                            $data['ID'],
                                            $server_id,
                                            $data['Names'],
                                            $pdata['ID'],
                                            $pdata['Names']
                                        ));
                                    }
                                }
                            }
                        }
                    }
                }
                if ($data['BanTime'] > 0)
                {
                    $text = "Срок действия блокировки истек, зайдите на сервер.";
                    if ($data['BanTime'] > time())
                    {
                        $time = $data['BanTime'] - time();
                        $hour = floor($time / 60 / 60);
                        $minute = floor(($time - ($hour * 60 * 60)) / 60);
                        $seconds = ($time - ($hour * 60 * 60)) - ($minute * 60);
                        $text = "До разблокировки осталось: " . sprintf("%02d:%02d:%02d", $hour, $minute, $seconds) . ".";
                    }
                    $this->add_to_template('
                                <div class="alert alert-warning" role="alert">
                                <b>Предупреждение: </b>ваш аккаунт заблокирован администратором ' . $data['BanName'] . ', это означает, что вы не сможете зайти на сервер в ближайшее время. ' . $text . '
                                </div>
                                ');
                }
                if ($server_id == 1)
                {
                    if ($data['transfer_complete'] == 2)
                    {
                        $this->add_to_template('
                                    <div class="alert alert-info" role="alert">
                                    <b>Напоминание:</b><br/>
                                    По некоторым причинам второй сервер GreenTech RolePlay скоро будет закрыт.<br/>
                                    Ваш никнейм на первом сервере: <b>' . $data['transfer_nickname'] . '</b><br/>
                                    </div>
                                    ');
                    }
                    else if ($data['transfer_complete'] == 1)
                    {
                        $this->add_to_template('
                                    <div class="alert alert-info" role="alert">
                                    <b>Внимание!</b><br/>
                                    По некоторым причинам второй сервер GreenTech RolePlay скоро будет закрыт.<br/>
                                    Вы не завершили перенос аккаунта на первый сервер.<br/>
                                    <br/>
                                    <a class="btn btn-primary btn-sm" href="/transfer.php">Завершить перенос</a>
                                    </div>
                                    ');
                    }
                    else
                    {
                        $this->add_to_template('
                                    <div class="alert alert-info" role="alert">
                                    <b>Внимание!</b><br/>
                                    По некоторым причинам второй сервер GreenTech RolePlay скоро будет закрыт.<br/>
                                    Мы предлагаем перенести Ваш аккаунт на первый сервер.<br/>
                                    <br/>
                                    <a class="btn btn-primary btn-sm" href="/transfer.php">Перенести аккаунт</a>
                                    </div>
                                    ');
                    }
                }
                $this->add_to_template('<div class="ucp-block-left" style="padding: 10px;">');
                $this->add_to_template('
                            <div class="ucp-menu-main">
                            <form method="post" action="ucp.php" class="ucp-menu-btn-exit"><input type="submit" name="logout" class="btn btn-danger btn-sm btn-block" value="Выйти"/></form>
                            <form method="post" action="ucp.php" class="ucp-menu-btn-sub-left"><input type="submit" name="change_email" class="btn btn-primary btn-sm btn-block" value="Сменить e-mail"/></form>
                            <form method="post" action="ucp.php" class="ucp-menu-btn-sub-right"><input type="submit" name="change_password" class="btn btn-primary btn-sm btn-block" value="Сменить пароль"/></form>
                            </div>
                            ');
                $this->add_to_template('<div class="ucp-menu-pages">');
                $this->add_to_template('
                            <form method="post" action="ucp.php"><input style="margin-bottom: 2px;" type="submit" name="page_main" class="btn btn-success btn-sm btn-block" value="Главное"/></form>
                            <form method="post" action="ucp.php"><input style="margin-bottom: 2px;" type="submit" name="page_property" class="btn btn-success btn-sm btn-block" value="Имущество"/></form>
                            <form method="post" action="ucp.php"><input style="margin-bottom: 2px;" type="submit" name="page_payments" class="btn btn-success btn-sm btn-block" value="Платежи"/></form>
                            <form method="post" action="ucp.php"><input style="margin-bottom: 2px;" type="submit" name="page_leaders" class="btn btn-success btn-sm btn-block" value="Лидеры"/></form>
                            ');
                if ($data['Leader'] > 0)
                {
                    $this->add_to_template('<form method="post" action="ucp.php"><input type="submit" name="page_leader" class="btn btn-success btn-sm btn-block" value="Фракция"/></form>');
                }
                $this->add_to_template('</div>');
                /*if($data['Admin'] > 0)
                        {
                            $this->add_to_template('<div style="margin-top: 10px;"><a href="ucp?a=admin&p=main">Админ-панель</a></div>');
                        }*/
                $this->add_to_template('</div>');
                $this->add_to_template('<div class="ucp-block-right" style="padding: 10px;">');
                if (isset($_POST['change_email']))
                {
                    $this->add_to_template('
                                <h5><b>Сменить e-mail</b></h5>
                                <form action="ucp.php" method="post" style="width: 70%">
                                <input style="margin-bottom: 5px;" type="email" name="new_email" class="form-control" placeholder="Введите новый e-mail" required/>
                                <input type="submit" name="change_email_final" class="btn btn-primary btn-sm btn-block" value="Далее"/>
                                </form>
                                ');
                    $this->wlog("pageview_change_email", array(
                        $data['ID'],
                        $server_id,
                        $data['Names']
                    ));
                }
                else if (isset($_POST['change_password']))
                {
                    $this->add_to_template('
                                <h5><b>Сменить пароль</b></h5>
                                <form action="ucp.php" method="post" style="width: 70%">
                                <input style="margin-bottom: 5px;" type="password" name="old_password" class="form-control" placeholder="Введите текущий пароль" required/>
                                <input style="margin-bottom: 5px;" type="password" name="new_password" class="form-control" placeholder="Введите новый пароль" required/>
                                <input style="margin-bottom: 5px;" type="password" name="new_password_sub" class="form-control" placeholder="Подтвердите новый пароль" required/>
                                <input type="submit" name="change_password_final" class="btn btn-primary btn-sm btn-block" value="Далее"/>
                                </form>
                                ');
                    $this->wlog("pageview_change_password", array(
                        $data['ID'],
                        $server_id,
                        $data['Names']
                    ));
                }
                else if (isset($_POST['page_property']))
                {
                    $this->add_to_template('<h5><b>Имущество</b></h5>');
                    $this->add_to_template('<table class="table" width="100%" style="font-size: 10pt;">');
                    $response = $this
                        ->db
                        ->query("SELECT hID,Street FROM houses WHERE hOwner = '" . $_SESSION['name'] . "'");
                    if ($response->num_rows)
                    {
                        for ($i = 0;$i < $response->num_rows;$i++)
                        {
                            $house_data = $response->fetch_assoc();
                            $this->add_to_template('<tr><td><b>Дом #' . $house_data['hID'] . '</b></td><td style="text-align: right;">' . iconv("CP1251", "UTF-8", $house_data['Street']) . '</td></tr>');
                        }
                    }
                    else
                    {
                        $this->add_to_template('<tr><td><b>дома отсутствуют</b></td><td style="text-align: right;"></td></tr>');
                    }
                    $response = $this
                        ->db
                        ->query("SELECT ID,Model,2Owner FROM cars WHERE Owner = '" . $_SESSION['name'] . "'");
                    if ($response->num_rows)
                    {
                        for ($i = 0;$i < $response->num_rows;$i++)
                        {
                            $car_data = $response->fetch_assoc();
                            $this->add_to_template('<tr><td><b>Машина #' . $car_data['ID'] . '</b></td><td style="text-align: right;">' . $car_data['Model'] . ', ' . $car_data['2Owner'] . '</td></tr>');
                        }
                    }
                    else
                    {
                        $this->add_to_template('<tr><td><b>машины отсутствуют</b></td><td style="text-align: right;"></td></tr>');
                    }
                    $this->add_to_template('</table>');
                    $this->wlog("pageview_property", array(
                        $data['ID'],
                        $server_id,
                        $data['Names']
                    ));
                }
                else if (isset($_POST['page_payments']))
                {
                    $this->add_to_template('<h5><b>Платежи</b></h5>');
                    $this->add_to_template('<table class="table" width="100%" style="font-size: 10pt;">');
                    $response = $this
                        ->db
                        ->query("SELECT * FROM unitpay_payments WHERE `account` = '" . $_SESSION['name'] . "' AND `status` = '1' ORDER BY `id` DESC");
                    if ($response->num_rows)
                    {
                        $this->add_to_template('<tr><td><b>общий номер (unitpayid)</b></td><td><b>зачислено на счет</b></td><td><b>дата оплаты</b></td><td style="text-align: right;"><b>сумма</b></td></tr>');
                        $common_sum = 0;
                        for ($i = 0;$i < $response->num_rows;$i++)
                        {
                            $payment_data = $response->fetch_assoc();
                            $common_sum += $payment_data['sum'];
                            $this->add_to_template('<tr><td>Платеж #' . $payment_data['id'] . ' (' . $payment_data['unitpayId'] . ')</td><td>' . $payment_data['itemsCount'] . ' донат-очков</td><td>' . $payment_data['dateComplete'] . '</td><td style="text-align: right;">' . $payment_data['sum'] . ' RUB</td></tr>');
                        }
                        $this->add_to_template('<tr><td><b>Всего</b></td><td></td><td></td><td style="text-align: right;"><b>' . $common_sum . ' RUB</b></td></tr>');
                    }
                    else
                    {
                        $this->add_to_template('<tr><td><b>Вы ещё не пополняли свой баланс</b></td><td></td><td></td><td style="text-align: right;"><a href="billing.php" class="btn-primary btn-sm">пополнить</a></td></tr>');
                    }
                    $this->add_to_template('</table>');
                    $this->wlog("pageview_payments", array(
                        $data['ID'],
                        $server_id,
                        $data['Names']
                    ));
                }
                else if (isset($_POST['page_leader']))
                {
                    if ($data['Leader'] > 0)
                    {
                        $fraction_id = $data['Leader'];
                        $this->add_to_template('<h5><b>Фракция "' . $this->fraction_name[$fraction_id] . '"</b></h5>');
                        $response = $this
                            ->db
                            ->query("SELECT * FROM players WHERE Member = '" . $fraction_id . "' ORDER BY Rank DESC");
                        $this->add_to_template('<table class="table" width="100%" style="font-size: 9pt;">');
                        $this->add_to_template('<tr><td><b>онлайн ли, имя</b></td><td><b>последний вход</b></td><td><b>ранг [подразделение]</b></td><td style="text-align: right;"><b>действия</b></td></tr>');
                        if ($response->num_rows)
                        {
                            for ($i = 0;$i < $response->num_rows;$i++)
                            {
                                $rang = "-";
                                $online = "offline";
                                $member_data = $response->fetch_assoc();
                                if (count($this->rang_name[$fraction_id]))
                                {
                                    $rang = $this->rang_name[$fraction_id][$member_data['Rank']];
                                }
                                if (count($this->sub_rang_name[$fraction_id]))
                                {
                                    $rang .= " [" . $this->sub_rang_name[$fraction_id][$member_data['DopRank']] . "]";
                                }
                                if ($member_data['Online'])
                                {
                                    $online = "<font color=\"green\">online</font>";
                                }
                                $uninvite_btn = '<a href="ucp.php?uninvite=' . $member_data['ID'] . '" class="btn-danger btn-sm" />исключить</a>';
                                if ($member_data['Leader'] == $data['Leader'])
                                {
                                    $uninvite_btn = 'невозможно ';
                                }
                                if ($member_data['ID'] == $data['ID'])
                                {
                                    $uninvite_btn = 'невозможно ';
                                }
                                $this->add_to_template('<tr><td><img src="images/ucp_' . ($member_data['Online'] ? "online" : "offline") . '.png" /> ' . $member_data['Names'] . '</td><td>' . $member_data['DataTimes'] . '</td><td>' . $rang . '</td></td><td style="text-align: right;">' . $uninvite_btn . '</td></tr>');
                            }
                        }
                        else
                        {
                            $this->add_to_template('<tr><td><b>в вашей фракции нет игроков</b></td><td></td><td></td><td style="text-align: right;"></td></tr>');
                        }
                        $this->add_to_template('</table>');
                        $this->wlog("pageview_leader", array(
                            $data['ID'],
                            $server_id,
                            $data['Names']
                        ));
                    }
                }
                else if (isset($_POST['page_leaders']))
                {
                    $this->add_to_template('<h5><b>Лидеры</b></h5>');
                    $response = $this
                        ->db
                        ->query("SELECT * FROM players WHERE Leader > 0 ORDER BY Leader");
                    $this->add_to_template('<table class="table" width="100%" style="font-size: 9pt;">');
                    $this->add_to_template('<tr><td><b>онлайн ли, имя</b></td><td style="text-align: center;"><b>последний вход</b></td><td style="text-align: center;"><b>фракция</b><td style="text-align: right;"><b>ранг [подразделение]</b></td></tr>');
                    if ($response->num_rows)
                    {
                        for ($i = 0;$i < $response->num_rows;$i++)
                        {
                            $rang = "-";
                            $online = "offline";
                            $member_data = $response->fetch_assoc();
                            $fraction_id = $member_data['Leader'];
                            if (count($this->rang_name[$fraction_id]))
                            {
                                $rang = $this->rang_name[$fraction_id][$member_data['Rank']];
                            }
                            if (count($this->sub_rang_name[$fraction_id]))
                            {
                                $rang .= " [" . $this->sub_rang_name[$fraction_id][$member_data['DopRank']] . "]";
                            }
                            if ($member_data['Online'])
                            {
                                $online = "<font color=\"green\">online</font>";
                            }
                            $this->add_to_template('<tr><td><img src="images/ucp_' . ($member_data['Online'] ? "online" : "offline") . '.png" /> ' . $member_data['Names'] . '</td><td style="text-align: center;">' . $member_data['DataTimes'] . '</td><td style="text-align: center;">' . $this->fraction_name[$fraction_id] . '</td><td style="text-align: right;">' . $rang . '</td></tr>');
                        }
                    }
                    $this->add_to_template('</table>');
                    $this->wlog("pageview_leaders", array(
                        $data['ID'],
                        $server_id,
                        $data['Names']
                    ));
                }
                else if (isset($_GET['uninvite']))
                {
                    if ($data['Leader'] > 0)
                    {
                        $response = $this
                            ->db
                            ->query("SELECT * FROM players WHERE ID = '" . strval($_GET['uninvite']) . "'");
                        if ($response->num_rows)
                        {
                            $pdata = $response->fetch_assoc();
                            if ($pdata['ID'] != $data['ID'])
                            {
                                if ($pdata['Member'] == $data['Leader'])
                                {
                                    $this->add_to_template('<h5><b>Фракция "' . $this->fraction_name[$data['Leader']] . '"</b></h5>');
                                    $this->add_to_template('
                                                <form method="post" action="ucp.php" style="width: 70%">
                                                <p>Вы действительно хотите исключить игрока ' . $pdata['Names'] . ' из своей фракции?</p>
                                                <input type="hidden" name="uninvite_id" value="' . $pdata['ID'] . '"/>
                                                <input type="hidden" name="page_leader" value="1"/>
                                                <input type="submit" name="uninvite_final" class="btn btn-danger btn-sm" value="Да"/>
                                                <input type="submit" name="uninvite_final_no_accept" class="btn btn-primary btn-sm" value="Нет"/>
                                                </form>
                                                ');
                                }
                            }
                        }
                    }
                }
                else
                {
                    $this->wlog("pageview_main", array(
                        $data['ID'],
                        $server_id,
                        $data['Names']
                    ));
                    $this->add_to_template('<h5><b>Главное</b></h5>');
                    $rang = "-";
                    $fraction_id = 0;
                    if ($data['Member'] > 0)
                    {
                        $fraction_id = $data['Member'];
                    }
                    if ($data['Leader'] > 0)
                    {
                        $fraction_id = $data['Leader'];
                    }
                    if ($fraction_id > 0)
                    {
                        if (count($this->rang_name[$fraction_id]))
                        {
                            $rang = $this->rang_name[$fraction_id][$data['Rank']];
                        }
                        if (count($this->sub_rang_name[$fraction_id]))
                        {
                            $rang .= " [" . $this->sub_rang_name[$fraction_id][$data['DopRank']] . "]";
                        }
                        $rang .= " (" . $data['Rank'] . ":" . $data['DopRank'] . ")";
                    }
                    $this->add_to_template('
                                <div id="ucp_main_left" class="ucp-main-left">
                                <table class="table" width="100%" style="border-left: 1px solid #eeeeee; font-size: 10pt;">
                                <tr><td><b>Сервер</b></td><td style="text-align: right;">GreenTech RolePlay #' . ($server_id + 1) . '</td></tr>
                                <tr><td><b>Аккаунт</b></td><td style="text-align: right;">#' . $data['ID'] . '</td></tr>
                                <tr><td><b>Имя</b></td><td style="text-align: right;">' . $username . '</td></tr>
                                <tr><td><b>E-mail</b></td><td style="text-align: right;">' . $data['Email'] . '</td></tr>
                                <tr><td><b>Уровень</b></td><td style="text-align: right;">' . $data['Level'] . '</td></tr>
                                <tr><td><b>Возраст</b></td><td style="text-align: right;">' . $data['Age'] . '</td></tr>
                                <tr><td><b>Пол</b></td><td style="text-align: right;">' . ($data['Sex'] == 2 ? "женский" : "мужской") . '</td></tr>
                                <tr><td><b>Номер телефона</b></td><td style="text-align: right;">' . $data['PhoneNumber'] . '</td></tr>
                                <tr><td><b>Баланс на телефоне</b></td><td style="text-align: right;">' . $data['PhoneBank'] . ' RUB</td></tr>
                                <tr><td><b>Деньги (наличные)</b></td><td style="text-align: right;">' . $data['Money'] . ' RUB</td></tr>
                                <tr><td><b>Деньги (в банке)</b></td><td style="text-align: right;">' . $data['Bank'] . ' RUB</td></tr>
                                <tr><td><b>Место появления</b></td><td style="text-align: right;">#' . $data['Spawn'] . ' <a href="ucp.php?spawn=reset" class="btn-primary btn-sm">сбросить</a></td></tr>
                                <tr><td><b>Донат-очки</b></td><td style="text-align: right;">' . $data['Donate'] . ' RUB <a href="billing.php" class="btn-primary btn-sm">пополнить</a></td></tr>
                                <tr><td><b>Фракция</b></td><td style="text-align: right;">' . $this->fraction_name[$fraction_id] . '</td></tr>
                                <tr><td><b>Ранг</b></td><td style="text-align: right;">' . $rang . '</td></tr>
                                </table>
                                </div>
                                ');
                    $this->add_to_template('<div id="ucp_main_right" class="ucp-main-right">');
                    $this->add_to_template('<table class="table table-bordered" width="100%" style="font-size: 10pt;">');
                    $this->add_to_template('<tr><td>');
                    if (file_exists("images/skins/pack/skin" . $data['Char'] . ".png"))
                    {
                        $this->add_to_template('<center>');
                        $this->add_to_template('<button id="bskinsel0" style="width: 46%; margin-right: 5px;" type="button" class="btn btn-primary btn-sm">Обычный</button>');
                        $this->add_to_template('<button id="bskinsel1" style="width: 46%;" type="button" class="btn btn-primary btn-sm">Мод-пак</button>');
                        $this->add_to_template('</center>');
                        $this->add_to_template('<img class="ucp-skin" id="skin_default" src="images/skins/default/skin' . $data['Char'] . '.png" style="display: none;" />');
                        $this->add_to_template('<img class="ucp-skin" id="skin_pack" src="images/skins/pack/skin' . $data['Char'] . '.png" style="display: block;" />');
                    }
                    else
                    {
                        $this->add_to_template('<img class="ucp-skin" src="images/skins/default/skin' . $data['Char'] . '.png" style="display: block;" />');
                    }
                    $this->add_to_template('</td></tr>');
                    $this->add_to_template('</table>');
                    $this->add_to_template('</div>');
                }
                $this->add_to_template('</div>');
            }
            else
            {
                unset($_SESSION['auth']);
                unset($_SESSION['name']);
                unset($_SESSION['srv']);
                $this->add_to_template('<div class="alert alert-danger" role="alert"><b>Ошибка: </b>авторизованный раннее аккаунт не найден в базе данных, возможно он был удален. Обновите страницу, чтобы перейти к авторизации</div>');
            }
        }
        $this->add_to_template('</div>');
    }
    else
    {
        $this->add_to_template('<div class="main-block-sub-login">');
        $this->add_to_template('<p class="block-title" style="margin-bottom: 10px;">Личный кабинет</p>');
        $error = "";
        $servers = "";
        for ($i = 0;$i < $this->server_count;$i++)
        {
            $servers .= '<option value="' . ($i + 1) . '">GreenTech RolePlay #' . ($i + 1) . '</option>';
        }
        if (isset($_SESSION['auth']) && $_SESSION['auth'] == "PIN")
        {
            if (isset($_GET['a']))
            {
                if ($_GET['a'] == "recovery")
                {
                    $this->add_to_template('<h4>Восстановление кода безопасности</h4>');
                    if (isset($_GET['k']) && isset($_SESSION['pin_recovery_key']))
                    {
                        $recovery_key = trim($_GET['k']);
                        if (strlen($recovery_key) == 24)
                        {
                            if ($_SESSION['pin_recovery_key'] == $recovery_key)
                            {
                                $form = true;
                                $this->db_connect($_SESSION['srv']);
                                if (isset($_POST['pincode']))
                                {
                                    $pincode = trim($_POST['pincode']);
                                    if ((strlen($pincode) == 4 || strlen($pincode) == 3) && ctype_digit($pincode))
                                    {
                                        $response = $this
                                            ->db
                                            ->query("SELECT * FROM players WHERE Names = '" . $_SESSION['name'] . "'");
                                        if ($response->num_rows)
                                        {
                                            $form = false;
                                            $this
                                                ->db
                                                ->query("UPDATE players SET DopZa = '" . strval($pincode) . "' WHERE Names = '" . $_SESSION['name'] . "'");
                                            $this->add_to_template('
                                                        <form action="ucp.php" method="post" class="billing-form">
                                                        <div class="alert alert-success" role="alert"><b>Успешно! </b>Новый код безопасности установлен</div>
                                                        <button type="submit" class="btn btn-primary">Вернуться на форму авторизации</button>
                                                        </form>
                                                        ');
                                        }
                                        else
                                        {
                                            $error = "аккаунт не найден";
                                        }
                                    }
                                    else
                                    {
                                        $error = "код безопасности должен состоять из 3 или 4 цифр";
                                    }
                                }
                                if ($form)
                                {
                                    $this->add_to_template('
                                                <form class="billing-form" method="post">
                                                ' . ($error == "" ? "" : '<div class="alert alert-danger" role="alert"><b>Ошибка: </b>' . $error . '</div>') . '
                                                <div class="form-group row">
                                                <label for="pincode-input">Новый код безопасности</label>
                                                <input class="form-control" type="input" name="pincode" placeholder="Введите новый код безопасности" id="pincode-input" required>
                                                </div>
                                                <button type="submit" class="btn btn-primary">Установить</button><br/>
                                                </form>
                                                ');
                                }
                            }
                            else
                            {
                                $this->add_to_template('<div class="alert alert-danger" role="alert"><b>Ошибка: </b>ссылка недействительна</div>');
                            }
                        }
                        else
                        {
                            $this->add_to_template('<div class="alert alert-danger" role="alert"><b>Ошибка: </b>некорректный запрос</div>');
                        }
                    }
                    else
                    {
                        if (!isset($_SESSION['pin_recovery_key']))
                        {
                            $this->db_connect($_SESSION['srv']);
                            $response = $this
                                ->db
                                ->query("SELECT * FROM players WHERE Names = '" . $_SESSION['name'] . "'");
                            if ($response->num_rows)
                            {
                                //require_once "SendMailSmtpClass.php"; // подключаем класс
                                //$mailSMTP = new SendMailSmtpClass('forum.greentech-rp@yandex.ua', 'a214aarji14214SDaa341', 'ssl://smtp.yandex.ru', 465, "UTF-8");
                                $row = $response->fetch_assoc();
                                $key = $this->generate_key(24);
                                $link = "http://greentech-rp.ru/ucp.php?a=recovery&k=" . $key;
                                $_SESSION['pin_recovery_key'] = $key;
                                $message = "Здравствуйте!\n\r\n\r";
                                $message .= "Вы запросили восстановление кода безопасности для доступа к аккаунту " . $_SESSION['name'] . " на сервере GreenTech RolePlay #" . ($_SESSION['srv'] + 1) . ", перейдите по " . $link . " для дальнейших действий.\n\r";
                                $message .= "Если вдруг вы не запрашивали восстановление, проигнорируйте это письмо.\n\r\n\r";
                                $message .= "С Уважением, администрация GreenTech RolePlay.";
                                //$result =  $mailSMTP->send(($row['Email'], "Восстановление кода безопасности", $message, "From: forum.greentech-rp@yandex.ua");
                                mail($row['Email'], "Восстановление кода безопасности", $message, "From: admin@greentech-rp.ru");
                            }
                        }
                        $form = false;
                        $this->add_to_template('<div class="alert alert-success" role="alert"><b>Успешно! </b>На электронную почту было отправлено письмо с дальнейшими инструкциями</div>');
                        $this->add_to_template('<div class="alert alert-success" role="alert"><b>Внимание! </b>Если письмо не получено в течении 5 минут, проверьте СПАМ!</div>');
                    }
                }
            }
            else
            {
                $form = true;
                $this->add_to_template('<h4>Авторизация</h4>');
                if (isset($_POST['pincode']) && isset($_POST['g-recaptcha-response']))
                {
                    $pincode = $_POST['pincode'];
                    $gresponse = $_POST['g-recaptcha-response'];
                    if (strlen($pincode) == 4 || strlen($pincode) == 3)
                    {
                        if (strlen($gresponse) > 0)
                        {
                            $curl = curl_init();
                            curl_setopt($curl, CURLOPT_URL, 'https://www.google.com/recaptcha/api/siteverify');
                            curl_setopt($curl, CURLOPT_POST, true);
                            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query(array(
                                "secret" => "6LeBTg0UAAAAAIBqLkP3M3bHZoTcAjq3va04fcRp",
                                "response" => $gresponse,
                                "remoteip" => $_SERVER['REMOTE_ADDR']
                            )));
                            $response = (array)json_decode(curl_exec($curl) , true);
                            curl_close($curl);
                            if (isset($response['success']))
                            {
                                if ($response['success'] == true)
                                {
                                    $this->db_connect($_SESSION['srv']);
                                    $response = $this
                                        ->db
                                        ->query("SELECT DopZa FROM players WHERE Names = '" . $_SESSION['name'] . "'");
                                    if ($response->num_rows)
                                    {
                                        $row = $response->fetch_assoc();
                                        if (strval($row['DopZa']) === strval($pincode))
                                        {
                                            $form = false;
                                            $_SESSION['auth'] = "YES";
                                            $this->add_header('<meta http-equiv="refresh" content="3;URL=ucp.php">');
                                            $this->add_to_template('<div id="auth_success" class="alert alert-success" role="alert"><b>Успешно!</b> Сейчас вы будете перенаправлены на страницу своего аккаунта, если этого не произойдет автоматически - обновите страницу</div>');
                                            $this->wlog("success_auth", array(
                                                $row['ID'],
                                                $_SESSION['srv'],
                                                $_SESSION['name']
                                            ));
                                        }
                                        else
                                        {
                                            $error = "неверный код безопасности";
                                        }
                                    }
                                    else
                                    {
                                        $error = "игрок с таким никнеймом или паролем не найден";
                                    }
                                }
                                else
                                {
                                    $error = "активируйте капчу";
                                }
                            }
                            else
                            {
                                $error = "произошла ошибка при подключении к серверам Google reCAPTCHA";
                            }
                        }
                        else
                        {
                            $error = "активируйте капчу";
                        }
                    }
                    else
                    {
                        $error = "код безопасности состоит из 3 или 4 цифр";
                    }
                }
                if ($form)
                {
                    $this->add_to_template('
                                <form class="billing-form" method="post">
                                ' . ($error == "" ? "" : '<div class="alert alert-danger" role="alert"><b>Ошибка: </b>' . $error . '</div>') . '
                                <div class="form-group row">
                                <label for="pincode-input">Код безопасности</label>
                                <input class="form-control" type="password" name="pincode" placeholder="Введите код безопасности" id="pincode-input">
                                </div>
                                <div class="form-group row" style="margin-top: 20px;">
                                <div class="g-recaptcha" data-sitekey="6LeBTg0UAAAAALAG7ZhJBr6PpTbk0WEjKAknCn0P"></div>
                                </div>
                                <button type="submit" class="btn btn-primary">Войти</button><br/>
                                </form>
                                <a href="ucp.php?a=recovery" class="btn btn btn-link">Восстановить код безопасности</a>
                                ');
                }
            }
        }
        else
        {
            if (isset($_GET['a']))
            {
                if ($_GET['a'] == "recovery")
                {
                    $this->add_to_template('<h4>Восстановление доступа</h4>');
                    if (isset($_GET['s']) && isset($_GET['k']))
                    {
                        $server_id = strval($_GET['s']) - 1;
                        $recovery_key = trim($_GET['k']);
                        if ($server_id >= 0 && $server_id < $this->server_count)
                        {
                            if (strlen($recovery_key) == 24)
                            {
                                $this->db_connect($server_id);
                                $recovery_key = $this
                                    ->db
                                    ->real_escape_string($recovery_key);
                                $response = $this
                                    ->db
                                    ->query("SELECT * FROM ucp_recovery WHERE `key` = '" . $recovery_key . "'");
                                if ($response->num_rows)
                                {
                                    $row = $response->fetch_assoc();
                                    $recovery_id = $row['id'];
                                    if ($row['ip'] == $_SERVER['REMOTE_ADDR'])
                                    {
                                        if (time() - $row['ts'] > 21600)
                                        {
                                            $this->add_to_template('<div class="alert alert-danger" role="alert"><b>Ошибка: </b>срок действия восстановления истек</div>');
                                        }
                                        else
                                        {
                                            $form = true;
                                            if (isset($_POST['pass']) && isset($_POST['pass-confirm']))
                                            {
                                                $password = trim($_POST['pass']);
                                                $password_confirm = trim($_POST['pass-confirm']);
                                                if (strlen($password) > 0 && strlen($password_confirm) > 0)
                                                {
                                                    if ($password == $password_confirm)
                                                    {
                                                        if (strlen($password) >= 6 && strlen($password) <= 32)
                                                        {
                                                            $response = $this
                                                                ->db
                                                                ->query("SELECT * FROM players WHERE `ID` = '" . $row['uid'] . "'");
                                                            if ($response->num_rows)
                                                            {
                                                                $row = $response->fetch_assoc();
                                                                $hash = $this->get_password_hash($password, $row['salt']);
                                                                if ($hash != $row['Pass'])
                                                                {
                                                                    $form = false;
                                                                    $this
                                                                        ->db
                                                                        ->query("DELETE FROM ucp_recovery WHERE `id` = '" . $recovery_id . "'");
                                                                    $this
                                                                        ->db
                                                                        ->query("UPDATE players SET `Pass` = '" . $hash . "' WHERE `ID` = '" . $row['ID'] . "'");
                                                                    $this->add_to_template('
                                                                                <form action="ucp.php" method="post" class="billing-form">
                                                                                <div class="alert alert-success" role="alert"><b>Успешно! </b>Новый пароль установлен</div>
                                                                                <button type="submit" class="btn btn-primary">Вернуться на форму авторизации</button>
                                                                                </form>
                                                                                ');
                                                                    $this->wlog("recovery_final", array(
                                                                        $row['ID'],
                                                                        $server_id,
                                                                        $row['Names'],
                                                                        $recovery_id
                                                                    ));
                                                                }
                                                                else
                                                                {
                                                                    $error = "данный пароль установлен сейчас на аккаунте";
                                                                }
                                                            }
                                                            else
                                                            {
                                                                $error = "аккаунт не найден";
                                                            }
                                                        }
                                                        else
                                                        {
                                                            $error = "допустимая длина пароля от 6 до 32 символов";
                                                        }
                                                    }
                                                    else
                                                    {
                                                        $error = "пароли не совпадают";
                                                    }
                                                }
                                                else
                                                {
                                                    $error = "введите пароль";
                                                }
                                            }
                                            if ($form)
                                            {
                                                $this->add_to_template('
                                                            <form class="billing-form" method="post">
                                                            ' . ($error == "" ? "" : '<div class="alert alert-danger" role="alert"><b>Ошибка: </b>' . $error . '</div>') . '
                                                            <div class="form-group row">
                                                            <label for="pass-input">Новый пароль</label>
                                                            <input class="form-control" type="password" name="pass" placeholder="Введите новый пароль" id="pass-input" required>
                                                            </div>
                                                            <div class="form-group row">
                                                            <label for="pass-confirm-input">Подтверждение нового пароля</label>
                                                            <input class="form-control" type="password" name="pass-confirm" placeholder="Снова введите новый пароль" id="pass-confirm-input" required>
                                                            </div>
                                                            <button type="submit" class="btn btn-primary">Продолжить</button><br/>
                                                            </form>
                                                            ');
                                            }
                                        }
                                    }
                                    else
                                    {
                                        $this->add_to_template('<div class="alert alert-danger" role="alert"><b>Ошибка: </b>вы пытаетесь восстановить пароль с IP-адреса, который не совпадает с адресом при запросе восстановления</div>');
                                    }
                                }
                                else
                                {
                                    $this->add_to_template('<div class="alert alert-danger" role="alert"><b>Ошибка: </b>ссылка недействительна</div>');
                                }
                            }
                            else
                            {
                                $this->add_to_template('<div class="alert alert-danger" role="alert"><b>Ошибка: </b>некорректный запрос</div>');
                            }
                        }
                        else
                        {
                            $this->add_to_template('<div class="alert alert-danger" role="alert"><b>Ошибка: </b>некорректный запрос</div>');
                        }
                    }
                    else if (isset($_GET['s']) && isset($_GET['u']))
                    {
                        $server_id = strval($_GET['s']) - 1;
                        $user_id = strval($_GET['u']);
                        if ($server_id >= 0 && $server_id < $this->server_count)
                        {
                            $this->db_connect($server_id);
                            $user_id = $this
                                ->db
                                ->real_escape_string($user_id);
                            $response = $this
                                ->db
                                ->query("SELECT * FROM players WHERE `ID` = '" . $user_id . "'");
                            if ($response->num_rows)
                            {
                                $row = $response->fetch_assoc();
                                $key = $this->generate_key(24);
                                $link = "http://greentech-rp.ru/ucp.php?a=recovery&s=" . ($server_id + 1) . "&k=" . $key;
                                $text = "";
                                $message = "Здравствуйте!\n\r\n\r";
                                $message .= "Вы запросили восстановление доступа к аккаунту " . $row['Names'] . " на сервере GreenTech RolePlay #" . ($server_id + 1) . ", перейдите по " . $link . " для дальнейших действий.\n\r";
                                $message .= "Если вдруг вы не запрашивали восстановление, проигнорируйте это письмо.\n\r\n\r";
                                $message .= "С Уважением, администрация GreenTech RolePlay.";
                                mail($row['Email'], "Восстановление доступа к аккаунту", $message, "From: admin@greentech-rp.ru");
                                $this->wlog("recovery_query", array(
                                    $row['ID'],
                                    $server_id,
                                    $row['Names'],
                                    $key,
                                    $row['Email']
                                ));
                                $result = $this
                                    ->db
                                    ->query("INSERT INTO ucp_recovery(`uid`,`ts`,`ip`,`key`) VALUES('" . $row['ID'] . "','" . time() . "','" . $_SERVER['REMOTE_ADDR'] . "','" . $key . "')");
                                if ($result)
                                {
                                    $form = false;
                                    $this->add_to_template('<div class="alert alert-success" role="alert"><b>Успешно! </b>На электронную почту было отправлено письмо с дальнейшими инструкциями</div>');
                                    $this->add_to_template('<div class="alert alert-success" role="alert"><b>Внимание! </b>Если письмо не получено в течении 5 минут, проверьте СПАМ!</div>');
                                }
                                else
                                {
                                    $this->add_to_template('<div class="alert alert-danger" role="alert"><b>Ошибка: </b>произошла ошибка при выполнении запроса, попробуйте позже</div>');
                                }
                            }
                            else
                            {
                                $this->add_to_template('<div class="alert alert-danger" role="alert"><b>Ошибка: </b>аккаунт не найден</div>');
                            }
                        }
                        else
                        {
                            $this->add_to_template('<div class="alert alert-danger" role="alert"><b>Ошибка: </b>некорректный запрос</div>');
                        }
                    }
                    else
                    {
                        $form = true;
                        if (isset($_POST['srv']) && isset($_POST['email']) && isset($_POST['g-recaptcha-response']))
                        {
                            $server_id = strval($_POST['srv']) - 1;
                            $email = $_POST['email'];
                            $gresponse = $_POST['g-recaptcha-response'];
                            if ($server_id >= 0 && $server_id < $this->server_count)
                            {
                                if (strlen($email) > 0)
                                {
                                    if (strlen($gresponse) > 0)
                                    {
                                        $curl = curl_init();
                                        curl_setopt($curl, CURLOPT_URL, 'https://www.google.com/recaptcha/api/siteverify');
                                        curl_setopt($curl, CURLOPT_POST, true);
                                        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                                        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                                        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query(array(
                                            "secret" => "6LeBTg0UAAAAAIBqLkP3M3bHZoTcAjq3va04fcRp",
                                            "response" => $gresponse,
                                            "remoteip" => $_SERVER['REMOTE_ADDR']
                                        )));
                                        $response = (array)json_decode(curl_exec($curl) , true);
                                        curl_close($curl);
                                        if (isset($response['success']))
                                        {
                                            if ($response['success'] == true)
                                            {
                                                $this->db_connect($server_id);
                                                $email = $this
                                                    ->db
                                                    ->real_escape_string($email);
                                                $response = $this
                                                    ->db
                                                    ->query("SELECT * FROM players WHERE Email = '" . $email . "'");
                                                if ($response->num_rows)
                                                {
                                                    $form = false;
                                                    $this->add_to_template('<div class="alert alert-info" role="alert"><b>Отлично! </b>Выберете аккаунт, который вы хотите восстановить</div>');
                                                    $this->add_to_template('<table class="table" style="width: 70%;">');
                                                    for ($i = 0;$i < $response->num_rows;$i++)
                                                    {
                                                        $row = $response->fetch_assoc();
                                                        $this->add_to_template('<tr><td>' . $row['Names'] . '</td><td style="text-align: right;"><a href="ucp.php?a=recovery&s=' . ($server_id + 1) . '&u=' . $row['ID'] . '" class="btn btn-primary">восстановить</a></td></tr>');
                                                    }
                                                    $this->add_to_template('</table>');
                                                }
                                                else
                                                {
                                                    $error = "игрок с таким email не найден";
                                                }
                                            }
                                            else
                                            {
                                                $error = "активируйте капчу";
                                            }
                                        }
                                        else
                                        {
                                            $error = "произошла ошибка при подключении к серверам Google reCAPTCHA";
                                        }
                                    }
                                    else
                                    {
                                        $error = "активируйте капчу";
                                    }
                                }
                                else
                                {
                                    $error = "введите email";
                                }
                            }
                            else
                            {
                                $error = "некорректный сервер";
                            }
                        }
                        if ($form)
                        {
                            $this->add_to_template('
                                        <form class="billing-form" method="post">
                                        ' . ($error == "" ? "" : '<div class="alert alert-danger" role="alert"><b>Ошибка: </b>' . $error . '</div>') . '
                                        <div class="form-group row">
                                        <label for="server-select">Сервер</label>
                                        <select class="form-control" name="srv" id="server-select">
                                        ' . $servers . '
                                        </select>
                                        </div>
                                        <div class="form-group row">
                                        <label for="email-input">E-mail</label>
                                        <input class="form-control" type="email" name="email" placeholder="Введите e-mail" id="email-input" required>
                                        </div>
                                        <div class="form-group row" style="margin-top: 20px;">
                                        <div class="g-recaptcha" data-sitekey="6LeBTg0UAAAAALAG7ZhJBr6PpTbk0WEjKAknCn0P"></div>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Продолжить</button><br/>
                                        </form>
                                        ');
                        }
                    }
                }
            }
            else
            {
                $form = true;
                $this->add_to_template('<h4>Авторизация</h4>');
                if (isset($_POST['srv']) && isset($_POST['username']) && isset($_POST['password']) && isset($_POST['g-recaptcha-response']))
                {
                    $server_id = strval($_POST['srv']) - 1;
                    $username = $_POST['username'];
                    $password = $_POST['password'];
                    $gresponse = $_POST['g-recaptcha-response'];
                    if ($server_id >= 0 && $server_id < $this->server_count)
                    {
                        if (strlen($username) > 0)
                        {
                            if (strlen($password) > 0)
                            {
                                if (strlen($gresponse) > 0)
                                {
                                    $curl = curl_init();
                                    curl_setopt($curl, CURLOPT_URL, 'https://www.google.com/recaptcha/api/siteverify');
                                    curl_setopt($curl, CURLOPT_POST, true);
                                    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                                    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query(array(
                                        "secret" => "6LeBTg0UAAAAAIBqLkP3M3bHZoTcAjq3va04fcRp",
                                        "response" => $gresponse,
                                        "remoteip" => $_SERVER['REMOTE_ADDR']
                                    )));
                                    $response = (array)json_decode(curl_exec($curl) , true);
                                    curl_close($curl);
                                    if (isset($response['success']))
                                    {
                                        if ($response['success'] == true)
                                        {
                                            if (strlen($username) >= 3 && strlen($username) <= 20)
                                            {
                                                if (strlen($username) >= 6 && strlen($username) <= 32)
                                                {
                                                    if (preg_match("#^[aA-zZ0-9\-_\]\[\$\=\(\)\@\.]+$#", $username))
                                                    {
                                                        $this->db_connect($server_id);
                                                        $username = $this
                                                            ->db
                                                            ->real_escape_string($username);
                                                        $response = $this
                                                            ->db
                                                            ->query("SELECT salt,Pass,DopZa FROM players WHERE Names = '" . $username . "'");
                                                        if ($response->num_rows)
                                                        {
                                                            $row = $response->fetch_assoc();
                                                            $real_password_hash = $row['Pass'];
                                                            $my_password_hash = $this->get_password_hash($password, $row['salt']);
                                                            if ($real_password_hash === $my_password_hash)
                                                            {
                                                                $form = false;
                                                                $_SESSION['name'] = $username;
                                                                $_SESSION['srv'] = $server_id;
                                                                if ($row['DopZa'])
                                                                {
                                                                    $_SESSION['auth'] = "PIN";
                                                                    $this->add_header('<meta http-equiv="refresh" content="0;URL=ucp.php">');
                                                                }
                                                                else
                                                                {
                                                                    $_SESSION['auth'] = "YES";
                                                                    $this->add_header('<meta http-equiv="refresh" content="3;URL=ucp.php">');
                                                                    $this->add_to_template('<div id="auth_success" class="alert alert-success" role="alert"><b>Успешно!</b> Сейчас вы будете перенаправлены на страницу своего аккаунта, если этого не произойдет автоматически - обновите страницу</div>');
                                                                    $this->wlog("success_auth", array(
                                                                        $row['ID'],
                                                                        $server_id,
                                                                        $username
                                                                    ));
                                                                }
                                                            }
                                                            else
                                                            {
                                                                $error = "игрок с таким никнеймом или паролем не найден";
                                                            }
                                                        }
                                                        else
                                                        {
                                                            $error = "игрок с таким никнеймом или паролем не найден";
                                                        }
                                                    }
                                                    else
                                                    {
                                                        $error = "в никнейме недопустимые символы";
                                                    }
                                                }
                                                else
                                                {
                                                    $error = "допустимая длина пароля: 6-32 символов";
                                                }
                                            }
                                            else
                                            {
                                                $error = "допустимая длина никнейма: 3-20 символов";
                                            }
                                        }
                                        else
                                        {
                                            $error = "активируйте капчу";
                                        }
                                    }
                                    else
                                    {
                                        $error = "произошла ошибка при подключении к серверам Google reCAPTCHA";
                                    }
                                }
                                else
                                {
                                    $error = "активируйте капчу";
                                }
                            }
                            else
                            {
                                $error = "введите пароль";
                            }
                        }
                        else
                        {
                            $error = "введите никнейм";
                        }
                    }
                    else
                    {
                        $error = "некорректный сервер";
                    }
                }
                if ($form)
                {
                    $this->add_to_template('
                                <form class="billing-form" method="post">
                                ' . ($error == "" ? "" : '<div class="alert alert-danger" role="alert"><b>Ошибка: </b>' . $error . '</div>') . '
                                <div class="form-group row">
                                <label for="server-select">Сервер</label>
                                <select class="form-control" name="srv" id="server-select">
                                ' . $servers . '
                                </select>
                                </div>
                                <div class="form-group row">
                                <label for="username-input">Никнейм</label>
                                <input class="form-control" type="text" name="username" placeholder="Введите никнейм" id="username-input">
                                </div>
                                <div class="form-group row">
                                <label for="password-input">Пароль</label>
                                <input class="form-control" type="password" name="password" placeholder="Введите пароль" id="password-input">
                                </div>
                                <div class="form-group row" style="margin-top: 20px;">
                                <div class="g-recaptcha" data-sitekey="6LeBTg0UAAAAALAG7ZhJBr6PpTbk0WEjKAknCn0P"></div>
                                </div>
                                <button type="submit" class="btn btn-primary">Войти</button><br/>
                                </form>
                                <a href="ucp.php?a=recovery" class="btn btn btn-link">Восстановить пароль</a>
                                ');
                }
            }
        }
    }
    $this->add_to_template('
                </div>
                </div>
                </center>
                </section>
                ');
    
