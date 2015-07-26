<?php
include_once ('../include/config.php');

$sql_statistics_by_region= pg_query("
	SELECT
		statistics.region_id,
		statistics.region_name,
		statistics.route,
		statistics.route_master,
		statistics.platform,
		statistics.stop_position,
		statistics.station,
		statistics_prev.route as route_prev,
		statistics_prev.route_master as route_master_prev,
		statistics_prev.platform as platform_prev,
		statistics_prev.stop_position as stop_position_prev,
		statistics_prev.station as station_prev
	FROM
		(SELECT
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
			statistics_by_region.tstamp=(SELECT MAX(tstamp) from statistics_by_region)) as statistics
		LEFT OUTER JOIN
		(SELECT
			statistics_by_region.region_id,

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
			statistics_by_region.tstamp=(
				SELECT MAX(tstamp)
				FROM statistics_by_region
				WHERE tstamp<(SELECT MAX(tstamp)FROM statistics_by_region))) as statistics_prev
		ON (statistics.region_id = statistics_prev.region_id)
	ORDER BY statistics.region_name
");

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
");

$output = "<div class='content_body_table'>
<h2 align=center>Статистические данные</h2>
<h3>Статистика по регионам:</h3>";
$output .="
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
		<td><a href='region?id=".$row_by_region['region_id']."'>".$row_by_region['region_name']."</a></td>";

	if ($row_by_region['route'] == $row_by_region['route_prev']) {
		$output.="<td>".$row_by_region['route']."</td>";
	} elseif ($row_by_region['route'] > $row_by_region['route_prev']) {
		$output.="<td>".$row_by_region['route']." <span class='text_green'>↗".($row_by_region['route']-$row_by_region['route_prev'])."</span></td>";
	} elseif ($row_by_region['route'] < $row_by_region['route_prev']) {
		$output.="<td>".$row_by_region['route']." <span class='text_red'>↘".($row_by_region['route_prev']-$row_by_region['route'])."</span></td>";
	}

	if ($row_by_region['route_master'] == $row_by_region['route_master_prev']) {
		$output.="<td>".$row_by_region['route_master']."</td>";
	} elseif ($row_by_region['route_master'] > $row_by_region['route_master_prev']) {
		$output.="<td>".$row_by_region['route_master']." <span class='text_green'>↗".($row_by_region['route_master']-$row_by_region['route_master_prev'])."</span></td>";
	} elseif ($row_by_region['route_master'] < $row_by_region['route_master_prev']) {
		$output.="<td>".$row_by_region['route_master']." <span class='text_red'>↘".($row_by_region['route_master_prev']-$row_by_region['route_master'])."</span></td>";
	}

	if ($row_by_region['platform'] == $row_by_region['platform_prev']) {
		$output.="<td>".$row_by_region['platform']."</td>";
	} elseif ($row_by_region['platform'] > $row_by_region['platform_prev']) {
		$output.="<td>".$row_by_region['platform']." <span class='text_green'>↗".($row_by_region['platform']-$row_by_region['platform_prev'])."</span></td>";
	} elseif ($row_by_region['platform'] < $row_by_region['platform_prev']) {
		$output.="<td>".$row_by_region['platform']." <span class='text_red'>↘".($row_by_region['platform_prev']-$row_by_region['platform'])."</span></td>";
	}

	if ($row_by_region['stop_position'] == $row_by_region['stop_position_prev']) {
		$output.="<td>".$row_by_region['stop_position']."</td>";
	} elseif ($row_by_region['stop_position'] > $row_by_region['stop_position_prev']) {
		$output.="<td>".$row_by_region['stop_position']." <span class='text_green'>↗".($row_by_region['stop_position']-$row_by_region['stop_position_prev'])."</span></td>";
	} elseif ($row_by_region['stop_position'] < $row_by_region['stop_position_prev']) {
		$output.="<td>".$row_by_region['stop_position']." <span class='text_red'>↘".($row_by_region['stop_position_prev']-$row_by_region['stop_position'])."</span></td>";
	}

	if ($row_by_region['station'] == $row_by_region['station_prev']) {
		$output.="<td>".$row_by_region['station']."</td>";
	} elseif ($row_by_region['station'] > $row_by_region['station_prev']) {
		$output.="<td>".$row_by_region['station']." <span class='text_green'>↗".($row_by_region['station']-$row_by_region['station_prev'])."</span></td>";
	} elseif ($row_by_region['station'] < $row_by_region['station_prev']) {
		$output.="<td>".$row_by_region['station']." <span class='text_red'>↘".($row_by_region['station_prev']-$row_by_region['station'])."</span></td>";
	}

	$output.="</tr>";
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

$output.="</tbody></table></div>";

pg_close($dbconn);

$page_title='Статистика маршрутов общественного транспорта OpenStreetMap';
$page = 'statistics';
include(TEMPLATE_PATH);
?>
