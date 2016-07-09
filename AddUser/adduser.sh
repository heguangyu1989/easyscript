#!/bin/bash

############################### 
# add user shell 
# add user ,set default password, set OUTPUT ip 
# @author heguangyu 
###############################
password="password" #user default password

if [ $# -lt 2 ] ; then
   echo "error params "
   echo "usage ./adduser.sh <username> <ip>"
   exit
fi

useradd $1
echo "${password}" | passwd $1 --stdin

#  ldap:x:55:55:LDAP User:/var/lib/ldap:/bin/false
id=`cat /etc/passwd |grep "${1}:" |awk -F':' '{print $3}' `
echo "user id is ${id}"

iptables -t mangle -A OUTPUT -m owner --uid-owner $id -j MARK --set-mark $id
iptables -t nat -A POSTROUTING -m mark --mark $id -j SNAT --to-source $2

filename=`date "+%Y_%m_%d_%H_%M_%S.iptables"`
echo "iptables save file name is ${filename}"

iptables-save > $filename

service iptables save
service iptables restart 

echo "${1},${2},${password}" >> 'adduser.log'
 

