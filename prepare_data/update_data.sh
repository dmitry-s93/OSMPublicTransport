#!/bin/bash
cd $(dirname $(readlink -e $0))
. ./config.sh

echo "#   Deleting old data   #"
if [ -f $input_file_path ]
then
	echo "+++++ Delete the file: $input_file_path +++++"
	rm $input_file_path
fi
if [ -f $output_file_path ]
then
	echo "+++++ Delete the file: $output_file_path +++++"
	rm $output_file_path
fi

echo "+++++ Downloading new data +++++"
wget --output-document=$input_file_path $osm_data_url

echo "+++++ Data preparation +++++"
. ./sh_scripts/routes_data.sh

echo "+++++ Database purging +++++"
psql -h $db_host -d $db_name -U $db_user -f "sql_scripts/pgsnapshot_schema_0.6.sql"

echo "+++++ Loading data into the database +++++"
. ./sh_scripts/data_to_db.sh

echo "+++++ Processing of data in the database +++++"
psql -h $db_host -d $db_name -U $db_user -f "sql_scripts/prepare_routes_data.sql"

echo "+++++ Cleaning of the database +++++"
psql -h $db_host -d $db_name -U $db_user -f "sql_scripts/clean_db.sql"

echo "+++++ Done +++++"
