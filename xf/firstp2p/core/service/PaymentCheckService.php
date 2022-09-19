<?php
/**
 * 对账Service
 */
namespace core\service;

use libs\utils\PaymentApi;
use libs\utils\Logger;

class PaymentCheckService extends BaseService
{

    /**
     * 获取全站最大的用户Id
     */
    public function getMaxUserId()
    {
        $sql = 'SELECT max(id) maxid FROM firstp2p_user';
        $ret = $GLOBALS['db']->get_slave()->getRow($sql);
        return intval($ret['maxid']);
    }

    /**
     * 获取批量用户余额
     */
    public function getUserMoney($where)
    {
        $userMoney = array();
        $sql = "SELECT id, group_id, money, lock_money FROM firstp2p_user WHERE $where";
        $ret = $GLOBALS['db']->get_slave()->getAll($sql);
        foreach ($ret as $item) {
            $userMoney[$item['id']]['sum'] = bcadd($item['money'], $item['lock_money'], 2);
            $userMoney[$item['id']]['money'] = $item['money'];
            $userMoney[$item['id']]['lock_money'] = $item['lock_money'];
            $userMoney[$item['id']]['group_id'] = $item['group_id'];
        }

        return $userMoney;
    }

    /**
     * 获取批量先锋支付余额
     */
    public function getUcfpayMoney(array $userIds)
    {
        if (empty($userIds)) {
            return array();
        }

        $ucfpayMoney = array();
        $ret = PaymentApi::instance()->request('searchBalances', array('userIds' => implode(',', $userIds)));
        if (empty($ret['result'])) {
            return array();
        }

        foreach ($ret['result'] as $item) {
            $ucfpayMoney[$item['userId']] = bcdiv(bcadd($item['available'], $item['freeze'], 2), 100, 2);
        }

        return $ucfpayMoney;
    }


    /**
     * 获取批量存管用户余额
     */
    public function getSupervisionMoney(array $userIds) {
        if (empty($userIds)) {
            return array();
        }
        $supervisionMoneys = array();
        $ret = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_SUPERVISION)->request('memberBatchBalanceSearch', array('userIds' => implode(',', $userIds)));
        if (empty($ret['usersBalance'])) {
            return array();
        }
        foreach ($ret['usersBalance'] as $item) {
            $supervisionMoneys[$item['userId']] = $item['availableBalance'];
        }
        return $supervisionMoneys;
    }

    /**
     * 获取基金申购中的金额
     */
    private function _getFundMoney($userId)
    {
        $sql = "SELECT sum(money) total from firstp2p_fund_money_log where user_id='{$userId}' and event in (1,2) and status=1 group by out_order_id having count(*)=1;";
        $ret = $GLOBALS['db']->get_slave()->getAll($sql);
        $sum = 0;
        foreach ($ret as $item)
        {
            $sum += $item['total'];
        }
        return $sum;
    }

    /**
     * 获取小额转账验证金额
     */
    private function _getVerifyInfo($userId)
    {
        $params = array(
            'merchantId' => $GLOBALS['sys_config']['XFZF_PAY_MERCHANT'],
            'userId' => $userId,
        );
        $query_string = \libs\utils\Aes::buildString($params);
        $signature = md5($query_string."&key=".$GLOBALS['sys_config']['XFZF_SEC_KEY']);
        $params['sign'] = $signature;

        $api = $GLOBALS['sys_config']['XFZF_BANKCARD_QUERY'];
        $aesData = \libs\utils\Aes::encode($query_string."&sign=".$signature, base64_decode($GLOBALS['sys_config']['XFZF_AES_KEY']));
        $retSrc = \libs\utils\Curl::post($api, array('data'=>$aesData));
        $ret = json_decode($retSrc, true);
        $rs = \libs\utils\Aes::decode($ret['data'], base64_decode($GLOBALS['sys_config']['XFZF_AES_KEY']));
        parse_str($rs, $result);

        //没做过小额转账
        if (!isset($result['merchantNo'])) {
            return false;
        }

        //小额转账正常
        $orderSn = $result['merchantNo'];
        $ret = $GLOBALS['db']->get_slave()->getOne("SELECT id FROM firstp2p_payment_notice WHERE notice_sn='$orderSn'");
        if (!empty($ret)) {
            return false;
        }

        return $result;
    }

    /**
     * 获取用户备注
     */
    public function getUserNote($userId)
    {
        $noteList = array(
            //超出充值
            13536 => array('note' => '超出充值', 'amount' => 100),
            8186 => array('note' => '超出充值', 'amount' => 3000),
            14410 => array('note' => '超出充值', 'amount' => 100),
            11835 => array('note' => '超出充值', 'amount' => 1000),
            14568 => array('note' => '超出充值', 'amount' => 15000),
            13327 => array('note' => '超出充值', 'amount' => 40001),
            14074 => array('note' => '超出充值', 'amount' => 20000),
            14474 => array('note' => '超出充值', 'amount' => 50000),
            14319 => array('note' => '超出充值', 'amount' => 200000),
            //回款还款误差
            20887 => array('note' => '还款误差', 'amount' => 0.24),
            11751 => array('note' => '还款误差', 'amount' => 2.86),
            16438 => array('note' => '还款误差', 'amount' => 0.27),
            14705 => array('note' => '还款误差', 'amount' => 0.01),
            656 => array('note' => '还款误差', 'amount' => -0.01),
            1816 => array('note' => '还款误差', 'amount' => 0.01),
            25177 => array('note' => '还款误差', 'amount' => 0.15),
            12863 => array('note' => '还款误差', 'amount' => 0.02),
            13078 => array('note' => '还款误差', 'amount' => 0.03),
            9632 => array('note' => '还款误差', 'amount' => 0.02),
            1539 => array('note' => '还款误差', 'amount' => 0.01),
            29665 => array('note' => '还款误差', 'amount' => -0.72),
            6689 => array('note' => '还款误差', 'amount' => 0.04),
            14071 => array('note' => '还款误差', 'amount' => -0.01),
            710 => array('note' => '还款误差', 'amount' => -0.01),
            2982 => array('note' => '还款误差', 'amount' => -0.01),
            2988 => array('note' => '还款误差', 'amount' => 0.23),
            734 => array('note' => '还款误差', 'amount' => 0),
            17343 => array('note' => '还款误差', 'amount' => -3.24),
            20420 => array('note' => '还款误差', 'amount' => -0.04),
            3 => array('note' => '还款误差', 'amount' => 0.01),
            739 => array('note' => '还款误差', 'amount' => -0.01),
            12512 => array('note' => '还款误差', 'amount' => -0.24),
            33468 => array('note' => '还款误差', 'amount' => -0.24),
            1915 => array('note' => '还款误差', 'amount' => 0.06),
            14111 => array('note' => '还款误差', 'amount' => -0.04),
            14995 => array('note' => '还款误差', 'amount' => -0.01),
            10504 => array('note' => '还款误差', 'amount' => -0.01),
            6337 => array('note' => '还款误差', 'amount' => 0.78),
            14281 => array('note' => '还款误差', 'amount' => -0.46),
            1091 => array('note' => '还款误差', 'amount' => -0.04),
            12818 => array('note' => '还款误差', 'amount' => 0.01),
            5411 => array('note' => '还款误差', 'amount' => -0.02),
            923 => array('note' => '还款误差', 'amount' => -0.01),
            36472 => array('note' => '还款误差', 'amount' => 0.03),
            645 => array('note' => '还款误差', 'amount' => -1.26),
            646 => array('note' => '还款误差', 'amount' => 27.42),
            662 => array('note' => '还款误差', 'amount' => -0.01),
            21290 => array('note' => '还款误差', 'amount' => 0.01),
            35106 => array('note' => '还款误差', 'amount' => -0.18),
            669 => array('note' => '还款误差', 'amount' => -0.01),
            3709 => array('note' => '还款误差', 'amount' => -0.01),
            534 => array('note' => '还款误差', 'amount' => 0.19),
            13388 => array('note' => '还款误差', 'amount' => -1.14),
            9662 => array('note' => '还款误差', 'amount' => 0.05),
            12896 => array('note' => '还款误差', 'amount' => 0.02),
            18811 => array('note' => '还款误差', 'amount' => 3.06),
            1139 => array('note' => '还款误差', 'amount' => 0.25),
            9812 => array('note' => '还款误差', 'amount' => -1.4),
            19340 => array('note' => '还款误差', 'amount' => 0.12),
            6136 => array('note' => '还款误差', 'amount' => 0.01),
            11694 => array('note' => '还款误差', 'amount' => 1.12),
            14928 => array('note' => '还款误差', 'amount' => -0.04),
            4570 => array('note' => '还款误差', 'amount' => 0.03),
            296 => array('note' => '还款误差', 'amount' => 0),
            9553 => array('note' => '还款误差', 'amount' => -0.05),
            595 => array('note' => '还款误差', 'amount' => -0.01),
            12202 => array('note' => '还款误差', 'amount' => 0.01),
            13146 => array('note' => '还款误差', 'amount' => 0.03),
            4707 => array('note' => '还款误差', 'amount' => -0.03),
            5217 => array('note' => '还款误差', 'amount' => 0.02),
            33320 => array('note' => '还款误差', 'amount' => -1.02),
            14252 => array('note' => '还款误差', 'amount' => -0.3),
            13743 => array('note' => '还款误差', 'amount' => -0.18),
            33770 => array('note' => '还款误差', 'amount' => -0.02),
            17204 => array('note' => '还款误差', 'amount' => -0.06),
            12390 => array('note' => '还款误差', 'amount' => -0.24),
            766 => array('note' => '还款误差', 'amount' => 11.1),
            28813 => array('note' => '还款误差', 'amount' => 0.15),
            767 => array('note' => '还款误差', 'amount' => 11.1),
            1797 => array('note' => '还款误差', 'amount' => -0.01),
            768 => array('note' => '还款误差', 'amount' => 6.65),
            11172 => array('note' => '还款误差', 'amount' => -0.01),
            912 => array('note' => '还款误差', 'amount' => -0.01),
        );

        $note = array();

        //已知列表
        if (isset($noteList[$userId])) {
            $note[] = $noteList[$userId]['note'].$noteList[$userId]['amount'];
        }

        //基金申购
        $fund = $this->_getFundMoney($userId);
        if ($fund != 0) {
            $note[] = '基金申购'.$fund;
        }

        //小额转账
        /*
        $result = $this->_getVerifyInfo($userId);
        if ($result) {
            $note[] = '小额转账异常'.round($result['amount'] / 100, 2);
        }
        */

        //历史数据
        //$history = $this->_getHistoryInfo($userId);

        return array('note' => implode(';', $note), 'fund' => $fund);
    }

    private function _getHistoryInfo($userId) {
        //$db = new \libs\db\MysqlDb(app_conf('DB_ITIL_HOST').":".app_conf('DB_ITIL_PORT'), app_conf('DB_ITIL_USER'),app_conf('DB_ITIL_PWD'),app_conf('DB_ITIL_NAME'),'utf8');
        $db = \libs\db\MysqlDb::getInstance('itil');
        $sql = "SELECT * FROM payment_user_check WHERE user_id = '{$userId}' ORDER BY id DESC LIMIT 1";
        return $db->getRow($sql);
    }

    public function batchInsert($exceptionUsers) {
        //$db = new \libs\db\MysqlDb(app_conf('DB_ITIL_HOST').":".app_conf('DB_ITIL_PORT'), app_conf('DB_ITIL_USER'),app_conf('DB_ITIL_PWD'),app_conf('DB_ITIL_NAME'),'utf8');
        $db = \libs\db\MysqlDb::getInstance('itil');
        if (is_array($exceptionUsers)) {
            foreach ($exceptionUsers as $userId => $exceptInfo) {
                $rowData['note'] = $exceptInfo['note']['note'];
                $rowData['user_id'] = $userId;
                $rowData['p2p'] = bcmul($exceptInfo['p2p'], '100', 2);
                $rowData['ucfpay'] = bcmul($exceptInfo['ucfpay'], '100', 2);
                $rowData['diff'] = bcmul($exceptInfo['diff'], '100', 2);
                $rowData['create_date'] = date('Y-m-d');
                $rowData['create_time'] = time();
                $db->insert('payment_user_check', $rowData);
            }
        }
    }

    /**
     * 查询下单模式转账充值订单数据
     * @param integer $userId 用户id
     * @param array $searchStatus 查询状态数组
     * @param integer $page 页码
     * @return array
     *      pageCnt integer
     *      pageList array
     */
    public function queryOfflineOrders($searchParams = [])
    {
        $response = [
            'pageCnt' => 0,
            'pageList' => [],
        ];

        extract($searchParams);
        $requestParams = [];
        if (empty($userId))
        {
            return $response;
        }
        $requestParams['userId'] = $userId;

        if (empty($startDate))
        {
            return $response;
        }
        $requestParams['startDate'] = $startDate;

        if (empty($endDate))
        {
            return $response;
        }
        $requestParams['endDate'] = $endDate;

        if (empty($pageNo))
        {
            $pageNo = 1;
        }
        $requestParams['pageNo'] = $pageNo;

        if (empty($pageSize))
        {
            $pageSize = 30;
        }
        $requestParams['pageSize'] = $pageSize;

        if (!empty($bankCardNo))
        {
            $requestParams['bankCardNo'] = $bankCardNo;
        }

        if (!empty($busType))
        {
            $requestParams['busType'] = $busType;
        }

        if (empty($orderStatus))
        {
            $requestParams['orderStatus'] = '';
        } else {
            $requestParams['orderStatus'] = $orderStatus;
        }


        $result = PaymentApi::instance()->request('queryOfflineOrders', $requestParams);
        if (empty($result['respCode']) || $result['respCode'] != '00')
        {
            return $response;
        }
        foreach ($result['recordList'] as $k => $row)
        {
            $response['pageList'][] = $row;
        }
        $response['pageCnt'] = count($response['pageList']);
        return $response;
    }


    /**
     * 查询 网信平台收款账户到账情况
     * 1、时间（上账时间和到账时间）和商户订单号至少送一个
     * 2、商户订单号不送的时候，上账时间和到账时间至少送一个（一个是一对，包括开始时间和结束时间）
     * 3、一组时间间隔不能超过10天
     * 4、开始时间不能大于结束时间
     * @param array searchParams 检索条件
     *      outOrderId string 用商户订单号检索， 如果以此状态查询，则时间范围可非必填
     *      transStartTime string 到账时间(开始时间)，格式YYYYmmddHHiiss
     *      transEndTime string 到账时间(结束时间)，格式YYYYmmddHHiiss
     *      payAccountName string 银行账户名称
     *      payAccountNo string 银行账户号
     *      amount string 金额单位分
     *      accountNo string 备付金账号
     *      status string 匹配状态 ready 待匹配 success 匹配成功
     *      account string 备付金账号
     *      accountStartDate string 上账时间(开始时间)，格式YYYYmmddHHiiss
     *      accountEndDate string 上账时间(结束时间)，格式YYYYmmddHHiiss
     *      pageNo string 页码
     *      pageSize string 每页记录数
     * @return array
     *      pageCnt integer
     *      pageList array
     */
    public function queryAccountRecords($searchParams)
    {
        $response = [
            'pageCnt' => 0,
            'pageList' => [],
        ];

        if (!isset($searchParams['outOrderId'])) {
            // 校验到账时间
            if (empty($searchParams['transStartTime']) && empty($searchParams['transEndTime'])) {
                PaymentApi::log(sprintf('%s，params：%s，transTime_Is_Empty', __METHOD__, json_encode($searchParams)), Logger::ERR);
                return $response;
            }
            // 校验到账时间是否合法
            if (!empty($searchParams['transStartTime']) && !empty($searchParams['transEndTime'])
                && strtotime($searchParams['transStartTime']) > strtotime($searchParams['transEndTime'])) {
                PaymentApi::log(sprintf('%s，params：%s，transTime_Is_Illegal', __METHOD__, json_encode($searchParams)), Logger::ERR);
                return $response;
            }
            // 校验上账时间
            if (empty($searchParams['accountStartDate']) && empty($searchParams['accountEndDate'])) {
                PaymentApi::log(sprintf('%s，params：%s，accountDate_Is_Empty', __METHOD__, json_encode($searchParams)), Logger::ERR);
                return $response;
            }
            // 校验上账时间是否合法
            if (!empty($searchParams['accountStartDate']) && !empty($searchParams['accountEndDate'])
                && strtotime($searchParams['transStartTime']) > strtotime($searchParams['transEndTime'])) {
                PaymentApi::log(sprintf('%s，params：%s，accountDate_Is_Illegal', __METHOD__, json_encode($searchParams)), Logger::ERR);
                return $response;
            }
        }

        // 页号
        $searchParams['pageNo'] = max(1, (int)$searchParams['pageNo']);
        // 每页数量
        $searchParams['pageSize'] = empty($searchParams['pageSize']) ? 30 : (int)$searchParams['pageSize'];

        $result = PaymentApi::instance()->request('queryAccountRecords', $searchParams);
        if (empty($result['respCode']) || $result['respCode'] != '00')
        {
            return $response;
        }
        foreach ($result['recordList'] as $k => $row)
        {
            $response['pageList'][] = $row;
        }
        $response['pageCnt'] = count($response['pageList']);
        return $response;
    }

    /**
     * 查询最近几日大额充值订单
     */
    public function queryLastDaysLargeOrders($userId, $lastDays = 7, $orderStatus = '') {
        $params['userId'] = $userId;
        $endDate = time();
        $startDate = strtotime('-' . $lastDays . ' days', $endDate);
        $params['startDate'] = $startDate;
        $params['endDate'] = $endDate;
        $params['pageNo'] = 1;
        $params['pageSize'] = 1000;
        if (!empty($orderStatus)) {
            $params['orderStatus'] = $orderStatus;
        }
        $params['busType'] = 'COMMON_LARGE'; //COMMON_LARGE：大额转账充值（下单模式） OFFLINE：商户后台转账充值 不传：全部
        $result = $this->queryOfflineOrders($params);
        return !empty($result['pageList']) ? $result['pageList'] : [];
    }
}
