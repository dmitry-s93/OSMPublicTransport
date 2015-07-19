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

$sql_stops = pg_query("
	SELECT
		transport_stops.id,
		CASE
			WHEN (transport_stops.tags::hstore ? 'name')
			THEN transport_stops.tags->'name'
			ELSE stop_area.tags->'name'
		END as name,
		relation_members.member_role as role,
		ST_AsGeoJSON(transport_stops.geom) as geom
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
		relation_members.relation_id=".$r_id." and
		relation_members.member_id=transport_stops.id and
		relation_members.member_role in ('stop','stop_exit_only','stop_entry_only')
	ORDER BY
		relation_members.sequence_id;
");

$sql_platforms = pg_query("
	SELECT
		transport_stops.id,
		CASE
			WHEN (transport_stops.tags::hstore ? 'name')
			THEN transport_stops.tags->'name'
			ELSE stop_area.tags->'name'
		END as name,
		relation_members.member_role as role,
		ST_AsGeoJSON(transport_stops.geom) as geom
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
		relation_members.relation_id=".$r_id." and
		relation_members.member_id=transport_stops.id and
		relation_members.member_role in ('platform','platform_entry_only','platform_exit_only')
	ORDER BY
		relation_members.sequence_id;
");

$result="";

while ($row_route = pg_fetch_assoc($sql_route)){
	if ($row_route['geom'] != "") {
		$result.="
			geojsonRoute = {
				'type': 'Feature',
				'properties': {
					'type': '".$transport_type_names[$row_route['type']]."',
					'ref': '".$row_route['ref']."',
					'from': '".$row_route['route_from']."',
					'to' :'".$row_route['route_to']."',
					'length':'".round($row_route['length']/1000,3)."'
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
