#!/bin/bash
cd $(dirname $(readlink -e $0))
. ./config.sh

pg_dump -h $db_host -U $db_user --no-password --format custom --verbose -f BackUp/db_backup.backup --schema "public" $db_name
