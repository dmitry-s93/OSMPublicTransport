<?php
include_once ('../include/config.php');

$r_id = $_POST['id'];

$sql_route = pg_query("
	SELECT
		transport_routes.tags->'route' as type,
		transport_routes.tags->'ref' as ref,
		transport_routes.tags->'from' as route_from,
		transport_routes.tags->'to' as route_to,
		ST_AsGeoJSON(geom) as geom,
		transport_routes.length as length
	FROM transport_routes
	WHERE id=".$r_id.";
");

$sql_stops=pg_query("
	SELECT
		transport_stops.id,
		transport_stops.tags->'name' as name,
		relation_members.member_role as role,
		ST_AsGeoJSON(transport_stops.geom) as geom
	FROM
		transport_stops,
		relation_members
	WHERE
		relation_members.relation_id=".$r_id." and
		relation_members.member_id=transport_stops.id and
		relation_members.member_role in ('stop','stop_exit_only','stop_entry_only')
");

$sql_platforms=pg_query("
	SELECT
		transport_stops.id,
		transport_stops.tags->'name' as name,
		relation_members.member_role as role,
		ST_AsGeoJSON(transport_stops.geom) as geom
	FROM
		transport_stops,
		relation_members
	WHERE
		relation_members.relation_id=".$r_id." and
		relation_members.member_id=transport_stops.id and
		relation_members.member_role in ('platform','platform_entry_only','platform_exit_only')
");


$result="";

while ($row_route = pg_fetch_assoc($sql_route)){

	if ($row_route['route_from']<>'' and $row_route['route_to']<>'') {
		$pt_name=": ".$row_route['route_from']." ⇨ ".$row_route['route_to'];
	} else {
		$pt_name="";
	}

	$route_name=$transport_type_names[$row_route['type']]." ".$row_route['ref'].$pt_name;

	if ($row_route['geom'] != "") {
		$result.="
			geojsonRoute = {
				'type': 'Feature',
				'properties': {
					'type': 'route',
					'name': '".$route_name."',
					'description': 'Протяженность маршрута: ".round($row_route['length']/1000,3)." км.'
				},
				'geometry': ".$row_route['geom']."
			}";
	}
}

if (pg_num_rows($sql_stops) > 0) {
	$result.="
	geojsonStops = { 'type': 'FeatureCollection','features': [";
	while ($row_stops = pg_fetch_assoc($sql_stops)){
		if ($row_stops['geom'] != "") {
			$result.="
				{
					'type': 'Feature',
					'properties': {
						'id':'".$row_stops['id']."',
						'type': 'stop',
						'name': '".$row_stops['name']."'
					},
					'geometry': ".$row_stops['geom']."
				},";
		}
	}
	$result.="]}";
}

if (pg_num_rows($sql_platforms) > 0) {
	$result.="
	geojsonPlatforms = { 'type': 'FeatureCollection','features': [";
	while ($row_platforms = pg_fetch_assoc($sql_platforms)){
		if ($row_platforms['geom'] != "") {
			$result.="
				{
					'type': 'Feature',
					'properties': {
						'id':'".$row_platforms['id']."',
						'type': 'platform',
						'name': '".$row_platforms['name']."'
					},
					'geometry': ".$row_platforms['geom']."
				},";
		}
	}
	$result.="]}";
}

echo $result;
?>
