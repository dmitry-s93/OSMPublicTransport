#!/bin/bash
cd $(dirname $(readlink -e $0))
. ./config.sh

psql -d $db_name -U $db_user -f "sql_scripts/create_extensions.sql"
psql -d $db_name -U $db_user -f "sql_scripts/pgsnapshot_schema_0.6.sql"
psql -d $db_name -U $db_user -f "sql_scripts/public_transport_schema.sql"

echo "#############################"
echo "#  Скрипт завершил работу   #"
echo "#############################"
