<?php
include_once ('include/config.php');

$output = file_get_contents("template/main.html");

$page_title="Карта маршрутов общественного транспорта OpenStreetMap";
$page = 'main';
include("template/template.html");