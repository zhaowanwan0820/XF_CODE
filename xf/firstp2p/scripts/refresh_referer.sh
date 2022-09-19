#!/bin/sh

echo 'start at '$(date +%Y-%m-%d:%H:%M:%S) >> /tmp/refreshreferer.log 2>&1
if [ -z "$1" ]
then
   echo "刷数据  1 [并发数]"
   exit;
fi
    if [ -n "$2" ]
        then
            i=0
            while (( $i<$2 ))
            do
            let "i++"
            /apps/product/php/bin/php /apps/product/nginx/htdocs/firstp2p/scripts/refresh_referer.php $1 $2 $i & >> /tmp/refreshreferer.log 2>&1
         
            done
    fi


echo 'end at '$(date +%Y-%m-%d:%H:%M:%S) >> /tmp/refreshreferer.log 2>&1
