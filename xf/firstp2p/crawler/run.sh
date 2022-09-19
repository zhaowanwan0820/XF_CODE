#!/bin/sh
#
# 文件名称：run.sh
# 文件说明：根据对应环境，配置系统环境变量，并启动爬虫程序的入口文件
# 执行说明：传递相应的环境标示($1)到当前脚本即可开始执行爬虫程序，环境标示包括dev,test,online,分别对应开发环境，测试环境，生产环境
# 执行示例：run.sh online
#

echo "begin to organize files"

BASE_DIR=$(cd "$(dirname "$0")"; pwd)
echo $BASE_DIR

cd $BASE_DIR

if [ x$1 = 'x' ] || [ x$1 = 'xonline' ] || [ x$1 = 'xproduct' ] || [ x$1 = 'xproducttest' ]; then
	echo 'Config online Env ok!'
	source ./config/online/env.sh
elif [ x$1 = 'xtest' ]; then
	echo 'Config Test Env ok!'
	source ./config/test/env.sh
elif [ x$1 = 'xdev' ]; then
	echo 'Config Dev Env ok!'
	source ./config/dev/env.sh
fi
echo "organize files success"
echo "run application:"


python crawler.py $1

