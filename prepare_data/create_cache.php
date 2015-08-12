<?php

$domain = 'http://osmpublictransport/';

$ZoomRange = array(14, 14); // start and finish zoom
$LatLonStart = array("57.8613", "28.2164"); // left-top coordinates
$LatLonFinish = array("57.7316", "28.468"); // right-bottom coordinates
$Feature = 'platform';

function getTileNumber($LatLon, $zoom) {
	$lat = $LatLon[0];
	$lon = $LatLon[1];
	$xtile = floor((($lon + 180) / 360) * pow(2, $zoom));
	$ytile = floor((1 - log(tan(deg2rad($lat)) + 1 / cos(deg2rad($lat))) / pi()) /2 * pow(2, $zoom));
	return array(
		"x" => $xtile,
		"y" => $ytile
	);
}

$savepath = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..') . DIRECTORY_SEPARATOR . 'www' . DIRECTORY_SEPARATOR;

for($zoom = $ZoomRange[0]; $zoom <= $ZoomRange[1]; $zoom++) {

	$start = getTileNumber($LatLonStart, $zoom);
	$finish = getTileNumber($LatLonFinish, $zoom);

	for($x = $start["x"]; $x <= $finish["x"]; $x++) {
		$dirpath = $savepath . $Feature . DIRECTORY_SEPARATOR . $zoom . DIRECTORY_SEPARATOR . $x;
		mkdir($dirpath, 0777, TRUE);

		for($y = $start["y"]; $y <= $finish["y"]; $y++) {

			$url = $domain . "ajax/get_json_tile.php?type=$Feature&x=$x&y=$y&z=$zoom";
			$filepath = $dirpath . DIRECTORY_SEPARATOR . $y . ".geojson";

			$out = fopen($filepath,"wb");
			if ($out == FALSE){ 
			  print "File not opened"; 
			  exit;
			} 

			$ch = curl_init(); 

			curl_setopt($ch, CURLOPT_FILE, $out); 
			curl_setopt($ch, CURLOPT_HEADER, 0); 
			curl_setopt($ch, CURLOPT_URL, $url); 

			curl_exec($ch);

			curl_close($ch); 
		}
	}
	
}