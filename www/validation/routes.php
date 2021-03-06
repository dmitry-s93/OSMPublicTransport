<?php
include_once ('../include/config.php');

$region_id = $_GET['id'];

switch ($_GET['val']) {
case "ref":
	$r_validation="not(transport_routes.tags::hstore ? 'ref')";
	$heading="Список маршрутов без ref";
	break;
case "name":
	$r_validation="not(transport_routes.tags::hstore ? 'name')";
	$heading="Список маршрутов без name";
	break;
case "from_to":
	$r_validation="not(transport_routes.tags::hstore ?| ARRAY['from','to'])";
	$heading="Список маршрутов без from/to";
	break;
case "wrong_geom":
	$r_validation="transport_routes.is_valid=false and transport_routes.version=2";
	$heading="Ошибки геометрии";
	break;
}

$sql_region = pg_query("
SELECT
	id,
	name
FROM regions
WHERE
	id=".$region_id
);

$sql_routes= pg_query("
SELECT DISTINCT
	id,
	transport_routes.tags->'route' as route,
	transport_routes.tags->'ref' as ref,
	substring(transport_routes.tags->'ref' from '^\\d+')::int as int_ref,
	transport_routes.tags->'name' as name,
	transport_routes.tags->'from' as from,
	transport_routes.tags->'via' as via,
	transport_routes.tags->'to' as to,
	transport_routes.length,
	transport_routes.version,
	transport_routes.is_valid
FROM
	transport_routes, transport_location
WHERE
	transport_routes.tags->'route' in (".PUBLIC_TRANSPORT.") and
	transport_routes.id=transport_location.route_id and
	transport_location.region_id=".$region_id." and
	".$r_validation."
ORDER BY route, int_ref
");

$row = pg_fetch_assoc($sql_region);
$region_name=$row['name'];

$output = "<div class='content_body_table'><h2 align=center>".$heading." (".$region_name.")</h2>";

if ($_GET['val']=='wrong_geom') {
	$output .= "<p>На странице отображаются маршруты с ошибками геометрии. Маршруты, выполненные по старой схеме, не отображаются.</p>";
}

$output.="
<table border>
	<thead>
		<tr>
			<th width=20%>id</th>
			<th>route</th>
			<th>ref</th>
			<th>name</th>
			<th>from</th>
			<th>to</th>
			<th>Длина</th>
			<th>Ver</th>
			<th>Valid</th>
		</tr>
	</thead>";

while ($row = pg_fetch_assoc($sql_routes)){
	$output.=
	"<tr class='highlight'>
		<td><a href='http://openstreetmap.org/relation/".$row['id']."' target='_blank'>".$row['id']."</a> (<a href='http://localhost:8111/import?url=http://api.openstreetmap.org/api/0.6/relation/".$row['id']."/full' target='_blank'>JOSM</a>, <a href='http://ra.osmsurround.org/analyze.jsp?relationId=".$row['id']."' target='_blank'>analyze</a>)</td>
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
	$output.="<td>".round($row['length']/1000,3)." км.</td>";
	$output.="<td>".$row['version']."</td>";
	if ($row['is_valid']=='t') {
		$output.="<td>true</td>";
	} else {
		if ($row['version']=='2') {
			$output.="<td class='warning'><a href='geometry?id=".$row['id']."'>false</a></td>";
		} else {
			$output.="<td>n/a</td>";
		}
	}

	$output.="</tr>";
}

$output.="</tbody></table></div>";

pg_close($dbconn);

$page_title=$page_title." - ".$heading." (".$region_name.")";
$page = 'validation';
include(TEMPLATE_PATH);
?>
