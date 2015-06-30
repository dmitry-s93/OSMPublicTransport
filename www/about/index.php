<?php
include_once ('../include/config.php');

$sql_update_date= pg_query("
	SELECT DISTINCT
		MAX(tstamp) as tstamp
	FROM
		statistics_summary
") or die(mysql_error());

$update_date = pg_fetch_assoc($sql_update_date)['tstamp'];

$output = "
	<h2 align=center>О сайте</h2>
	<p>Сайт представляет собой средство просмотра и валидации маршрутов общественного транспорта международного проекта <a href='http://openstreetmap.org'>OpenStreetMap</a>.</p>
	<p>Для просмотра доступны маршруты автобусов, троллейбусов, маршрутных такси, трамваев и поездов.</p>
	<p>Используются данные OpenStreetMap — международного проекта по созданию свободно-распространяемой подробной карты всего мира.<br><br>
	Веб-сайт проекта: <a href='http://openstreetmap.org'>openstreetmap.org</a><br>
	Российский портал: <a href='http://openstreetmap.ru'>openstreetmap.ru</a><br></p>
	<h3>Обратная связь</h3>
	<p>Проект на <a href='https://github.com/dmitry-s93/OSMPublicTransport'>GitHub</a>.</p>
	<h3>Техническая информация</h3>
	<p>Дата актуализации данных: ".$update_date."</p>
	";

$page_title='О сайте маршрутов общественного транспорта OpenStreetMap';
$page = 'about';
include(TEMPLATE_PATH);
?>
