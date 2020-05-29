<?php

$good_ips = ["::1", "192.168.0.3", "192.168.0.20"];

$good_ip = false;

foreach($good_ips as $ip){
	if($_SERVER['REMOTE_ADDR'] == $ip) $good_ip = true;
}

if(!$good_ip) echo "Тебе сюда нельзя, ты не root. Иди отсюда!";
else echo phpinfo();