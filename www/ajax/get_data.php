<?php
include_once ('../include/config.php');

$point1 = $_POST['point1'];
$point2 = $_POST['point2'];

$tags_station = "
	transport_stops.tags->'public_transport'='station' or
	transport_stops.tags->'amenity'='bus_station' or
	transport_stops.tags->'railway'='station' or
	transport_stops.tags->'railway'='halt'";
$tags_platform = "
	transport_stops.tags->'public_transport'='platform' or
	transport_stops.tags->'highway'='bus_stop'";
$tags_stop_position = "
	transport_stops.tags->'public_transport'='stop_position' or
	transport_stops.tags->'railway'='stop' or
	transport_stops.tags->'railway'='tram_stop'";

if ($_POST['station'] == 'true') {
	$TagsArr[] = $tags_station;
}
if ($_POST['platform'] == 'true') {
	$TagsArr[] = $tags_platform;
}
if ($_POST['stop_pos'] == 'true') {
	$TagsArr[] = $tags_stop_position;
}

$condition = "";

for ($i = 0; $i < count($TagsArr); $i++) {
	$condition .= $TagsArr[$i];
	if ($i < (count($TagsArr)-1)) {
		$condition .= " or ";
	}
}

$sql_query=pg_query("
	SELECT
		transport_stops.id,
		CASE
			WHEN (".$tags_station.") THEN 'station'
			WHEN (".$tags_platform.") THEN 'platform'
			WHEN (".$tags_stop_position.") THEN 'stop'
			ELSE 'unknown'
		END as type,
		CASE
			WHEN (transport_stops.tags::hstore ? 'name')
			THEN transport_stops.tags->'name'
			ELSE stop_area.tags->'name'
		END as name,
		ST_AsGeoJSON(geom) as geom
	FROM
		transport_stops
	LEFT JOIN
		(SELECT
			relation_members.member_id,
			relations.tags
		FROM relations, relation_members
		WHERE
			relations.id=relation_members.relation_id and
			relations.tags->'public_transport'='stop_area'
		) as stop_area
	ON (transport_stops.id = stop_area.member_id)
	WHERE
		(".$condition.") and
		ST_Contains(ST_SetSRID(ST_MakeBox2D(ST_Point(".$point1."), ST_Point(".$point2.")), 4326), geom)
	LIMIT
		150
");

function geoJsonEncode($query) {
	if (pg_num_rows($query) > 0) {
		$stationResult = "geojson_stations = { 'type': 'FeatureCollection','features': [";
		$platformResult = "geojson_platforms = { 'type': 'FeatureCollection','features': [";
		$stopResult = "geojson_stop_positions = { 'type': 'FeatureCollection','features': [";
		
		while ($row = pg_fetch_assoc($query)) {
			if ($row['geom'] != "") {
				$feature="
					{
						'type': 'Feature',
						'properties': {
							'id': '".$row['id']."',
							'type': '".$row['type']."',
							'name': '".$row['name']."'
						},
						'geometry': ".$row['geom']."
					},";
				}
				
				switch ($row['type']) {
					case 'station':
						$stationResult .= $feature;
						break;
					case 'platform':
						$platformResult .= $feature;
						break;
					case 'stop':
						$stopResult .= $feature;
						break;
				}
		}
		$stationResult .= "]} \n";
		$platformResult .= "]} \n";
		$stopResult .= "]} \n";
		return $stationResult.$platformResult.$stopResult;
	}
}

$output = geoJsonEncode($sql_query);

echo $output;
?>
