<?php
include_once ('../include/config.php');

$sql_query=pg_query("
	SELECT
		id,
		tags->'name' as name,
		ST_AsGeoJSON(geom) as geom
	FROM
		transport_stops
	WHERE
		(tags->'highway'='bus_stop' or
		tags->'public_transport'='platform') and
		ST_Contains(ST_SetSRID(ST_MakeBox2D(ST_Point(".$_POST['point1']."), ST_Point(".$_POST['point2'].")), 4326), geom)
	LIMIT
		300;
");

if (pg_num_rows($sql_query) > 0) {
	$result="
	geojsonResult = { 'type': 'FeatureCollection','features': [";
	while ($row = pg_fetch_assoc($sql_query)){
		if ($row['geom'] != "") {
			$result.="
				{
					'type': 'Feature',
					'properties': {
						'id': '".$row['id']."',
						'type': 'platform',
						'name': '".$row['name']."',
						'description':''
					},
					'geometry': ".$row['geom']."
				},";
			}
	}
	$result.="]}";
}

echo $result;
?>
