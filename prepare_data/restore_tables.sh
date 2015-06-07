#!/bin/bash
cd $(dirname $(readlink -e $0))
. ./config.sh

pg_restore -d $db_name -U $db_user -t "regions" -v "BackUp/regions.backup"
pg_restore -d $db_name -U $db_user -t "places" -v "BackUp/places.backup"

echo "#############################"
echo "#  Скрипт завершил работу   #"
echo "#############################"
