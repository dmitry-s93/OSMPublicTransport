#!/bin/bash
cd $(dirname $(readlink -e $0))
. ./config.sh

pg_restore -h $db_host -d $db_name -U $db_user -t "regions" -v "BackUp/regions.backup"
pg_restore -h $db_host -d $db_name -U $db_user -t "places" -v "BackUp/places.backup"
