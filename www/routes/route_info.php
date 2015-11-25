<?php
include_once ('../include/config.php');

$r_place = $_GET['id'];
$r_type = $_GET['type'];
$r_ref = $_GET['ref'];

$sql_place = pg_query("
SELECT
	id,
	name
FROM places
WHERE
	id=".$r_place
);

$sql_route = pg_query("
	SELECT
		transport_routes.id,
		transport_routes.tags->'route' as type,
		transport_routes.tags->'ref' as ref,
		transport_routes.tags->'from' as route_from,
		transport_routes.tags->'via' as route_via,
		transport_routes.tags->'to' as route_to,
		transport_routes.length,
		transport_routes.version
	FROM transport_routes, transport_location
	WHERE
		transport_location.place_id=".$r_place." and
		transport_location.route_id=transport_routes.id and
		transport_routes.tags->'route'='".$r_type."' and
		transport_routes.tags->'ref'='".$r_ref."'
	");

$row_place = pg_fetch_assoc($sql_place);
$place_id=$row_place['id'];
$place_name=$row_place['name'];

$output = "<div class='content_body'><h2 align=center>".$transport_type_names[$r_type]." ".$r_ref." (<a href='routes_list?id=".$place_id."'>".$place_name."</a>)</h2>";

while ($row_route = pg_fetch_assoc($sql_route)){

	if ($row_route['version']==2) {
		//New version
		if ($row_route['route_from']<>'' and $row_route['route_to']<>'') {
			$pt_name=": ".$row_route['route_from']." ⇨ ".$row_route['route_to'];
		} else
		{
			$pt_name="";
		}

		$route_name=$transport_type_names[$r_type]." ".$row_route['ref'].$pt_name;
		$output.="<p><b>".$route_name."</b> [<a href='../#route=".$row_route['id']."'>показать на карте</a>]</b><br>
		Протяженность маршрута: ".round($row_route['length']/1000,3)." км.</p>";

		$sql_stop = pg_query("
			SELECT
				transport_stops.id,
				CASE
					WHEN (stop_area.tags::hstore ? 'name')
					THEN stop_area.tags->'name'
					ELSE transport_stops.tags->'name'
				END as name,
				relation_members.member_role as role
			FROM
				relation_members,
				transport_stops
			LEFT JOIN
				(SELECT
					relation_members.member_id,
					relations.tags
				FROM
					relations,
					relation_members
				WHERE
					relations.id=relation_members.relation_id and
					relations.tags->'public_transport'='stop_area'
				) as stop_area
			ON (transport_stops.id = stop_area.member_id)
			WHERE
				relation_members.relation_id=".$row_route['id']." and
				relation_members.member_id=transport_stops.id and
				relation_members.member_role in ('stop','stop_entry_only','stop_exit_only')
			ORDER BY
				relation_members.sequence_id;
		");

		$sql_platform = pg_query("
			SELECT
				transport_stops.id,
				CASE
					WHEN (stop_area.tags::hstore ? 'name')
					THEN stop_area.tags->'name'
					ELSE transport_stops.tags->'name'
				END as name,
				relation_members.member_role as role
			FROM
					relation_members,
					transport_stops
			LEFT JOIN
				(SELECT
					relation_members.member_id,
					relations.tags
				FROM
					relations,
					relation_members
				WHERE
					relations.id=relation_members.relation_id and
					relations.tags->'public_transport'='stop_area'
				) as stop_area
			ON (transport_stops.id = stop_area.member_id)
			WHERE
				relation_members.relation_id=".$row_route['id']." and
				relation_members.member_id=transport_stops.id and
				relation_members.member_role in ('platform','platform_entry_only','platform_exit_only')
			ORDER BY
				relation_members.sequence_id;
		");

		$output.="<ol class='route_info'>";

		if (pg_num_rows($sql_stop)>pg_num_rows($sql_platform)) {
			while ($row_stop = pg_fetch_assoc($sql_stop)){
				$output.="<li>" . $row_stop['name'] . "</li>";
			}
		} else {
			while ($row_stop = pg_fetch_assoc($sql_platform)){
				$output=$output."<li>" . $row_stop['name'] . "</li>";
			}
		}
		$output.="</ol>";

	} else {
		//Old version
		$sql_stop = pg_query("
			SELECT
			transport_stops.id,
			transport_stops.tags->'name' as name,
			relation_members.sequence_id as platform_order,
			relation_members.member_role as role
		FROM
			transport_stops,
			relation_members
		WHERE
			relation_members.relation_id=".$row_route['id']." and
			relation_members.member_id=transport_stops.id and
			relation_members.member_role in ('forward:stop','backward:stop')
		ORDER BY platform_order
		");

		$forward=''; $backward='';
		while ($row_stop = pg_fetch_assoc($sql_stop)){
			if ($row_stop['role']=="forward:stop") {
				$forward=$forward."<li>". $row_stop['name'] . "</li>";
			}
			if ($row_stop['role']=="backward:stop") {
				$backward=$backward."<li>". $row_stop['name'] . "</li>";
			}
		}

		$output.="<p><b><a href='http://openstreetmap.org/relation/".$row_route['id']."'>".$transport_type_names[$r_type]." ".$row_route['ref']."</a>: прямой маршрут</b><br></p>";
		$output.="<ol class='route_info'>".$forward."</ol>";
		$output.="<p><b><a href='http://openstreetmap.org/relation/".$row_route['id']."'>".$transport_type_names[$r_type]." ".$row_route['ref']."</a>: обратный маршрут</b><br></p>";
		$output.="<ol class='route_info'>".$backward."</ol>";

		$output.="<p><font color=#FF0000>Внимание! Маршрут выполнен по старой схеме. Некоторая информация может быть недоступна.</font></p>";

	}
}

$output .= "</div>";

pg_close($dbconn);

$page_title=$place_name." - ".$transport_type_names[$r_type].' '.$r_ref;
$page = 'routes';
include(TEMPLATE_PATH);
?>
