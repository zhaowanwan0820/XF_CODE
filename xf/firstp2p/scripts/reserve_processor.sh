#!/bin/bash

help()
{
cat <<EOF
Options:
    Run processes (eg. nohup $ScriptDir/$ScriptName limit &)
EOF
exit 0
}


ScriptName="$( basename "$0" )"
ScriptDir="$( cd "$( dirname "$0" )" && pwd )"
PHP=/apps/product/php/bin/php
HOME=/apps/product/nginx/htdocs/firstp2p/
#HOME=/home/dev/git/firstp2p/

if [ $# -eq 0 ]
then
help
fi

limit=$1 #子进程数

cd $HOME

while [ 1 ]
do
    count=`ps -ef | grep "reserve_processor.php" | grep -v grep | wc -l`
    if [ $count -lt $limit ]; then
        $PHP ./scripts/reserve_processor.php &
    fi
    sleep 1
done
