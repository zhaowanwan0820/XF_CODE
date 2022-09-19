<?php
/**
 *  网贷 农担结息返利补贴 还代偿款监控
 * @author 王群强<wangqunqiang@ucfgroup.com>
 */
ini_set('memory_limit', '512M');
set_time_limit(0);
require_once dirname(__FILE__).'/../app/init.php';

use core\dao\NongdanModel;
use core\service\NongdanService;

class NongdanWorker
{

    public function run()
    {
        $pidList = \libs\utils\Process::getPidList('nongdan_worker.sh');
        $pidCount = count($pidList) > 0 ? count($pidList) : 1;
        $pidOffset = array_search(posix_getppid(), $pidList);
        if ($pidOffset === false) {
            exit("进程启动方式错误，请用 nongdan_worker.sh 启动\n");
        }

        for ($i = 0; $i < 1000; $i++)
        {
            $data = NongdanModel::instance()->pop($pidCount, $pidOffset);

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

            NongdanService::processRequest($data);
        }
        exit;
    }
}

$nongdanWorker = new NongdanWorker();
$nongdanWorker->run();
