#!/bin/bash

help()
{
cat <<EOF
Options:
    Run processes (eg. nohup $ScriptDir/$ScriptName dealTypes &)
EOF
exit 0
}

ScriptName="$( basename "$0" )"
ScriptDir="$( cd "$( dirname "$0" )" && pwd )"
PHP=/apps/product/php/bin/php
HOME=/apps/product/nginx/htdocs/firstp2p/
#HOME=/home/dev/git/firstp2p/
cd $HOME

if [ $# -eq 0 ]
then
help
fi

dealTypes=$1 #借款类型，多个用逗号分割

while [ 1 ]
do
    IFS=',' read -r -a type <<< "$dealTypes"
    for index in "${!type[@]}"; do
        count=`ps -ef | grep "reserve_collector.php ${type[index]}" | grep -v grep | wc -l`
        if [ $count -le 0 ]; then
            $PHP ./scripts/reserve_collector.php ${type[index]} &
        fi
        sleep 1
    done
done
