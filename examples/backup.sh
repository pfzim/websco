#!/bin/sh

# add to crontab: crontab -e -u root
# 8 2 * * * /var/websco/backup.sh

storage_path="/var/websco/backups"
curdate=`date '+%Y-%m-%d-%H%M%S'`

mysqldump --add-drop-table --databases websco | gzip > "${storage_path}/backup-${curdate}-websco.sql.gz"
tar -czpf "${storage_path}/backup-${curdate}-websco.tar.gz" --ignore-failed-read -C /var/www/html websco
/var/websco/rotate.sh -p "${storage_path}" -d 14 -n
