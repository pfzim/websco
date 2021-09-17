#!/bin/sh
# script for remove old backup files v0.09.3   pfzim (c) 2010 (44f5709e242b975305161941b30d1573)
# save last ${storage_time} days old backups, all 1 and 15 day of month
# backups other will be deleted

storage_path=''
storage_time=0
no_permanent=no

expired_cmd='rm -f'
awkcmd='awk'


while [ $# -gt 0 ]; do
  key="$1"

  case $key in
    -p|--path)
      storage_path="$2"
      shift
      shift
      ;;
    -d|--days)
      storage_time="$2"
      shift
      shift
      ;;
    -n|--no-permanent)
      no_permanent=yes
      shift
      ;;
    -h|--help)
      storage_time=0
      break
      ;;
    *)
      echo "Unknown argument: $1"
      exit 1
    ;;
  esac
done


echo 'Script for remove old backup files v0.09.3   pfzim (c) 2010'

if [ -z "${storage_path}" -o -z "${storage_time}" -o "${storage_time}" -le 0 ] ; then
  echo 'Usage: rotate.sh -p /var/backups -d 14'
  echo 'Options:'
  echo '  -p|--path          - path to stored backups'
  echo '  -d|--days          - how long store backups'
  echo '  -n|--no-permanent  - do not skip files for 1 and 15 days'
  echo '  -h|--help          - this help'
  exit 1
fi


curdate=`date '+%Y-%m-%d-%H-%M-%S'`
#curdate=`mysql -h 127.0.0.1 -u LOGIN -pPASSWORD -s --skip-column-names -e "SELECT DATE_FORMAT(NOW(), '%Y-%m-%d-%H-%i-%S');"`

year=`echo $curdate | ${awkcmd} -F- '{ print $1+0; }'`
month=`echo $curdate | ${awkcmd} -F- '{ print $2+0; }'`
day=`echo $curdate | ${awkcmd} -F- '{ print $3+0; }'`
hour=`echo $curdate | ${awkcmd} -F- '{ print $4+0; }'`
minute=`echo $curdate | ${awkcmd} -F- '{ print $5+0; }'`
second=`echo $curdate | ${awkcmd} -F- '{ print $6+0; }'`

echo "Today is ${day}.${month}.${year}"
echo "Path: ${storage_path}"
echo "Days: ${storage_time}"
echo "No permanent: ${no_permanent}"

#[ "backup" "<" "backup-2010-04-14-test.tar.gz" ] || ( echo TEST1 ERROR; exit )
#[ "backup" "<" "db_dump.sql.gz" ] && ( echo TEST2 ERROR; exit )

#for filename in `find $storage_path -type f -name 'backup-*' -print`; do
for filename in ${storage_path}/backup-* ; do
	#filename=`basename $filename`
	filename=`echo $filename | ${awkcmd} -F/ '{ print $NF; }'`
	echo -n "$filename"

	if [ ! -f "${storage_path}/${filename}" ] ; then
	    echo " : something gone wrong. file not exist"
	    continue
	fi

#	if [ ! "backup" "<" "$filename" ] ; then
#	    echo " invalid file name"
#	    continue
#	fi

	y=`echo $filename | ${awkcmd} -F- '{ print $2+0; }'`
	m=`echo $filename | ${awkcmd} -F- '{ print $3+0; }'`
	d=`echo $filename | ${awkcmd} -F- '{ print $4+0; }'`

	if [ \( "$d" -lt 1 \) -o \( "$d" -gt 31 \) -o \( "$y" -lt 1 \) ] ; then
	    echo " : invalid date in file name"
	    continue
	fi

	if [ \( "$no_permanent" != "yes" \) -a \( \( "$d" -eq 1 \) -o \( "$d" -eq 15 \) \) ] ; then
	    echo " : never deleted"
	    continue
	fi

	d=$(($d+$storage_time))

	check_again=1

	while [ $check_again -ne 0 ]; do
		check_again=0

		case $(($m)) in
			1|3|5|7|8|10|12) dpm=31 ;;
			4|6|9|11) dpm=30 ;;
			2)	if [ \( $(($y % 4)) -eq 0 \) -a \( \( $(($y % 100)) -ne 0 \) -o \( $(($y % 400)) -eq 0 \) \) ]; then
					dpm=29
				else
					dpm=28
				fi
				;;
			*) echo " : invalid month value: $m"; continue 2 ;;
		esac

		if [ $d -gt $dpm ]; then
			m=$(($m+1))
			d=$(($d-$dpm))
			if [ $m -gt 12 ]; then
				y=$(($y+1))
				m=1
			fi
			check_again=1
		fi
	done

	outdated=0

	if [ $year -gt $y ]; then
		outdated=1
	elif [ $year -lt $y ]; then
	outdated=-1
	elif [ $month -gt $m ]; then
		outdated=1
	elif [ $month -lt $m ]; then
		outdated=-1
	elif [ $day -gt $d ]; then
		outdated=1
	elif [ $day -lt $d ]; then
		outdated=-1
	fi

	if [ $outdated -gt 0 ]; then
		echo " : deleted. expired at ${d}.${m}.${y}"
		${expired_cmd} "${storage_path}/${filename}"
	else
		echo " : saved until ${d}.${m}.${y}"
	fi
done

