#!/bin/bash
$osmosis_bin_path \
	--read-pbf file="$input_file_path" \
	--tag-filter accept-relations \
		route_master=bus,trolleybus,share_taxi,tram,train \
		route=bus,trolleybus,share_taxi,tram,train \
		public_transport=stop_area \
	--used-way \
	--used-node \
	--write-pbf file="$temp_path/file1.osm.pbf"

$osmosis_bin_path \
	--read-pbf file="$input_file_path" \
	--node-key-value keyValueList="public_transport.stop_position,public_transport.platform,public_transport.station,highway.bus_stop,amenity.bus_station,railway.stop,railway.tram_stop,railway.halt,railway.station" \
	--used-way \
	--write-pbf file="$temp_path/file2.osm.pbf"

$osmosis_bin_path \
	--read-pbf file="$input_file_path" \
	--way-key-value keyValueList="public_transport.platform,public_transport.station,amenity.bus_station,railway.station" \
	--tag-filter reject-relations \
	--used-node \
	--write-pbf file="$temp_path/file3.osm.pbf"

$osmosis_bin_path \
	--rb file="$temp_path/file1.osm.pbf" \
	--rb file="$temp_path/file2.osm.pbf" \
	--rb file="$temp_path/file3.osm.pbf" \
	--merge \
	--merge \
	--wb file="$output_file_path" \

rm $temp_path/file1.osm.pbf $temp_path/file2.osm.pbf $temp_path/file3.osm.pbf
