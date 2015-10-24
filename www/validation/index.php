<?php
include_once ('../include/config.php');

$sql_quality= pg_query("
SELECT
	regions.id as region_id,
	regions.name as region_name,
	t_validation.routes,
	t_validation.no_ref,
	t_validation.no_name,
	t_validation.no_from_to,
	t_validation_prev.routes as routes_prev,
	t_validation_prev.no_ref as no_ref_prev,
	t_validation_prev.no_name as no_name_prev,
	t_validation_prev.no_from_to as no_from_to_prev
FROM
	regions LEFT JOIN
	(SELECT
		region_id,
		routes,
		no_ref,
		no_name,
		no_from_to
	FROM
		transport_validation) as t_validation
	ON regions.id=t_validation.region_id
	LEFT JOIN
	(SELECT
		region_id,
		routes,
		no_ref,
		no_name,
		no_from_to
	FROM
		transport_validation_prev) as t_validation_prev
	ON regions.id=t_validation_prev.region_id
ORDER BY region_name
");

$output = "<div class='content_body_table'><h2 align=center>Качество маршрутов по регионам</h2>";

$output.="
<table border width=100%>
	<thead>
		<tr>
			<th width=28%>Регионы</th>
			<th width=18%>Маршруты (направления)</th>
			<th width=18%>Маршруты без ref</th>
			<th width=18%>Маршруты без name</th>
			<th width=18%>Маршруты без from/to</th>
		</tr>
	</thead>
	<tbody>";

while ($row = pg_fetch_assoc($sql_quality)){
	$output.=
	"<tr class='highlight'>
		<td><a href='region?id=".$row['region_id']."'>".$row['region_name']."</td>";

	$output.="<td>".($row['routes']+0);
	if ($row['routes'] > $row['routes_prev']) {
		$output.=" <span class='text_green'>↗".($row['routes']-$row['routes_prev'])."</span>";
	} elseif ($row['routes'] < $row['routes_prev']) {
		$output.=" <span class='text_red'>↘".($row['routes_prev']-$row['routes'])."</span>";
	}
	$output.="</td>";

	if ($row['routes'] > 0) {
		$no_ref_percent=number_format(round($row['no_ref']/$row['routes']*100,2),2);
		$no_name_percent=number_format(round($row['no_name']/$row['routes']*100,2),2);
		$no_from_to_percent=number_format(round($row['no_from_to']/$row['routes']*100,2),2);
	} else {
		$no_ref_percent="0.00";
		$no_name_percent="0.00";
		$no_from_to_percent="0.00";
	}

	$output.="<td><a href='routes?id=".$row['region_id']."&val=ref'>".$no_ref_percent."%</a> (".($row['no_ref']+0);
	if ($row['no_ref'] > $row['no_ref_prev']) {
		$output.=" <span class='text_red'>↗".($row['no_ref']-$row['no_ref_prev'])."</span>";
	} elseif ($row['no_ref'] < $row['no_ref_prev']) {
		$output.=" <span class='text_green'>↘".($row['no_ref_prev']-$row['no_ref'])."</span>";
	}
	$output.=")</td>";

	$output.="<td><a href='routes?id=".$row['region_id']."&val=name'>".$no_name_percent."%</a> (".($row['no_name']+0);
	if ($row['no_name'] > $row['no_name_prev']) {
		$output.=" <span class='text_red'>↗".($row['no_name']-$row['no_name_prev'])."</span>";
	} elseif ($row['no_name'] < $row['no_name_prev']) {
		$output.=" <span class='text_green'>↘".($row['no_name_prev']-$row['no_name'])."</span>";
	}
	$output.=")</td>";

	$output.="<td><a href='routes?id=".$row['region_id']."&val=from_to'>".$no_from_to_percent."%</a> (".($row['no_from_to']+0);
	if ($row['no_from_to'] > $row['no_from_to_prev']) {
		$output.=" <span class='text_red'>↗".($row['no_from_to']-$row['no_from_to_prev'])."</span>";
	} elseif ($row['no_from_to'] < $row['no_from_to_prev']) {
		$output.=" <span class='text_green'>↘".($row['no_from_to_prev']-$row['no_from_to'])."</span>";
	}
	$output.=")</td>";

	$output.="
	</tr>";
}

$output.="</tbody></table></div>";

pg_free_result($sql_quality);
pg_close($dbconn);

$page_title="Качество маршрутов общественного транспорта OpenStreetMap";
$page = 'validation';
include(TEMPLATE_PATH);
?>
