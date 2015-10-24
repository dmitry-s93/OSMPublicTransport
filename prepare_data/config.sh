osmosis_bin_path="/home/user/osmosis-latest/bin/osmosis"

osm_data_url="http://be.gis-lab.info/data/osm_dump/dump/latest/RU.osm.pbf"
input_file_path="$(dirname $(readlink -e $0))/osm_data/RU.osm.pbf"
output_file_path="$(dirname $(readlink -e $0))/osm_data/routes-ru.osm.pbf"
temp_path="/tmp"

db_host="localhost"
db_name="osm_pt_ru"
db_user="pt_user"
db_password="pt_password"

new_db_backup="osm_data/db_backup.backup"
old_db_backup="osm_data/db_backup(old).backup"
