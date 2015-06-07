#!/bin/bash
$osmosis_bin_path \
--read-pbf file="$output_file_path" \
--write-pgsql database="$db_name" user="$db_user" password="$db_password"
