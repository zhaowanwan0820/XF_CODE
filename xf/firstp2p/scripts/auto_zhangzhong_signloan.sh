#!/bin/sh
#usage  ./scripts/auto_zhangzhong_signloan.sh 0 or 1 or 2
if [ -z "$1" ];then
    echo -e "请指定执行的命令参数\n 0 同时签署合同并放款 \n 1 只签署合同 \n 2 只放款"
    exit
fi

if [ "$1" = 0 ] ; then
    echo "同时执行签署合同并放款"
    elif [ "$1" = 1 ] ; then
    echo "只签署合同"
    elif [ "$1" = 2 ] ; then
    echo "只放款"
fi

while [ 1 ]
do
    sleep 1
    /apps/product/php/bin/php ./scripts/auto_zhangzhong_signloan.php $1
done
