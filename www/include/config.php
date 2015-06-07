<?php
header('Content-Type: text/html; charset=utf-8');

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

include_once ('db_connect.php');

if (!defined('EMPTY_VALUE')) define('EMPTY_VALUE', '-');

if (!defined('TEMPLATE_PATH')) define('TEMPLATE_PATH', '../template/template.html');

if (!defined('PUBLIC_TRANSPORT')) define('PUBLIC_TRANSPORT',"'bus','trolleybus','share_taxi','tram','train'");

$transport_type_names = array(
	"bus"  => "Автобус",
	"trolleybus" => "Троллейбус",
	"share_taxi" => "Маршрутное такси",
	"tram" => "Трамвай",
	"train" => "Поезд",
);

$page_title='Общественный транспорт OpenStreetMap';
$page='null';
?>
