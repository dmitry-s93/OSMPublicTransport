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
	<script src='template/js/L.Control.Locate.min.js' ></script>

	<script type='text/javascript'>
		function left_panel_toggle() {
			$('#left_panel_content').toggle();
			if ($('#left_panel_content').css('display') == 'none') {
				$('#left_panel_control').css('left', '0px');
			} else {
				$('#left_panel_control').css('left', '286px');
			}
			map.invalidateSize();
		}
	</script>

	<div id='left_panel'>
		<div id='left_panel_content'></div>
		<div id='left_panel_control' class='leaflet-control' onclick='left_panel_toggle()'>
			<span>⇤<br>⇥</span>
		</div>
	</div>
	<div id='map' class='map'></div>
	";

$output.="<script src='template/js/map.js'></script>";

$page_title="Карта маршрутов общественного транспорта OpenStreetMap";
$page = 'main';
include("template/template.html");

?>
