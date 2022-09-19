<?php
/**
 * 清洗资金记录
 * @author 王群强<wangqunqiang@ucfgroup.com>
 */
ini_set('memory_limit', '1024M');
set_time_limit(0);
require_once dirname(__FILE__).'/../app/init.php';

use core\service\WashUserLogService;
use libs\db\MysqlDb;
use NCFGroup\Common\Library\Idworker;

error_reporting(E_ALL);
ini_set('display_errors', 1);

class WashUserLog
{
    public function __construct($partition, $db = 'backup', $start = 0)
    {
        $this->partition = $partition;
        $this->db = $db;
        $this->start = $start;
    }

    /**
     * 数据遍历逻辑
     */
    public function run()
    {
        $service = new WashUserLogService($this->partition, $this->db, $this->start, 'cli');
        if (empty($service->init))
        {
            die('无效的启动信息'.json_encode($service->init));
        }
        // 设置各种查询限制约束
        $service->allowUserLogInfoCliMod = [
            '招标成功' => '1|放款',
            '投资放款' => '2|投资',
            '平台手续费' => '3|平台手续',
            '分期咨询费' => '5|交易手续费',
            '咨询费' => '5|交易手续费',
            '担保费' => '5|交易手续费',
            '还本'  => '8|赎回本金',
            '提前还款本金'  => '8|赎回本金',
            '付息'  => '9|赎回利息',
            '提前还款利息'  => '9|赎回利息',
            '提前还款补偿金'  => '9|赎回利息',
            '偿还本息'  => '18|还款本金',
            '提前还款'  => '18|还款本金',
            // 根据规则临时生成的loginfo
            '代偿金'    => '27|代偿金',
        ];
        $logInfo = array_keys($service->allowUserLogInfoCliMod);
        array_pop($logInfo);
        $service->setQueryLogInfo($logInfo);
        $service->queryDealType = WashUserLogService::DEAL_TYPE_ALL;
        $whiteUser = $service->ncfph->getCol("select user_id from firstp2p_deal_agency where type in (1,6)");
        $service->setUserWhiteList($whiteUser);
        //执行主进程
        $records = [];
        do {
            $records = $service->getRecords();
            if (empty($records) && $service->init['pos'] >= $service->init['end'])
            {
                break;
            }
            foreach ($records as $record)
            {
                $data = $service->parseUserLog($record);
                if ($data === true || $data === false)
                {
                    //echo '资金记录无效'.json_encode($record);
                    continue;
                }
                $service->addLog($data);
            }

        } while (true);
    }

}

$args = ($_SERVER['argv']);
if (count($args) != 4)
{
    echo '参数错误,需要指定表分区 以及开始id和结束id';
    exit;
}

$washer = new WashUserLog($args[1], $args[2], $args[3]);
$washer->run();
