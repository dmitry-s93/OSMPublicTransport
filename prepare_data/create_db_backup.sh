#!/bin/bash
cd $(dirname $(readlink -e $0))
. ./config.sh

if [ -f $new_db_backup ]
then
	if [ -f $old_db_backup ]
	then
		echo "+++++ Delete oldest file +++++"
		rm $old_db_backup
	fi
	echo "+++++ Rename the last file +++++"
	mv $new_db_backup $old_db_backup
fi

echo "+++++ Create a database backup +++++"
pg_dump -h $db_host -U $db_user --no-password --format custom --verbose -f $new_db_backup --schema "public" $db_name

echo "+++++ Done +++++"
