<?php
include_once ('../include/config.php');

$route_id = $_GET['id'];

$sql_route= pg_query("
SELECT
	transport_routes.id,
	transport_routes.tags->'route' as route,
	transport_routes.tags->'ref' as ref,
	array_to_json(incorrect_routes.fault_pos) as fault_pos

	--substring(transport_routes.tags->'ref' from '^\\d+')::int as int_ref,
	--transport_routes.tags->'name' as name,
	--transport_routes.tags->'from' as from,
	--transport_routes.tags->'via' as via,
	--transport_routes.tags->'to' as to,
	--transport_routes.length,
	--transport_routes.version,
	--transport_routes.is_valid
FROM
	transport_routes,incorrect_routes
WHERE
	transport_routes.id=incorrect_routes.id and
	transport_routes.id=".$route_id."
");

$sql_relation= pg_query("
SELECT
			relation_members.sequence_id as way_pos,
			ways.id as way_id,
			relation_members.member_role,
			ways.tags->'name' as way_name
		FROM relations, relation_members, ways
		WHERE
			relations.id=relation_members.relation_id and
			relation_members.member_id=ways.id and
			relations.id=".$route_id."
		ORDER BY way_pos
");

$route = pg_fetch_assoc($sql_route);

$output = "<div class='content_body'><h2 align=center>Ошибки геометрии: ".$route['route']." ".$route['ref']."</h2>";

$fault_pos = json_decode($route['fault_pos']);

$output.="
<p>На странице отображаются линии из отношения маршрута. Маршрут проверяется на отсутствие разрывов и корректный порядок линий.<br>
Отношение маршрута: <a href='http://openstreetmap.org/relation/".$route_id."' target='_blank'>".$route_id."</a> (<a href='http://localhost:8111/import?url=http://api.openstreetmap.org/api/0.6/relation/".$route_id."/full' target='_blank'>JOSM</a>, <a href='../#route=".$route_id."' target='_blank'>на карте</a>)<br>";
$output.="Ошибки на позициях: ";
foreach ($fault_pos as &$value) {
    $output.="<a href='#p_".$value."'>".$value."</a> ";
}
unset($value);

$output.="
<p>
<table border>
	<thead>
		<tr>
			<th>Позиция</th>
			<th>Элемент</th>
			<th>Название</th>
		</tr>
	</thead>";

while ($relation = pg_fetch_assoc($sql_relation)){
	if (in_array($relation['way_pos'], $fault_pos)) {
		$class=" class='warning'";
	} else {
		$class="";
	}
	$output.=
	"<tr class='highlight'>
		<td".$class."><a id='p_".$relation['way_pos']."'>".$relation['way_pos']."</a></td>
		<td".$class."><a href='http://openstreetmap.org/way/".$relation['way_id']."' target='_blank'>".$relation['way_id']."</a> (<a href='http://localhost:8111/import?url=http://api.openstreetmap.org/api/0.6/way/".$relation['way_id']."/full' target='_blank'>JOSM</a>)</td>";
		if ($relation['way_name']=="") {
			$output.="<td".$class.">-</td>";
		} else {
			$output.="<td".$class.">".$relation['way_name']."</td>";
		}
	$output.="</tr>";
}

$output.="</tbody></table></div>";

pg_close($dbconn);

$page_title=$page_title." - ошибки маршрута ".$route['route']." ".$route['ref']." (".$route['id'].")";
$page = 'validation';
include(TEMPLATE_PATH);
?>
