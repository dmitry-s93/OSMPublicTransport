<?php

$domain = 'http://osmpublictransport/'; // the requested url

$ZoomRange = array(14, 14); // start and finish zoom
$Feature = 'platform';

$bBoxes = array(
	'Moscow' => array(
		'start' => array(57.8613, 28.2164), // left-top coordinates
		'finish' => array(57.7316, 28.468), // right-bottom coordinates
	),
	'Saint Petersburg' => array(
		'start' => array(60.0844, 30.0909),
		'finish' => array(59.7315, 30.6656),
	),
);

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

	foreach ($bBoxes as $bKey=>$bBox) {
		echo "Generating bbox: $bKey, zoom: $zoom...\n";
		
		$start = getTileNumber($bBox['start'], $zoom);
		$finish = getTileNumber($bBox['finish'], $zoom);

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
}