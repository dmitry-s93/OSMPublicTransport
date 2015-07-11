<?php
include_once ('../include/config.php');

$r_id = $_GET['id'];

$sql_place = pg_query("
SELECT
	id,
	name
FROM places
WHERE
	id='$r_id'
	");

$row = pg_fetch_assoc($sql_place);
$place_id=$row['id'];
$place_name=$row['name'];
$output = "<div class='content_body'><h2 align=center>Маршруты общественного транспорта (".$place_name.")</h2>";

$pt_array = explode(',',PUBLIC_TRANSPORT);

for ($i = 0; $i < count($pt_array); $i++) {

	$sql_transport = pg_query("
	SELECT
		--relations.id,
		transport_routes.tags->'route' as type,
		transport_routes.tags->'ref' as ref
	FROM transport_routes, transport_location
	WHERE
		transport_location.place_id=".$r_id." and
		transport_location.route_id=transport_routes.id and
		transport_routes.tags->'route'=".$pt_array[$i]." and
		transport_routes.tags->'ref'<>''
	GROUP BY type, ref
	ORDER BY type, substring(transport_routes.tags->'ref' from '^\\d+')::int
	");

	$pt_count=pg_num_rows($sql_transport);

	if (pg_num_rows($sql_transport)>0) {
		switch (str_replace("'",'',$pt_array[$i])) {
			case "bus": $pt_name="Автобусы:"; break;
			case "trolleybus": $pt_name="Троллейбусы:"; break;
			case "share_taxi": $pt_name="Маршрутные такси:"; break;
			case "tram": $pt_name="Трамваи:"; break;
			case "train": $pt_name="Поезда:"; break;
		}
		$tmp=0;
		$output .=
			"<h3>".$pt_name."</h3>".
			"<p align=justify>";
		while ($row = pg_fetch_assoc($sql_transport)){
			$tmp++;
			$output .= "<a href='route_info.php?id=".$place_id."&type=" . $row['type'] . "&ref=" . $row['ref'] ."'>". $row['ref'] . "</a>";
			if ($tmp<$pt_count)
			{
				$output .= ", ";
			}
		}
		$output .= "</p>";
	}
}

$output .= "</div>";

pg_free_result($sql_transport);

pg_close($dbconn);

$page_title=$place_name.' - маршруты общественного транспорта OpenStreetMap';
$page = 'routes';
include(TEMPLATE_PATH);
?>
