#!/bin/bash

################################
# run shell as nologin user
# where i use laravel
# i want run some commands by the user which run php-fpm 
# @author heguangyu 
###############################

if [ $(id -u) != "0" ]; then
    echo "Error: You must be root to run this script!!!"
    exit 1
fi

www=`ps aux|grep php-fpm|grep -v "root"|grep -v "grep"|awk 'NR==1 {print $1}'`
echo $www

/bin/su $www -s artisan list 
