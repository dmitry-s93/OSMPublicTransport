<?php
include_once ('../include/config.php');

$place_id = $_GET['place_id'];

if ($place_id == "null") {
	$r_validation=
		"transport_location.region_id=".$_GET['region_id']."
		and transport_location.place_id is NULL";
} else {
	$r_validation="transport_location.place_id=".$place_id;
}

$sql_place = pg_query("
SELECT
	id,
	name
FROM places
WHERE
	id=".$place_id
) or die(mysql_error());

$sql_routes= pg_query("
SELECT
	transport_routes.id,
	transport_routes.tags->'route' as route,
	transport_routes.tags->'ref' as ref,
	transport_routes.tags->'name' as name,
	transport_routes.tags->'from' as from,
	transport_routes.tags->'via' as via,
	transport_routes.tags->'to' as to,
	transport_routes.tags->'operator' as operator,
	transport_routes.tags->'network' as network,
	transport_routes.length
FROM
	transport_routes, transport_location
WHERE
	transport_routes.tags->'route' in (".PUBLIC_TRANSPORT.") and
	transport_routes.id=transport_location.route_id and
	".$r_validation."
ORDER BY route, substring(transport_routes.tags->'ref' from '^\\d+')::int
") or die(mysql_error());

$row = pg_fetch_assoc($sql_place);

if ($place_id !== "null") {
	$place_name=$row['name'];
} else
{
	$place_name="вне населенных пунктов";
}

$output = "<h2 align=center>Список маршрутов (".$place_name.")</h2>";

$output.="
<table border width=100%>
	<thead>
		<tr>
			<th>id</th>
			<th>route</th>
			<th>ref</th>
			<th>name</th>
			<th>from</th>
			<th>to</th>
			<th>operator</th>
			<th>network</th>
			<th>Длина</th>
		</tr>
	</thead>";

while ($row = pg_fetch_assoc($sql_routes)){
	$output.=
	"<tr class='highlight'>
		<td><a href='http://www.openstreetmap.org/relation/".$row['id']."'>".$row['id']."</a> (<a href='http://localhost:8111/import?url=http://api.openstreetmap.org/api/0.6/relation/".$row['id']."/full'>JOSM</a>, <a href='http://ra.osmsurround.org/analyze.jsp?relationId=".$row['id']."'>analyze</a>)</td>
		<td>".$row['route']."</td>";
	if ($row['ref']=="") {
		$output.="<td class='warning'>-</td>";
	} else {
		$output.="<td>".$row['ref']."</td>";
	}
	if ($row['name']=="") {
		$output.="<td class='warning'>-</td>";
	} else {
		$output.="<td>".$row['name']."</td>";
	}
	if ($row['from']=="") {
		$output.="<td class='warning'>-</td>";
	} else {
		$output.="<td>".$row['from']."</td>";
	}
	if ($row['to']=="") {
		$output.="<td class='warning'>-</td>";
	} else {
		$output.="<td>".$row['to']."</td>";
	}
	if ($row['operator']=="") {
		$output.="<td>-</td>";
	} else {
		$output.="<td>".$row['operator']."</td>";
	}
	if ($row['network']=="") {
		$output.="<td>-</td>";
	} else {
		$output.="<td>".$row['network']."</td>";
	}
	$output.="<td>".round($row['length']/1000,3)." км.</td>
	</tr>";
}

$output.="</tbody></table><br>";

pg_close($dbconn);

$page = 'validation';
include(TEMPLATE_PATH);
?>
