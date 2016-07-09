#!/bin/bash
#--------------------------------------------------------
# 后台发布脚本--服务发布/后台管理页面发布
# @author heguangyu
#--------------------------------------------------------
# example
# ./publishserver.sh /data/admin/ /data/htdocs/ admin 0.0.1 true
# ./publishserver.sh /data/server/ /data/htdocs/ server 0.0.1 true
# ./publishserver.sh /data/admin/ /data/htdocs/ admin 0.0.1 false
# ./publishserver.sh /data/server/ /data/htdocs/ server 0.0.1 false

d=`date `
echo "start $d $1 $2 $3 $4 $5" >> /data/log/publish.log

if [ $# -lt 5 ]
then
        echo "error parameters! need five"
		echo "error parameters! need five" >> /data/log/publish.log
		echo "usage ./publishserver.sh frompath topath admin/server  version  true/fasle"
        exit
fi

function copy(){
    #params: 1:frompath 2:topath 3:copy dirs
	index=0
	while [ ${3[$index]} ]
	do
		name=`echo ${1}${3[$index]}`
		to=`echo ${2}${3[$index]}`
		\cp -rf $name $to
		test -d $name
		stat=$?
		if [ $stat -eq 0 ]
		then
			name=`echo ${name}"/*" `
		fi
		\cp -rf $name $to
		echo ${3[$index]}
		echo ${3[$index]} >> /data/log/publish.log
		index=`expr $index + 1`
	done
}

# ./publish /data/server/ /data/htdocs/ server 0.0.1 false
case $3 in
server) version=`echo "${4}" |sed "s/\./\_/g"`
topath=`echo "${2}/server_${version}/"`
copy_dirs=("common" "server" "framework")
;;
admin) topath=`echo "${2}/admin/"`
copy_dirs=("common" "admin" "framework")
;;
*) echo "param 3 need input type server or admin"
echo "param 3 need input type server or admin" >> /data/log/publish.log
exit
esac

# checkpath
test -d $topath
stat=$?
if [ $stat -eq 1 ]
then
	mkdir $topath
fi

# check if delete dir
case $5 in
true) 
\rm -rf $topath
echo "remove ${topath}"
echo "remove ${topath}" >> /data/log/publish.log
mkdir $topath
echo "mkdir ${topath}"
echo "mkdir ${topath}" >> /data/log/publish.log
;;
*) echo "..."
esac

#start copy
echo "start copy"
copy $1 $topath $copy_dirs

# update game version
glo_p=`echo "${topath}/common/config.php"`
echo $glo_p
echo $glo_p >> /data/log/publish.log
sed -i "s/PUBLISH_TO_CHANGE_VERSION/${4}/g"  $glo_p
 
d=`date `
echo "end $d $1 $2 $3 $4 $5 " >> /data/log/publish.log









