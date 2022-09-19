<?php
/**
 * 导出转账数据
 * @author 王群强<wangqunqiang@ucfgroup.com>
 */
ini_set('memory_limit', '512M');
set_time_limit(0);
require_once dirname(__FILE__).'/../app/init.php';

use libs\utils\PaymentApi;
use libs\db\Db;
use libs\db\MysqlDb;

error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
329983672,6142068,鹰潭裕恒泰资产管理有限合伙企业,6666531,周艳丽,6000,红包充值,,2016-05-20 00:06:18
329983673,6142068,鹰潭裕恒泰资产管理有限合伙企业,5868380,徐美霞,10000,红包充值,,2016-05-20 00:14:03
329983682,6142068,鹰潭裕恒泰资产管理有限合伙企业,6038290,王长亮,32000,红包充值,,2016-05-20 00:37:19
329983688,6142068,鹰潭裕恒泰资产管理有限合伙企业,6292580,刘书卫,36000,红包充值,,2016-05-20 01:00:52
329983691,6142068,鹰潭裕恒泰资产管理有限合伙企业,6556711,安金高,16000,红包充值,,2016-05-20 01:16:14
329983692,6142068,鹰潭裕恒泰资产管理有限合伙企业,5099699,张宽新,10000,红包充值,,2016-05-20 01:17:13
329983699,6142068,鹰潭裕恒泰资产管理有限合伙企业,6596995,郑刚轩,8000,红包充值,,2016-05-20 01:41:19
330092147,6142068,鹰潭裕恒泰资产管理有限合伙企业,6204754,吴成威,4000,红包充值,,2016-05-20 04:56:51
330092148,6142068,鹰潭裕恒泰资产管理有限合伙企业,5933199,白守红,8000,红包充值,,2016-05-20 04:59:52
330092149,6142068,鹰潭裕恒泰资产管理有限合伙企业,5933199,白守红,4000,红包充值,,2016-05-20 05:00:45
*/


class BonusLog
{
    public $db = null;

    public $phDb = null;

    static $dataFileName = '/apps/logs/hongbao.export.';

    static $outDataFile = '/apps/logs/result/hongbao.output.%s.csv';

    static $userLogTableString = 'firstp2p_user_log_%d';

    static $columnNames = ['transferId', 'userId', 'payUserName', 'receiveUserId', 'receiveUserName', 'amount', 'logType', 'dealId', 'createDatetime'];


    public function __construct($sliceNum)
    {
        $sliceNum = intval($sliceNum);
        if ($sliceNum < 0)
        {
            exit('切片编号不正确'.PHP_EOL);
        }
        $this->sliceNum = str_pad($sliceNum, 2, '0',STR_PAD_LEFT);
        $this->db = Db::getInstance('firstp2p_moved', 'slave');
        $this->phDb = $this->getPhdb();
    }

    public function getPhdb()
    {
        $db = new MysqlDb('r-ncfph.mysql.ncfrds.com', 'ncfph_pro_r', '6734B7FE98e13613', 'ncfph');
        return $db;
    }

    public function run()
    {
        $fp = $this->loadDataFile();

        while ($row = $this->parseFpLine($fp))
        {
            $record = $this->getUesrLogRecord($row['receiveUserId'], $row['createDatetime'], $row['amount']);
            if (empty($record))
            {
                PaymentApi::log('bonusexport:转账id: '.$row['transferId'].' 无对应的红包消费记录');
                continue;
            }
            if (!isset($record['biz_token']))
            {
                PaymentApi::log('bonusexport:转账id: '.$row['transferId'].' 无对应的标的id, biz_token:'.$record['biz_token']);
                continue;
            }
            // 判断标的是否可用标的
            $bizInfo = json_decode($record['biz_token'], 1);
            if (empty($bizInfo['dealId']))
            {
                PaymentApi::log('bonusexport:转账id: '.$row['transferId'].' 无对应的标的id, biz_token:'.$record['biz_token']);
                continue;
            }

            $isP2pDeal = $this->isP2pDeal($bizInfo['dealId']);
            if (!$isP2pDeal)
            {
                PaymentApi::log('bonusexport:转账id: '.$row['transferId'].' 标的为非网贷标, dealId:'.$bizInfo['dealId']);
                continue;
            }
            PaymentApi::log('bonusexport:转账id: '.$row['transferId'].' 标的为网贷标, dealId:'.$bizInfo['dealId']);
            $row['dealId'] = $bizInfo['dealId'];
            $writeResult = $this->saveToFile($row);
            if (!$writeResult)
            {
                PaymentApi::log('bonusexport:转账id: '.$row['transferId'].'更新失败'.json_encode($row, JSON_UNESCAPED_UNICODE));
                continue;
            }
        }

        fclose($fp);
    }

    public function saveToFile($data)
    {
        $outDataFile = sprintf(self::$outDataFile, $this->sliceNum);
        $lineData = join(',', $data);
        $lineData .= PHP_EOL;
        $size = file_put_contents($outDataFile, $lineData, FILE_APPEND);
        return $size == strlen($lineData);
    }

    public function isP2pDeal($dealId)
    {
        $sql = "SELECT COUNT(*) FROM firstp2p_deal WHERE id = '{$dealId}'";
        $cnt = $this->phDb->getOne($sql);
        return $cnt == 1 ? true : false;
    }

    public function getUesrLogRecord($userId, $createDatetime, $amount)
    {
        $userLogTable = sprintf(self::$userLogTableString, $this->getTableHash($userId));
        $logTime = strtotime($createDatetime) - 28800;
        $money = bcdiv($amount, 100, 2);
        $sql = "SELECT log_info,log_user_id,money,log_time,biz_token FROM ".$userLogTable." WHERE user_id = '{$userId}' AND log_time = '{$logTime}' AND money = '{$money}'";
        $record = $this->db->getRow($sql);
        return $record;
    }


    public function getTableHash($userId)
    {
        return $userId % 64;
    }

    public function parseFpLine($fp)
    {
        $line = fgetcsv($fp);
        if (!is_array($line))
        {
            return null;
        }
        return array_combine(self::$columnNames, $line);
    }


    private function loadDataFile()
    {
        $dataFile = self::$dataFileName.$this->sliceNum;
        if (!file_exists($dataFile))
        {
            exit('切片编号'.$this->sliceNum.'对应的文件不存在'.PHP_EOL);
        }
        $fp = fopen($dataFile, 'r');
        return $fp;
    }
}

$args = ($_SERVER['argv']);
$BonusLog = new BonusLog($args[1]);
$BonusLog->run();
