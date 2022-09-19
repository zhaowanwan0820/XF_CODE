#!/bin/bash

HOME=/apps/product/nginx/htdocs/firstp2p/
minute=1

#pid log
PID_LOG=/tmp/bonus_status/
KILL_LOG=/tmp/bonus_cron_kill.log

if [ ! -d $PID_LOG ]; then
    mkdir -p $PID_LOG
fi

if [ $1 == '' ]; then
    echo ""
fi

cmd_shake="scripts/bonus/cron/shake.php"

cd $HOME
CMD_COUNT=`ps -ef | grep "$cmd_shake" | grep -v grep | wc -l`

if [ $CMD_COUNT -le 0 ]; then
    /apps/product/php/bin/php $cmd_shake $1 &
fi

# 进程心跳检测
cd $PID_LOG

if [ "$?" == 0 ];then
    for pid in `find ./ -mmin +"$minute"| grep -v /$ | awk -F '/' '{print $2}'`
    do
        if [ "$pid" != '' ];then
            NOW=`date +%Y-%m-%d_%H:%M`
            echo "$NOW" >>"$KILL_LOG"
            ps aux | grep "$cmd_shake $1" | grep $pid | grep -v grep >>"$KILL_LOG"
            echo "----------------------------------\n">>"$KILL_LOG"
            kill "$pid"
            cd  "$PID_LOG"
            rm -r "$pid"
        fi
    done
fi
