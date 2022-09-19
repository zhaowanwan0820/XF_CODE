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
        $pidList = \libs\utils\Process::getPidList('withdraw_proxy_notify_worker.sh');
        $pidCount = count($pidList) > 0 ? count($pidList) : 1;
        $pidOffset = array_search(posix_getppid(), $pidList);
        if ($pidOffset === false) {
            exit("进程启动方式错误，请用 scripts/daemonworkers/withdraw_proxy_noitfy__worker.sh 启动\n");
        }

        for ($i = 0; $i < 1000; $i++)
        {
            $data = WithdrawProxyModel::instance()->popNotify($pidCount, $pidOffset);

            //并发冲突
            if ($data === false)
            {
                continue;
            }

            //队列为空
            if (empty($data)) {
                sleep($pidCount*5);
                break;
            }
            WithdrawProxyService::processNotify($data);
        }
    }
}

$worker = new WithdrawProxyWorker();
$worker->run();
