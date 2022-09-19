<?php
/**
 * @desc 对于投资失败的进行重试
 * ----------------------------------------------------------------------------
 * 监控每1分钟运行一次
 * ----------------------------------------------------------------------------
 *
 * ----------------------------------------------------------------------------
 */
set_time_limit(0);
ini_set('memory_limit', '1024M');

require_once dirname(__FILE__).'/../../app/init.php';

use libs\utils\Rpc;
use NCFGroup\Protos\Duotou\RequestCommon;
use core\service\candy\CandyActivityService;

class xinLi
{
    public function run()
    {
        $candyActivityService = new CandyActivityService();
        $pageNum = 1;
        $request = new RequestCommon();
        $rpc = new Rpc('duotouRpc');

        $opts = getopt('t:u:');
        $startTime = isset($opts['t']) ? strtotime(trim($opts['t'])) : strtotime(date('Y-m-d').' -1 day');
        $userId = isset($opts['u']) && intval($opts['u']) ? intval($opts['u']) : 0;
        while (true) {
            $request->setVars(array(
                'pageNum' => $pageNum,
                'status' => '1,2,3,4,5',
                'startTime' => $startTime,
                'endTime' => $startTime + 86400,
                'userId' => $userId,
                'pageSize' => 50,
                ));

            $response = $rpc->go('NCFGroup\Duotou\Services\DealLoan', 'getDealLoanList', $request, 2, 3);

            if (empty($response)) {
                \libs\utils\Alarm::push('duotou_candy', '多投RPC请求失败');
                continue;
            }

            $list = $response['data']['data'];
            $totalPage = $response['data']['totalPage'];
            if (!empty($list)) {
                foreach ($list as $value) {
                    try {
                        $candyActivityService->activityCreateByType(CandyActivityService::SOURCE_TYPE_DT, 'dt_'.$value['token'], $value['userId'], $value['money'], $value['lockPeriod']);
                    } catch (Exception $e) {
                        \libs\utils\Alarm::push('duotou_candy', $e->getMessage(), json_encode($value));
                    }
                }
            }

            //总页数等0，或者当前页数等于总页数跳出循环
            if (0 == $totalPage || $totalPage == $pageNum) {
                break;
            }

            ++$pageNum;
        }
    }
}
echo 'begin:'.date('Y-m-d H:i:s')."\n";
$c = new xinLi();
$c->run();
echo 'end:'.date('Y-m-d H:i:s')."\n";
