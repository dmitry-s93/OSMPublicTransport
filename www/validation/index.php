<?php
include_once ('../include/config.php');

$sql_quality= pg_query("
SELECT
	regions.id as region_id,
	regions.name as region_name,
	t_validation.routes,
	t_validation.no_ref,
	t_validation.no_name,
	t_validation.no_from_to
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
ORDER BY region_name
") or die(mysql_error());

$output = "<h2 align=center>Качество маршрутов по регионам</h2>";

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
	$output=$output.
	"<tr class='highlight'>
		<td><a href='region.php?id=".$row['region_id']."'>".$row['region_name']."</td>
		<td>".($row['routes']+0)."</a></td>
		<td><a href='routes.php?id=".$row['region_id']."&val=ref'>".($row['no_ref']+0)."</a></td>
		<td><a href='routes.php?id=".$row['region_id']."&val=name'>".($row['no_name']+0)."</a></td>
		<td><a href='routes.php?id=".$row['region_id']."&val=from_to'>".($row['no_from_to']+0)."</a></td>
	</tr>";
}	

$output=$output."</tbody></table><br>";

pg_free_result($sql_quality);
pg_close($dbconn);		

$page_title="Качество маршрутов общественного транспорта";
$page = 'validation';
include(TEMPLATE_PATH);
?>
