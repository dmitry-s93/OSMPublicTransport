<?php
include_once ('../include/config.php');

$point1 = $_POST['point1'];
$point2 = $_POST['point2'];

if ($_POST['stations'] == 'true') {
	$TagsArr[] = "tags->'public_transport'='station' or tags->'amenity'='bus_station'";
}
if ($_POST['platforms'] == 'true') {
	$TagsArr[] = "tags->'public_transport'='platform' or tags->'highway'='bus_stop'";
}
if ($_POST['stops'] == 'true') {
	$TagsArr[] = "tags->'public_transport'='stop_position' or tags->'tram'='stop'";
}

$condition = "";

for ($i = 0; $i < count($TagsArr); $i++) {
	$condition .= $TagsArr[$i];
	if ($i < (count($TagsArr)-1)) {
		$condition .= " or ";
	}
}

$sql_query=pg_query("
	SELECT * FROM
		(SELECT
			id,
			CASE
				WHEN (tags->'public_transport'='station' or tags->'amenity'='bus_station') THEN 'station'
				WHEN (tags->'public_transport'='platform' or tags->'highway'='bus_stop') THEN 'platform'
				WHEN (tags->'public_transport'='stop_position' or tags->'tram'='stop') THEN 'stop'
				ELSE 'unknown'
			END as type,
			tags->'name' as name,
			ST_AsGeoJSON(geom) as geom
		FROM
			transport_stops
		WHERE
			(".$condition.") and
			ST_Contains(ST_SetSRID(ST_MakeBox2D(ST_Point(".$point1."), ST_Point(".$point2.")), 4326), geom)
		LIMIT
			300) as q1
	ORDER BY type='station',type='platform',type='stop'
");

function geoJsonEncode($query, $name) {
	if (pg_num_rows($query) > 0) {
		$result = $name." = { 'type': 'FeatureCollection','features': [";
		while ($row = pg_fetch_assoc($query)) {
			if ($row['geom'] != "") {
				$result.="
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
		}
		$result.="]}";
	}
	return $result;
}

$output = geoJsonEncode($sql_query, 'geojsonResult');

echo $output;
?>
