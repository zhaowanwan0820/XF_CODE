<?php
/**
 *  代发批处理脚本
 * @author 王群强<wangqunqiang@ucfgroup.com>
 */
ini_set('memory_limit', '512M');
set_time_limit(0);
require_once dirname(__FILE__).'/../../app/init.php';

use core\dao\WithdrawProxyModel;
use core\service\WithdrawProxyService;

class WithdrawProxyWorker
{

    public function run()
    {
        /**
         * 代发请求受到开关影响
         */
        $switch = intval(app_conf('WITHDRAW_PROXY_SWITCH'));
        if ($switch == 0)
        {
            return true;
        }
        $pidList = \libs\utils\Process::getPidList('withdraw_proxy_worker.sh');
        $pidCount = count($pidList) > 0 ? count($pidList) : 1;
        $pidOffset = array_search(posix_getppid(), $pidList);
        if ($pidOffset === false) {
            exit("进程启动方式错误，请用 scripts/daemonworkers/withdraw_proxy_worker.sh 启动\n");
        }

        for ($i = 0; $i < 1000; $i++)
        {
            $s = microtime(true);
            $data = WithdrawProxyModel::instance()->pop($pidCount, $pidOffset);

            //并发冲突
            if ($data === false)
            {
                continue;
            }

            //队列为空
            if (empty($data))
            {
                sleep($pidCount);
                break;
            }

            WithdrawProxyService::processRequest($data);
            $escaped = (microtime(true) - $s)/ 1000;
            \libs\utils\PaymentApi::log('WithdrawProxyWorker a single request cost:'.$escaped.'s');
        }
    }
}

$worker = new WithdrawProxyWorker();
$worker->run();
