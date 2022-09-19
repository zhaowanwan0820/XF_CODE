<?php
namespace core\dao;

use libs\utils\PaymentApi;
use NCFGroup\Commom\Library\Idworker;

class WithdrawProxyCheckModel extends BaseModel
{
    const CHECK_RESULT_OK = 1;
    const CHECK_RESULT_FAIL = 2;
    const CHECK_RESULT_P2P_ORDER_NOT_EXSIT = 3;
    const CHECK_RESULT_GATEWAY_ORDER_NOT_EXIST = 4;
    const CHECK_RESULT_STATUS_NOT_SAME = 5;
    const CHECK_RESULT_AMOUNT_NOT_EQUAL= 6;

    static $checkStatusCn = array(
        self::CHECK_RESULT_OK => '对账成功',
        self::CHECK_RESULT_FAIL => '对账失败',
        self::CHECK_RESULT_P2P_ORDER_NOT_EXSIT => '理财订单不存在',
        self::CHECK_RESULT_GATEWAY_ORDER_NOT_EXIST => '支付订单不存在',
        self::CHECK_RESULT_STATUS_NOT_SAME => '订单状态不一致',
        self::CHECK_RESULT_AMOUNT_NOT_EQUAL => '订单金额不一致',
    );

    /**
     * 根据指定日期获取对账信息
     * @param string checkDate 'YYYYmmdd' format , 不传则获取近7天的对账数据
     * @return array
     */
    public static function getRecentCheckList($checkDate = '')
    {
        $condition = " check_date = '{$checkDate}' ";
        if (empty($checkDate))
        {

            $createtime = strtotime('-7 days');
            $condition = " create_time >= {$createtime} ";
        }
        $sql = "SELECT distinct(check_date) AS check_date FROM firstp2p_withdraw_proxy_check WHERE {$condition}";
        $list = \libs\db\Db::getInstance('firstp2p', 'slave')->getAll($sql);
        $result = [];
        foreach ($list as $record)
        {
            $result[$record['check_date']] = self::getCheckCount($record['check_date']);
            $result[$record['check_date']]['date'] = $record['check_date'];
        }
        return $result;
    }


    /**
     * 统计指定date对应的对账记录总条数
     */
    public static function getCheckCount($date)
    {
        $sql = "SELECT check_status, COUNT(*) as cntNum, SUM(amount) as amtTotal,SUM(remote_amount) as remoteAmount FROM firstp2p_withdraw_proxy_check WHERE check_date = '{$date}' GROUP BY check_status";
        $countInfo = \libs\db\Db::getInstance('firstp2p', 'slave')->getAll($sql);
        $result = [
            'statistics' => [],
            'totalCnt' => 0,
            'totalAmount' => 0,
        ];
        foreach ($countInfo as $info)
        {
            if (!isset($result['statusAmt'][$info['check_status']]))
            {
                $result['statistics'][$info['check_status']] = [];
                $result['statistics'][$info['check_status']] = [
                    'count' => 0,
                    'amount' => 0,
                ];
            }
            $result['statistics'][$info['check_status']]['count'] = $info['cntNum'];
            $amount = $info['amtTotal'];
            if ($info['check_status'] == self::CHECK_RESULT_P2P_ORDER_NOT_EXSIT)
            {
                $amount = $info['remoteAmount'];
            }

            $result['statistics'][$info['check_status']]['amount'] += $amount;
            if ($info['check_status'] > 1)
            {
                $result['statistics'][2]['count'] += $info['cntNum'];
                $result['statistics'][2]['amount'] += $amount;
            }
            $result['totalCnt'] += $info['cntNum'];
            $result['totalAmount'] += $amount;
        }
        return $result;
    }

    public static function getDiffList($checkDate, $checkStatus)
    {
        $checkStatusCondition = ' = '.intval($checkStatus);
        if ($checkStatus == self::CHECK_RESULT_FAIL)
        {
            $status = array_keys(self::$checkStatusCn);
            unset($status[array_search(self::CHECK_RESULT_OK, $status)]);
            unset($status[array_search(self::CHECK_RESULT_FAIL, $status)]);
            $checkStatusCondition = ' IN ('.implode(',',$status).')';
        }
        $sql = "SELECT * FROM firstp2p_withdraw_proxy_check WHERE check_date = '{$checkDate}' AND check_status {$checkStatusCondition}";
        return \libs\db\Db::getInstance('firstp2p', 'slave')->getAll($sql);
    }
}
