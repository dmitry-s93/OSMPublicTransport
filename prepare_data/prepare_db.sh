#!/bin/bash
cd $(dirname $(readlink -e $0))
. ./config.sh

psql -h $db_host -d $db_name -U $db_user -f "sql_scripts/pgsnapshot_schema_0.6.sql"
psql -h $db_host -d $db_name -U $db_user -f "sql_scripts/public_transport_schema.sql"
