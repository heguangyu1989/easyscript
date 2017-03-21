#!/bin/bash

############################### 
# run shell as nologin user
# where i use laravel
# i want run some commands by the user which run php-fpm 
# @author heguangyu 
###############################

if [ $(id -u) != "0" ]; then
    echo "Error: You must be root to run this script!!!"
    exit 1
fi

/bin/su www -s artisan list 
