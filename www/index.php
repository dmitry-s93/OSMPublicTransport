<?php
include_once ('include/config.php');

$output="
		<link rel='stylesheet' href='template/css/map.css' />
		<link rel='stylesheet' href='template/css/leaflet.css' />
		<link rel='stylesheet' href='template/css/Control.FullScreen.css' />
		<link rel='stylesheet' href='template/css/L.Control.Locate.min.css' />
		<!--[if lt IE 9]>
			<link rel='stylesheet' href='template/css/L.Control.Locate.ie.min.css'/>
		<![endif]-->

		<div id='map' class='map'></div>
		<script src='template/js/leaflet.js'></script>
		<script src='template/js/Control.FullScreen.js'></script>
		<script src='template/js/L.Control.Locate.min.js' ></script>

		<script>";

if (isset($_GET['id'])) {
	$r_id = $_GET['id'];

	$dbconn = pg_connect("host=".HOST." dbname=".NAME_BD." user=".USER." password='".PASSWORD."'") or die(pg_last_error());

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
		") or die(mysql_error());

	$sql_stops=pg_query("
	SELECT
		transport_stops.tags->'name' as name,
		relation_members.member_role as role,
		ST_AsGeoJSON(transport_stops.geom) as geom
	FROM
		transport_stops,
		relation_members
	WHERE
		relation_members.relation_id=".$r_id." and
		relation_members.member_id=transport_stops.id and
		relation_members.member_role = 'stop'
	");

	$sql_platforms=pg_query("
	SELECT
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

	while ($row_route = pg_fetch_assoc($sql_route)){

		if ($row_route['route_from']<>'' and $row_route['route_to']<>'') {
			$pt_name=": ".$row_route['route_from']." ⇨ ".$row_route['route_to'];
		} else {
			$pt_name="";
		}

		$route_name=$transport_type_names[$row_route['type']]." ".$row_route['ref'].$pt_name;

		if ($row_route['geom'] != "") {
			$output.="
				geojsonRoute = {
					'type': 'Feature',
					'properties': {
						'type': 'route',
						'name': '".$route_name."',
						'description': 'Протяженность маршрута: ".round($row_route['length']/1000,3)." км.'
					},
					'geometry': ".$row_route['geom']."
				},";
		}
	}

	$output.="
	geojsonStops = { 'type': 'FeatureCollection','features': [";
	while ($row_stops = pg_fetch_assoc($sql_stops)){
		if ($row_stops['geom'] != "") {

			//switch ($row_stops['role']) {
				//case 'platform_entry_only': $description = "Только вход"; break;
				//case 'platform_exit_only': $description = "Только выход"; break;
				//default: $description = ""; break;
			//}

			$output.="
				{
					'type': 'Feature',
					'properties': {
						'type': '".$row_stops['role']."',
						'name': '".$row_stops['name']."',
						'description':'Место остановки'
					},
					'geometry': ".$row_stops['geom']."
				},";
			}
	}
	$output.="]}";

	$output.="
	geojsonPlatforms = { 'type': 'FeatureCollection','features': [";
	while ($row_platforms = pg_fetch_assoc($sql_platforms)){
		if ($row_platforms['geom'] != "") {

			switch ($row_platforms['role']) {
				case 'platform_entry_only': $description = "Только вход"; break;
				case 'platform_exit_only': $description = "Только выход"; break;
				default: $description = ""; break;
			}

			$output.="
				{
					'type': 'Feature',
					'properties': {
						'type': '".$row_platforms['role']."',
						'name': '".$row_platforms['name']."',
						'description':'".$description."'
					},
					'geometry': ".$row_platforms['geom']."
				},";
			}
	}
	$output.="]}";
}

$output.="
		</script>
		<script src='template/js/map.js'></script>";

$page_title="Карта маршрутов общественного транспорта";
$page = 'main';		
include("template/template.html");

?>
