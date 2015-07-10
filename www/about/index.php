<?php
include_once ('../include/config.php');

$sql_update_date= pg_query("
	SELECT DISTINCT
		MAX(tstamp) as tstamp
	FROM
		statistics_summary
");

$update_date = pg_fetch_assoc($sql_update_date)['tstamp'];

$output = "
	<div class='content_center'>
		<h2 align=center>О сайте</h2>
		<p>
			Данный веб-сайт представляет собой средство просмотра и валидации маршрутов общественного транспорта международного проекта <a href='http://openstreetmap.org'>OpenStreetMap</a>. Для просмотра доступны маршруты автобусов, троллейбусов, маршрутных такси, трамваев и поездов.<br>
			<br>
			Российский портал OSM: <a href='http://openstreetmap.ru'>openstreetmap.ru</a>
		</p>
		<h3>Авторские права</h3>
		<p>
			Проект общественного транспорта использует данные <a href='http://openstreetmap.org'>OpenStreetMap</a> — международного проекта по созданию свободно-распространяемой подробной карты всего мира.<br>
			Данные распространяются по лицензии <a href='http://opendatacommons.org/licenses/odbl/'>Open Data Commons Open Database License</a> (ODbL).
		</p>
		<h3>Обратная связь</h3>
		<p>
			Проект на <a href='https://github.com/dmitry-s93/OSMPublicTransport'>GitHub</a>.<br>
			Ветка на <a href='http://forum.openstreetmap.org/viewtopic.php?id=31809'>форуме</a>.
		</p>
		<h3>Техническая информация</h3>
		<p>
			Дата актуализации данных: ".$update_date."
		</p>
	</div>
	";

$page_title='О сайте маршрутов общественного транспорта OpenStreetMap';
$page = 'about';
include(TEMPLATE_PATH);
?>
