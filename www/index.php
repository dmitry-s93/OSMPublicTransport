<?php
include_once ('include/config.php');

$output = "
	<link rel='stylesheet' href='template/css/map.css' />
	<div id='map' class='map'></div>
	<script src='template/js/leaflet.js'></script>
	
	<link rel='stylesheet' href='template/css/Control.FullScreen.css' />
	<script src='template/js/Control.FullScreen.js'></script>
	
	<link rel='stylesheet' href='template/css/L.Control.Locate.min.css' />
	
	<!--[if lt IE 9]>
		<link rel='stylesheet' href='template/css/L.Control.Locate.ie.min.css'/>
	<![endif]-->

	<script src='template/js/L.Control.Locate.min.js' ></script>
	
	<script src='template/js/map.js'></script>
	";

$page_title="Карта маршрутов общественного транспорта";
$page = 'main';		
include("template/template.html");
?>
