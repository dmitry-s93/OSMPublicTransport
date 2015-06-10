<?php
include_once ('../include/config.php');

$dbconn = pg_connect("host=".HOST." dbname=".NAME_BD." user=".USER." password='".PASSWORD."'") or die(pg_last_error());

$sql_statistics_by_region= pg_query("
	SELECT
		statistics_by_region.region_id,
		regions.name as region_name,
		
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
		regions,
		statistics_by_region
	WHERE
		statistics_by_region.region_id=regions.id and
		statistics_by_region.tstamp=(SELECT MAX(tstamp) from statistics_by_region)
	ORDER BY name
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
		statistics_summary
	ORDER BY
		tstamp desc
	LIMIT
		20;
") or die(mysql_error());

$output = "<h2 align=center>Статистические данные</h2>";
$output.="<h3>Статистика по регионам:</h3>";
$output=$output."
<table border width=100%>
	<thead>
		<tr>
			<th width=25%>Регионы</th>
			<th width=15%>Маршруты</th>
			<th width=15%>Мастер-маршруты</th>
			<th width=15%>Остановки / платформы</th>
			<th width=15%>Места остановок</th>
			<th width=15%>Станции</th>
		</tr>
	</thead>
	<tbody>";

while ($row_by_region = pg_fetch_assoc($sql_statistics_by_region)){	
	$output.=
	"<tr class='highlight'>
		<td><a href='region.php?id=".$row_by_region['region_id']."'>".$row_by_region['region_name']."</a></td>
		<td>".$row_by_region['route']."</td>
		<td>".$row_by_region['route_master']."</td>
		<td>".$row_by_region['platform']."</td>
		<td>".$row_by_region['stop_position']."</td>
		<td>".$row_by_region['station']."</td>
	</tr>";
}	

$output.="</tbody></table>";

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

$page_title='Статистика маршрутов общественного транспорта';
$page = 'statistics';
include(TEMPLATE_PATH);
?>
