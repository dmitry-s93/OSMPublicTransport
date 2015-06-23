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

		<script src='template/js/jquery-2.1.4.min.js'></script>
		<script src='template/js/leaflet.js'></script>
		<script src='template/js/Control.FullScreen.js'></script>
		<script src='template/js/L.Control.Locate.min.js' ></script>";

if (isset($_GET['id'])) {
	$r_id = $_GET['id'];

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

	$output.="<script>";

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
				}";
		}
	}

	if (pg_num_rows($sql_stops) > 0) {
		$output.="
		geojsonStops = { 'type': 'FeatureCollection','features': [";
		while ($row_stops = pg_fetch_assoc($sql_stops)){
			if ($row_stops['geom'] != "") {
				$output.="
					{
						'type': 'Feature',
						'properties': {
							'id':'".$row_stops['id']."',
							'type': 'stop_position',
							'name': '".$row_stops['name']."',
							'description':'Место остановки'
						},
						'geometry': ".$row_stops['geom']."
					},";
				}
		}
		$output.="]}";
	}

	if (pg_num_rows($sql_platforms) > 0) {
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
							'id':'".$row_platforms['id']."',
							'type': 'platform',
							'name': '".$row_platforms['name']."',
							'description':'".$description."'
						},
						'geometry': ".$row_platforms['geom']."
					},";
				}
		}
		$output.="]}";
	}

	$output.="</script>";
}

$output.="
	<script type='text/javascript'>
		function SetList() {
			var list_id = document.getElementById('SelectList').selectedIndex;
			if (list_id == 0) {
				document.getElementById('platform-list').style.display = 'block';
				document.getElementById('stop-position-list').style.display = 'none';
			}
			if (list_id == 1) {
				document.getElementById('platform-list').style.display = 'none';
				document.getElementById('stop-position-list').style.display = 'block';
			}
		}
	</script>

	<div id='map' class='map'></div>
	<div id='topMessageBox' class='box'></div>
	<div id='infoPanel' class='box' style='display: none;'>
		<div id='infoPanelTop'><a href='/'><i class='fa fa-times-circle'></i></a></div>
		<form action='' align='center'>
				<select id='SelectList' onchange='SetList()'>
					<option value='platform'> Остановки / платформы </option>
					<option value='stop_position'> Места остановок </option>
				</select>
		</form>
		<div id='infoPanelMiddle'>
			<ol id='platform-list' class='marker-list'></ol>
			<ol id='stop-position-list' class='marker-list' style='display: none;'></ol>
		</div>
	</div>
	";

$output.="<script src='template/js/map.js'></script>";

$page_title="Карта маршрутов общественного транспорта";
$page = 'main';
include("template/template.html");

?>
