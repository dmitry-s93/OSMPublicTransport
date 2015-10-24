#!/bin/bash
cd $(dirname $(readlink -e $0))
. ./config.sh

pg_restore -h $db_host -d $db_name -U $db_user -t "regions" -v "osm_data/regions.backup"
pg_restore -h $db_host -d $db_name -U $db_user -t "places" -v "osm_data/places.backup"
