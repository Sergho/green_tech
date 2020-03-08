<?php 

// Домен страницы

$domain = "localhost";

// Забираем нужные GET параметры
$paymentId = $_GET['paymentId'];
$username = $_GET['account'];
$type = "ERROR";

// Делаем перенаправление на главную страницу доната с нужными GET параметрами
header("Location: http://" . $domain . "/donate.php?type=" . $type . "&account=" . $username . "&paymentId=" . $paymentId);

echo $paymentId;

 ?>