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
	<p>Сайт задуман как средство просмотра и валидации маршрутов общественного транспорта международного проекта OpenStreetMap.</p>
	<p>В настоящий момент сайт находится на начальном этапе разработки. У нас еще много интересных идей и предстоит много работы.</p>
	<h3>Немного об OpenStreetMap</h3>
	<p>OpenStreetMap — это международный проект по созданию свободно-распространяемой подробной карты всего мира.<br><br>
	Веб-сайт проекта: <a href='http://openstreetmap.org'>openstreetmap.org</a><br>
	Российский портал: <a href='http://openstreetmap.ru'>openstreetmap.ru</a><br></p>
	<h3>Техническая информация</h3>
	<p>Дата актуализации данных: ".$update_date."</p>
	";

$page_title='О сайте маршрутов общественного транспорта';
$page = 'about';
include(TEMPLATE_PATH);
?>
