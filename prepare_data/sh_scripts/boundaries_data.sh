osmosis/bin/osmosis \
	--read-pbf file="input/RU.osm.pbf" \
	--tag-filter accept-relations \
		admin_level=* \
		place=city,town,village,hamlet \
	--used-way \
	--used-node \
	--write-pbf file="output/file1.osm.pbf"

osmosis/bin/osmosis \
	--read-pbf file="input/RU.osm.pbf" \
	--tag-filter reject-relations \
	--tag-filter accept-ways \
		place=city,town,village,hamlet \
	--used-node \
	--write-pbf file="output/file2.osm.pbf"

osmosis/bin/osmosis \
	--rb file="output/file1.osm.pbf" \
	--rb file="output/file2.osm.pbf" \
	--merge \
	--wb file="output/ru-boundaries.osm.pbf" \
