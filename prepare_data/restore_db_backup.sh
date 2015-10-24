#!/bin/bash
cd $(dirname $(readlink -e $0))
. ./config.sh

if [ -f $new_db_backup ]
then
	echo "+++++ Database purging +++++"
	. ./prepare_db.sh

	echo "+++++ Restore the database backup +++++"
	pg_restore -h $db_host -p 5432 -U $db_user -d $db_name --no-password --no-owner --no-acl --data-only --schema public --verbose $new_db_backup

	echo "+++++ Done +++++"
else
	echo "File $new_db_backup does not exist"
fi
