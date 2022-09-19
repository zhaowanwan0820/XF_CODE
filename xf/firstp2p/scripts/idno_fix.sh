#!/bin/sh

echo 'start at '$(date +%Y-%m-%d:%H:%M:%S) >> /tmp/idno_fix.log 2>&1
if [ -z "$1" ]
then
   echo "捞数据  1 [表名] [并发数]"
   echo "排重检查 2 [表名]"
   exit;
fi
    if [ -n "$3" ]
        then
            i=0
            while (( $i<$3 ))
            do
            let "i++"
            /apps/product/php/bin/php /apps/product/nginx/htdocs/firstp2p/scripts/idno_fix.php $1 $2 $3 $i & >> /tmp/refreshreferer.log 2>&1
         
            done
    fi


echo 'end at '$(date +%Y-%m-%d:%H:%M:%S) >> /tmp/idno_fix.log 2>&1
