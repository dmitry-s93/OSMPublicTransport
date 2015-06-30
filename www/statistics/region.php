<?php
include_once ('../include/config.php');

$r_id = $_GET['id'];

$sql_region = pg_query("
SELECT
	iso3166,
	name
FROM regions
WHERE 
	id='$r_id'
	") or die(mysql_error());

$sql_statistics_by_places= pg_query("
SELECT
	routes.type as place_type,
	routes.name as place_name,
	count(case when routes.route_type = 'bus' then routes.route_type else null end) as bus,
	count(case when routes.route_type = 'trolleybus' then routes.route_type else null end) as trolleybus,
	count(case when routes.route_type = 'share_taxi' then routes.route_type else null end) as share_taxi,
	count(case when routes.route_type = 'tram' then routes.route_type else null end) as tram,
	count(case when routes.route_type = 'train' then routes.route_type else null end) as train
FROM
	(SELECT DISTINCT
		places.type,
		places.name,
		transport_routes.tags->'route' as route_type,
		transport_routes.tags->'ref' as ref

	FROM
		places,
		transport_location,
		transport_routes
	WHERE
		places.region_id=".$r_id." and
		places.id=transport_location.place_id and
		transport_location.route_id=transport_routes.id) as routes
GROUP BY
	routes.type,
	routes.name
	--transport_routes.route_type
ORDER BY
	routes.type,
	routes.name
") or die(mysql_error());
	
$sql_statistics_by_transport= pg_query("
SELECT	
	route_bus,
	route_trolleybus,
	route_share_taxi,
	route_tram,
	route_train,
	route_master_bus,
	route_master_trolleybus,
	route_master_share_taxi,
	route_master_tram,
	route_master_train,
	
	stop_position,
	platform,
	station
FROM statistics_by_region
WHERE region_id=".$r_id."
") or die(mysql_error());

$sql_statistics_by_date= pg_query("
	SELECT
		tstamp,
		(route_bus+
		route_trolleybus+
		route_share_taxi+
		route_tram+
		route_train) as route,
		
		(route_master_bus+
		route_master_trolleybus+
		route_master_share_taxi+
		route_master_tram+
		route_master_train) as route_master,
		
		platform,
		stop_position,
		station
	FROM
		statistics_by_region
	WHERE region_id=".$r_id."
	ORDER BY
		tstamp desc
	LIMIT
		20;
") or die(mysql_error());

$region_name = pg_fetch_assoc($sql_region)['name'];	
$output = "<h2 align=center>".$region_name." - статистика</h2>";		

$output.="
<h3>Статистика по населенным пунктам:</h3>
<table border width=100%>
	<thead>
		<tr>
			<th>Населенные  пункты</th>
			<th width=15%>Автобусы</th>
			<th width=15%>Троллейбусы</th>
			<th width=15%>Маршрутки</th>
			<th width=15%>Трамваи</th>
			<th width=15%>Поезда</th>
		</tr>
	</thead>
	<tbody>";
	
while ($row_by_places = pg_fetch_assoc($sql_statistics_by_places)){
	$output.="
	<tr class='highlight'>
		<td>".$row_by_places['place_name']."</td>
		<td>".$row_by_places['bus']."</td>
		<td>".$row_by_places['trolleybus']."</td>
		<td>".$row_by_places['share_taxi']."</td>
		<td>".$row_by_places['tram']."</td>
		<td>".$row_by_places['train']."</td>
	</tr>";
}

$output.="</tbody></table>";

$row_by_transport = pg_fetch_assoc($sql_statistics_by_transport);

$output.="
<h3>Статистика по типу транспорта:</h3>
<table border width=100%>
	<thead>
		<tr>
			<th width=35%>Транспорт</th>
			<th width=30%>Маршруты (направления)</th>
			<th width=30%>Мастер маршруты</th>
		</tr>
	</thead>
	<tbody>
	<tr class='highlight'>
		<td>Автобус</td>
		<td>".$row_by_transport['route_bus']."</td>
		<td>".$row_by_transport['route_master_bus']."</td>
	</tr>
	<tr class='highlight'>
		<td>Троллейбус</td>
		<td>".$row_by_transport['route_trolleybus']."</td>
		<td>".$row_by_transport['route_master_trolleybus']."</td>
	</tr>
	<tr class='highlight'>
		<td>Маршрутное такси</td>
		<td>".$row_by_transport['route_share_taxi']."</td>
		<td>".$row_by_transport['route_master_share_taxi']."</td>
	</tr>
	<tr class='highlight'>
		<td>Трамвай</td>
		<td>".$row_by_transport['route_tram']."</td>
		<td>".$row_by_transport['route_master_tram']."</td>
	</tr>
	<tr class='highlight'>
		<td>Поезд</td>
		<td>".$row_by_transport['route_train']."</td>
		<td>".$row_by_transport['route_master_train']."</td>
	</tr>	
	</tbody>
</table>";

$output.="
<h3>Статистика по датам:</h3>
<table border width=100%>
	<thead>
		<tr>
			<th width=20%>Дата и время</th>
			<th width=16%>Маршруты</th>
			<th width=16%>Мастер-маршруты</th>
			<th width=16%>Остановки / платформы</th>
			<th width=16%>Места остановок</th>
			<th width=16%>Станции</th>
		</tr>
	</thead>
	<tbody>";
	
while ($row_by_date = pg_fetch_assoc($sql_statistics_by_date)){	
	$output.="
	<tr class='highlight'>
		<td>".$row_by_date['tstamp']."</td>
		<td>".$row_by_date['route']."</td>
		<td>".$row_by_date['route_master']."</td>
		<td>".$row_by_date['platform']."</td>
		<td>".$row_by_date['stop_position']."</td>
		<td>".$row_by_date['station']."</td>
	</tr>";
}

$output.="</tbody></table><br>";

pg_close($dbconn);

$page_title=$region_name." - статистика маршрутов общественного транспорта OpenStreetMap";
$page = 'statistics';
include(TEMPLATE_PATH);
?>
