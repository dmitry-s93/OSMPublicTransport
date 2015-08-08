<?php
include_once ('../include/config.php');

function getPoint($xtile, $ytile, $zoom) {
	$n = pow(2, $zoom);
	$lon_deg = $xtile / $n * 360.0 - 180.0;
	$lat_deg = rad2deg(atan(sinh(pi() * (1 - 2 * $ytile / $n))));
	return $lon_deg . "," . $lat_deg;
}

$type = $_GET["type"];
$x = intval($_GET["x"]);
$y = intval($_GET["y"]);
$z = intval($_GET["z"]);

$point1 = getPoint($x, $y, $z);
$point2 = getPoint($x+1, $y+1, $z);

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

switch ($type) {
	case "station":
		$TagsArr[] = $tags_station;
		break;
	case "platform":
		$TagsArr[] = $tags_platform;
		break;
	case "stop_pos":
		$TagsArr[] = $tags_stop_position;
		break;
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
			WHEN (stop_area.tags::hstore ? 'name')
			THEN stop_area.tags->'name'
			ELSE transport_stops.tags->'name'
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
		(( ST_Contains(ST_SetSRID(ST_MakeBox2D(ST_Point(".$point1."), ST_Point(".$point2.")), 4326), geom) )
			OR
		( ST_Intersects(ST_SetSRID(ST_MakeBox2D(ST_Point(".$point1."), ST_Point(".$point2.")), 4326), geom) ))
	LIMIT
		150
");

function geoJsonEncode($query) {
	$geojson = array(
		"type" => "FeatureCollection",
		"features" => array()
	);
	
	if (pg_num_rows($query) > 0) {
		while ($row = pg_fetch_assoc($query)) {
			if ($row['geom'] != "") {
				$geojson['features'][] = array(
					"type" => "Feature",
					"properties" => array(
						"id" => $row['id'],
						"type" => $row['type'],
						"name" => addslashes($row['name'])
					),
					"geometry" => json_decode($row['geom'], true)
				);
			}
		}
	}
		
	return json_encode($geojson);
}

$output = geoJsonEncode($sql_query);

header('Content-type: application/vnd.geo+json; charset=utf-8');
echo $output;