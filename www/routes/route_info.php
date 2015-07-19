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

//Check version
$sql_route_version = pg_query("
	SELECT
		--relations.id,
		--relations.tags->'route' as type,
		--relations.tags->'ref' as ref,
		count(relation_members.member_role) as count
	FROM transport_routes, relation_members, transport_location
	WHERE
		transport_location.place_id=".$r_place." and
		transport_location.route_id=transport_routes.id and
		relation_members.relation_id=transport_routes.id and
		--relation_members.member_role in ('forward','backward','forward:stop','backward:stop') and
		relation_members.member_role in ('forward:stop','backward:stop') and
		transport_routes.tags->'route'='".$r_type."' and
		transport_routes.tags->'ref'='".$r_ref."'
	");

if (pg_fetch_assoc($sql_route_version)['count']>0) {
	$route_version=1;
} else
{
	$route_version=2;
}

//--------------------------------------------------------------------------

$sql_route = pg_query("
	SELECT
		transport_routes.id,
		transport_routes.tags->'route' as type,
		transport_routes.tags->'ref' as ref,
		transport_routes.tags->'from' as route_from,
		transport_routes.tags->'via' as route_via,
		transport_routes.tags->'to' as route_to,
		transport_routes.length as length
	FROM transport_routes, transport_location
	WHERE
		transport_location.place_id=".$r_place." and
		transport_location.route_id=transport_routes.id and
		transport_routes.tags->'route'='".$r_type."' and
		transport_routes.tags->'ref'='".$r_ref."'
	");

$row = pg_fetch_assoc($sql_place);
$place_id=$row['id'];
$place_name=$row['name'];

$output = "<div class='content_body'><h2 align=center>".$transport_type_names[$r_type]." №".$r_ref." (<a href='routes_list?id=".$place_id."'>".$place_name."</a>)</h2>";

while ($row = pg_fetch_assoc($sql_route)){

	if ($route_version==2) {
		//New version
		if ($row['route_from']<>'' and $row['route_to']<>'') {
			$pt_name=": ".$row['route_from']." ⇨ ".$row['route_to'];
		} else
		{
			$pt_name="";
		}

		$route_name=$transport_type_names[$r_type]." ".$row['ref'].$pt_name;
		$output.="<p><b>".$route_name."</b> [<a href='../#route=".$row['id']."'>показать на карте</a>]</b><br>
		Протяженность маршрута: ".round($row['length']/1000,3)." км.</p>";

		$sql_stop = pg_query("
			SELECT
				transport_stops.id,
				CASE
					WHEN (transport_stops.tags::hstore ? 'name')
					THEN transport_stops.tags->'name'
					ELSE stop_area.tags->'name'
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
				relation_members.relation_id=".$row['id']." and
				relation_members.member_id=transport_stops.id and
				relation_members.member_role in ('stop','stop_entry_only','stop_exit_only')
			ORDER BY
				relation_members.sequence_id;
		");
		
		$sql_platform = pg_query("
			SELECT
				transport_stops.id,
				CASE
					WHEN (transport_stops.tags::hstore ? 'name')
					THEN transport_stops.tags->'name'
					ELSE stop_area.tags->'name'
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
				relation_members.relation_id=".$row['id']." and
				relation_members.member_id=transport_stops.id and
				relation_members.member_role in ('platform','platform_entry_only','platform_exit_only')
			ORDER BY
				relation_members.sequence_id;
		");

		$output.="<pre>";

		$i=1;
		if (pg_num_rows($sql_stop)>pg_num_rows($sql_platform)) {
			while ($row_stop = pg_fetch_assoc($sql_stop)){
				$output.="&#9;".$i++ .". ". $row_stop['name'] . "<br>";
			}
		} else {
			while ($row_stop = pg_fetch_assoc($sql_platform)){
				$output=$output."&#9;".$i++ .". ". $row_stop['name'] . "<br>";
			}
		}
		$output.="</pre>";

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
			relation_members.relation_id=".$row['id']." and
			relation_members.member_id=transport_stops.id and
			relation_members.member_role in ('forward:stop','backward:stop')
		ORDER BY platform_order
		");

		$i=1; $j=1; $forward=''; $backward='';
		while ($row_stop = pg_fetch_assoc($sql_stop)){
			if ($row_stop['role']=="forward:stop") {
				$forward=$forward."&#9;".$i++ .". ". $row_stop['name'] . "<br>";
			}
			if ($row_stop['role']=="backward:stop") {
				$backward=$backward."&#9;".$j++ .". ". $row_stop['name'] . "<br>";
			}
		}

		$output.="<p><b><a href='http://openstreetmap.org/relation/".$row['id']."'>".$transport_type_names[$r_type]." ".$row['ref']."</a>: прямой маршрут</b><br></p>";
		$output.="<pre>".$forward."</pre>";
		$output.="<p><b><a href='http://openstreetmap.org/relation/".$row['id']."'>".$transport_type_names[$r_type]." ".$row['ref']."</a>: обратный маршрут</b><br></p>";
		$output.="<pre>".$backward."</pre>";

		$output.="<p><font color=#FF0000>Внимание! Маршрут выполнен по старой схеме. Некоторая информация может быть недоступна.</font></p>";

	}
}

$output .= "</div>";

pg_close($dbconn);

$page_title=$place_name." - ".$transport_type_names[$r_type].' №'.$r_ref;
$page = 'routes';
include(TEMPLATE_PATH);
?>
