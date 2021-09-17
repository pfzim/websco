#!/bin/sh

storage_path="/var/websco/backups"
curdate=`date '+%Y-%m-%d-%H%M%S'`

mysqldump --add-drop-table --databases websco | gzip > "${storage_path}/backup-${curdate}-websco.sql.gz"
/var/websco/rotate.sh -p "${storage_path}" -d 14 -n
