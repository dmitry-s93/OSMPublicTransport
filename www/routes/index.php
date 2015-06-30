<?php
include_once ('../include/config.php');

$sql_districts = pg_query("
SELECT federal_district
FROM regions
GROUP BY federal_district
ORDER BY federal_district
") or die(mysql_error());
    
$output = "<h2 align=center>Список регионов</h2>";

while ($row_district = pg_fetch_assoc($sql_districts)){	
	$output=$output. "<h3>" . $row_district['federal_district'] . ":</h3>";	
	
	$output=$output."<p align=justify>";
	
	$sql_regions = pg_query("
	SELECT id, federal_district, name
	FROM regions
	WHERE federal_district='".$row_district['federal_district']."'
	ORDER BY name
	") or die(mysql_error());
	
	$len = pg_num_rows($sql_regions); $tmp=0;
	while ($row_region = pg_fetch_assoc($sql_regions)){
		$tmp++;
		$output=$output. "<a href='region.php?id=" . $row_region['id'] . "'>" . $row_region['name'] . "</a>";	
		if ($tmp<$len)
		{
			$output=$output.", ";
		}	
	}
	
	$output=$output."</p>";
}
    


// Очистка результата
pg_free_result($sql_districts);
pg_free_result($sql_regions);

// Закрытие соединения
pg_close($dbconn);

$page_title='Маршруты общественного транспорта OpenStreetMap по регионам';
$page = 'routes';
include(TEMPLATE_PATH);
?>
