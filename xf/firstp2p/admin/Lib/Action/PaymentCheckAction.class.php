<?php

use libs\utils\PaymentApi;
use core\service\YeepayPaymentService;
use core\dao\PaymentNoticeModel;

ini_set('memory_limit', '2048M');

class PaymentCheckAction extends CommonAction
{

    public function index()
    {
        $this->display();
    }

    /**
     * 用户是否可信
     */
    public function credible()
    {
        $idsArray = $this->_matchUserIds($_REQUEST['ids']);

        require_once APP_ROOT_PATH.'/core/service/UserCreditService.php';
        $service = new core\service\UserCreditService();

        $result = array();
        foreach ($idsArray as $id)
        {
            $result[$id] = $service->isCredible($id);
        }

        $this->assign('result', $result);

        $this->display();
    }

    /**
     * 易宝批量用户绑定银行卡查询
     */
    public function userBindcardQuery()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ids = $this->_matchUserIds($_POST['ids']);
            return header('location:?m=PaymentCheck&a=userBindcardQuery&ids='.implode(',', $ids));
        }

        $idsArray = explode(',', $_GET['ids']);
        $data = [];
        $userInfo = [];
        $yeepayService = new YeepayPaymentService();
        foreach ($idsArray as $id)
        {
            $result = $yeepayService->bankCardAuthBindList($id);
            if (isset($result['respCode']) && $result['respCode'] === '00')
            {
                $data[$id] = $result['data']['cardlist'];
            }
            if (!isset($userInfo[$id]))
            {
                $userInfo[$id] = MI('User')->getById($id);
                $userInfo[$id]['sump2p'] = bcadd($userInfo[$id]['money'], $userInfo[$id]['lock_money'],2);
            }
        }

        $this->assign('result', $data);
        $this->assign('userInfo', $userInfo);
        $this->display();
    }


    public function yeepayOrder()
    {
        $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        $start = !empty($_REQUEST['start']) ? $_REQUEST['start'] : date('Ymd', time() - 86400 * 7);
        $end = !empty($_REQUEST['end']) ? $_REQUEST['end'] : date('Ymd');
        $startTime = strtotime($start) - date('Z');
        $endTime = strtotime($end) + 86400 - date('Z');

        $userInfo = MI('User')->getById($id);
        $orders = array();
        $orders = array_merge($orders, $this->_getChargeOrders($id, $startTime, $endTime, PaymentNoticeModel::PAYMENT_YEEPAY));
        $this->assign('startTime', $startTime);
        $this->assign('endTime', $endTime);
        $this->assign('userInfo', $userInfo);
        $this->assign('orders', $orders);
        $this->display();
    }


    /**
     * 批量用户余额对账表单
     */
    public function userBalance()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ids = $this->_matchUserIds($_POST['ids']);
            return header('location:?m=PaymentCheck&a=userBalance&ids='.implode(',', $ids));
        }

        $idsArray = explode(',', $_GET['ids']);

        $data = array();
        for ($i = 0; $i < count($idsArray); $i += 1000)
        {
            $userIds = array_slice($idsArray, $i, 1000);
            $sql = 'SELECT id, user_name, real_name, money, lock_money FROM firstp2p_user WHERE id IN ('.implode(',', $userIds).')';
            $ret = $GLOBALS['db']->get_slave()->getAll($sql);
            foreach ($ret as $item) {
                $data[$item['id']]['p2p'] = $item;
            }

            $ret = PaymentApi::instance()->request('searchBalances', array('userIds' => implode(',', $userIds)));
            foreach ($ret['result'] as $item) {
                $data[$item['userId']]['ucfpay'] = $item;
            }
        }

        $result = array();
        foreach ($data as $id => $item)
        {
            $item['p2p']['sum'] = bcadd($item['p2p']['money'], $item['p2p']['lock_money'], 2);
            $item['ucfpay']['sum'] = bcdiv($item['ucfpay']['available'] + $item['ucfpay']['freeze'], 100, 2);

            $result[$item['p2p']['sum'] === $item['ucfpay']['sum'] ? '余额相等用户' : '余额不相等用户'][$id] = $item;
        }
        ksort($result);

        $this->assign('result', $result);

        $this->display();
    }

    /**
     * 用户订单对账
     */
    public function userOrder()
    {
        $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        $start = !empty($_REQUEST['start']) ? $_REQUEST['start'] : date('Ymd', time() - 86400 * 7);
        $end = !empty($_REQUEST['end']) ? $_REQUEST['end'] : date('Ymd');
        $startTime = strtotime($start) - date('Z');
        $endTime = strtotime($end) + 86400 - date('Z');

        $userInfo = M('User')->getById($id);

        $orders = array();
        $orders = array_merge($orders, $this->_getTransferOrders($id, $startTime, $endTime));
        $orders = array_merge($orders, $this->_getChargeOrders($id, $startTime, $endTime));
        $orders = array_merge($orders, $this->_getWithdrawOrders($id, $startTime, $endTime));
        $orders = array_merge($orders, $this->_getOfflineOrders($id, $startTime, $endTime));

        function ordersSort($a, $b) { return $a['time'] < $b['time']; }
        usort($orders, ordersSort);

        $this->assign('startTime', $startTime);
        $this->assign('endTime', $endTime);
        $this->assign('userInfo', $userInfo);
        $this->assign('orders', $orders);

        $this->display();
    }

    /**
     * 按时间订单对账
     */
    public function termOrders($result = array()) {
        ini_set('memory_limit', '1024M');
        set_time_limit(0);
        $term = isset($_REQUEST['term']) ? trim($_REQUEST['term']) : '';
        $type = isset($_REQUEST['type']) ? trim($_REQUEST['type']) : '';
        $btype = isset($_REQUEST['btype']) ? trim($_REQUEST['btype']) : '';
        if (empty($term) || !in_array($btype, array('10','17','14'))) {
            $this->error('参数不正确');
        }
        $bTypeCn = '';
        if ($btype == '10') {
            $bTypeCn = '充值';
            $exceptionOrders = $this->_getTermChargeOrders($term);
        }
        else if ($btype == '14') {
            $bTypeCn = '提现';
            $exceptionOrders = $this->_getTermWithdrawOrders($term);
        }
        $total = '0.00';
        $payTotal = '0.00';
        foreach ($exceptionOrders as $item) {
            $total = bcadd($total, bcdiv($item['amount'], 100, 2), 2);
            $payTotal= bcadd($payTotal, bcdiv($item['payAmount'], 100, 2), 2);
        }
        $this->assign('orders', $exceptionOrders);
        $this->assign('term', $term.$bTypeCn);
        $this->assign('total', number_format($total,2));
        $this->assign('payTotal', number_format($payTotal,2));
        $this->display();
    }

    private function _getTermWithdrawOrders($term) {
        $result = array();
        $pageNumber = 1;
        $termUTCBegin = strtotime($term) - 28800;
        $termUTCEnd = $termUTCBegin + 86400;
        $sql = "SELECT id,money,withdraw_status,withdraw_time FROM firstp2p_user_carry WHERE update_time between '{$termUTCBegin}' AND '{$termUTCEnd}'";
        $p2pOrderInfo = $GLOBALS['db']->getAll($sql);
        do {
            $params = array(
                'businessType' => '14',
                'pageSize' => '1000',
                'pageNumber' => $pageNumber ++,
                'searchDate' => $term,
            );
            $response = PaymentApi::instance()->request('searchtrades', $params);
            if ($response['status'] != '00') {
                $this->error($response['respMsg']);
                exit;
            }
            $payStatus = array( '00' => 'S', '01' => 'F', '02' => 'I', '03' => 'F',);
            foreach ($response['listP2pSearchAmountInOutDetailResult'] as $item) {
                unset($item['curType']);
                unset($item['businessType']);
                $k = $item['outOrderId'];
                $item['orderStatus'] = $payStatus[$item['orderStatus']];
                $result[$k] = $item;
            }
        } while ($response['isEndPage'] != 1);
        $exceptionOrders = array();
        $p2pOrderStatus = array(
            '1' => 'S',
            '3' => 'I',
            '2' => 'F',
            '0' => 'U',
        );
        $orderStatusCn = array(
            'S' => '成功',
            'I' => '处理中',
            'F' => '失败',
            'U' => '未处理',
        );
        $p2pOrders = array();
        foreach ($p2pOrderInfo as $k => $item) {
            $item['withdraw_status'] = $p2pOrderStatus[$item['withdraw_status']];
            $item['amount'] = round($item['money'] * 100);
            $item['gmtFinished'] = date('YmdHis', $item['withdraw_time'] + 28800);
            unset($item['money']);
            $p2pOrders[$item['id']] = $item;
        }

        foreach ($p2pOrders as $outOrderId => $item) {
            if (isset($result[$outOrderId])) {
                $_payOrderItem = $result[$outOrderId];
                if ($_payOrderItem['orderStatus'] != $item['withdraw_status']
                    || $_payOrderItem['amount'] != $item['amount']) {
                    $exceptionOrders[$outOrderId] = array(
                        'gmtFinished' => $item['gmtFinished'],
                        'outOrderId' => $item['id'],
                        'amount' => bcdiv($item['amount'], 100, 2),
                        'payAmount' => $_payOrderItem['amount'],
                        'status' => $orderStatusCn[$item['withdraw_status']],
                        'payStatus' => $orderStatusCn[$_payOrderItem['orderStatus']],
                        'msg' => '<span style="color:red;">订单状态或者金额异常</span>',
                        'paySuccessTime' => $result['gmt_finished']
                    );
                }
                unset($result[$outOrderId]);
            } //end ifisset
            else {
                if ($item['withdraw_status'] === 'S' || $item['withdraw_status'] === 'I') {
                    $exceptionOrders[$outOrderId] = array(
                        'gmtFinished' => $item['gmtFinished'],
                        'outOrderId' => $item['id'],
                        'amount' => bcdiv($item['amount'], 100, 2),
                        'payAmount' => '0.00',
                        'status' => $orderStatusCn[$item['withdraw_status']],
                        'payStatus' => '不存在',
                        'msg' => '<span style="color:red;">支付订单不存在</span>',
                        'paySuccessTime' => $result['gmt_finished']
                    );
                }
            }
        } //end foreach
        foreach ($result as $outOrderId => $item) {
            $exceptionOrders[$outOrderId] = array(
                'gmtFinished' => '',
                'outOrderId' => $outOrderId,
                'amount' => '0.00',
                'payAmount' => bcdiv($item['amount'], 100, 2),
                'status' => '不存在',
                'payStatus' => $orderStatusCn[$item['orderStatus']],
                'msg' => '<span style="color:red;">P2P订单不存在</span>',
                'paySuccessTime' => $item['gmt_finished']
            );
        }
        return $exceptionOrders;
    }

    private function _getTermChargeOrders($term) {
        $result = array();
        $pageNumber = 1;
        $termUTCBegin = strtotime($term) -28800;
        $termUTCEnd = $termUTCBegin + 86400;
        $sql = "SELECT notice_sn,money,is_paid,pay_time,platform FROM firstp2p_payment_notice WHERE pay_time between '{$termUTCBegin}' AND '{$termUTCEnd}' AND is_paid  = 1";
        $p2pOrderInfo = $GLOBALS['db']->getAll($sql);
        do {
            $params = array(
                'businessType' => '10',
                'pageSize' => '1000',
                'pageNumber' => $pageNumber ++,
                'searchDate' => $term,
            );
            // 新h5充值
            if ($p2pOrderInfo['platform'] == PaymentNoticeModel::PLATFORM_H5_NEW_CHARGE)
            {
                $params['businessType'] = 'new_recharge';
            }
            $response = PaymentApi::instance()->request('searchtrades', $params);
            if ($response['status'] != '00') {
                $this->error($response['respMsg']);
                exit;
            }
            $payOrderStatus = array(
                '00' => 'S',
                '01' => 'F',
                '02' => 'I',
                '03' => 'F',
            );
            foreach ($response['listP2pSearchAmountInOutDetailResult'] as $item) {
                unset($item['curType']);
                unset($item['businessType']);
                $item['orderStatus'] = $payOrderStatus[$item['orderStatus']];
                if ($item['orderStatus'] != 'S') {
                    continue;
                }
                $k = $item['outOrderId'];
                $result[$k] = $item;
            }
        } while ($response['isEndPage'] != 1);
        $exceptionOrders = array();
        $p2pOrderStatus = array(
            '1' => 'S',
            '2' => 'I',
            '3' => 'F',
            '0' => 'U',
        );
        $orderStatusCn = array(
            'S' => '成功',
            'I' => '处理中',
            'F' => '失败',
            'U' => '未处理',
        );
        $p2pOrders = array();
        foreach ($p2pOrderInfo as $k => $item) {
            $item['amount'] = round($item['money'] * 100);
            $item['is_paid'] = $p2pOrderStatus[$item['is_paid']];
            $item['gmtFinished'] = date('YmdHis', $item['pay_time'] + 28800);
            unset($item['money']);
            unset($item['id']);
            $p2pOrders[$item['notice_sn']] = $item;
        }

        foreach ($p2pOrders as $outOrderId => $item) {
            if (isset($result[$outOrderId])) {
                $_payOrderItem = $result[$outOrderId];
                if ($_payOrderItem['orderStatus'] != $item['is_paid']
                    || $_payOrderItem['amount'] != $item['amount']) {
                    $exceptionOrders[$outOrderId] = array(
                        'gmtFinished' => $item['gmtFinished'],
                        'outOrderId' => $item['notice_sn'],
                        'amount' => bcdiv($item['amount'], 100, 2),
                        'payAmount' => bcdiv($_payOrderItem['amount'], 100, 2),
                        'status' => $orderStatusCn[$item['is_paid']],
                        'payStatus' => $orderStatusCn[$_payOrderItem['orderStatus']],
                        'msg' => '<span style="color:red;">订单状态或者金额异常</span>',
                        'paySuccessTime' => $result['gmt_finished']
                    );
                }
                unset($result[$outOrderId]);
            } //end ifisset
            else {
                if ($item['is_paid'] === 'S' || $item['is_paid'] === 'I') {
                    $exceptionOrders[$outOrderId] = array(
                        'gmtFinished' => $item['gmtFinished'],
                        'outOrderId' => $item['notice_sn'],
                        'amount' => bcdiv($item['amount'], 100, 2),
                        'payAmount' => '0.00',
                        'status' => $orderStatusCn[$item['is_paid']],
                        'payStatus' => '不存在',
                        'msg' => '<span style="color:red;">支付订单不存在</span>',
                        'paySuccessTime' => $result['gmt_finished']
                    );
                }
            }
        } //end foreach
        foreach ($result as $outOrderId => $item) {
            $exceptionOrders[$outOrderId] = array(
                'gmtFinished' => '',
                'outOrderId' => $outOrderId,
                'amount' => '0.00',
                'payAmount' => bcdiv($item['amount'], 100, 2),
                'status' => '不存在',
                'payStatus' => $orderStatusCn[$item['orderStatus']],
                'msg' => '<span style="color:red;">P2P订单不存在</span>',
                'paySuccessTime' => $item['gmt_finished']
            );
        }
        return $exceptionOrders;
    }

    private function _getTransferOrders($userId, $startTime = 0, $endTime = 0)
    {
        $orders = array();

        $where = empty($orderIds) ? "payerId='{$userId}' OR receiverId='{$userId}'" : 'outOrderId IN "'.implode('","', $orderIds).'"';
        $sql = "SELECT * FROM firstp2p_finance_detail_log WHERE (payerId='{$userId}' OR receiverId='{$userId}')";
        $sql .= $startTime === 0 ? '' : " AND create_time-28800>={$startTime}";
        $sql .= $endTime === 0 ? '' : " AND create_time-28800<={$endTime}";

        $result = $GLOBALS['db']->get_slave()->getAll($sql);
        //冷数据
        $moved_result = \libs\db\Db::getInstance('firstp2p_moved', 'slave')->getAll($sql);
        //合并
        $result = array_merge($moved_result, $result);

        $statusMap = array(
            0 => '初始状态(队列积压)',
            1 => '成功',
            2 => '失败',
        );

        foreach ($result as $item)
        {
            $orders[] = array(
                'type' => $item['payerId'] == $userId ? '转出' : '转入',
                'amount' => $item['repaymentAmount'],
                'orderId' => $item['outOrderId'],
                'time' => $item['create_time'] - date('Z'),
                'payType' => '17',
                'status' => $statusMap[$item['status']],
                'note' => "{$item['payerId']} => {$item['receiverId']} {$item['reason']}",
                'url' => "?m=FinanceQueue&a=detail&id={$item['outOrderId']}",
            );
        }

        return $orders;
    }

    private function _getChargeOrders($userId, $startTime = 0, $endTime = 0, $paymentId = 4)
    {
        $orders = array();

        $sql = "SELECT * FROM firstp2p_payment_notice WHERE user_id='{$userId}'";
        $sql .= $startTime === 0 ? '' : " AND create_time>={$startTime}";
        $sql .= $endTime === 0 ? '' : " AND create_time<={$endTime}";
        $sql .= " AND payment_id = {$paymentId}";
        $sql .= ' ORDER BY id DESC';

        $result = $GLOBALS['db']->get_slave()->getAll($sql);

        $statusMap = array(
            0 => '初始状态',
            1 => '成功',
            2 => '处理中',
            3 => '失败',
        );

        $payTypes = [
            PaymentNoticeModel::PLATFORM_FUND_REDEEM => 'fund_redeem',
        ];

        foreach ($result as $item)
        {
            $orders[] = array(
                'type' => '充值',
                'amount' => round($item['money'] * 100),
                'orderId' => $item['notice_sn'],
                'time' => $item['create_time'],
                'dealtime' => $item['pay_time'],
                'status' => $statusMap[$item['is_paid']],
                'payType' => $payTypes[$item['platform']]?:'10',
                'note' => "设备:{$item['platform']}(1Web,2IOS,3Android,8H5)",
                'url' => "?m=PaymentNotice&a=index&notice_sn={$item['notice_sn']}",
                'memo' => $item['memo'],
            );
        }

        return $orders;
    }

    private function _getWithdrawOrders($userId, $startTime = 0, $endTime = 0)
    {
        $orders = array();

        $sql = "SELECT * FROM firstp2p_user_carry WHERE user_id='{$userId}'";
        $sql .= $startTime === 0 ? '' : " AND create_time>={$startTime}";
        $sql .= $endTime === 0 ? '' : " AND create_time<={$endTime}";

        $result = $GLOBALS['db']->get_slave()->getAll($sql);

        $statusMap = array(
            0 => '初始状态',
            1 => '成功',
            2 => '失败',
            3 => '处理中',
        );

        foreach ($result as $item)
        {
            $orders[] = array(
                'type' => '提现',
                'amount' => round($item['money'] * 100),
                'orderId' => $item['id'],
                'time' => $item['create_time'],
                'dealtime' => $item['withdraw_time'],
                'status' => $statusMap[$item['withdraw_status']],
                'payType' => '14',
                'note' => $item['withdraw_msg'],
                'url' => "?m=UserCarry&a=index&id={$item['id']}",
            );
        }

        return $orders;
    }

    private function _getOfflineOrders($userId, $startTime = 0, $endTime = 0)
    {
        $orders = array();

        $sql = "SELECT * FROM firstp2p_money_apply WHERE user_id='{$userId}' AND type=2";
        $sql .= $startTime === 0 ? '' : " AND time>={$startTime}";
        $sql .= $endTime === 0 ? '' : " AND time<={$endTime}";

        $result = $GLOBALS['db']->get_slave()->getAll($sql);

        $payTypes = [
            PaymentNoticeModel::PLATFORM_FUND_REDEEM => 'fund_redeem',
        ];

        $statusMap = array(
            1 => '处理中',
            2 => '成功',
        );

        foreach ($result as $item)
        {
            $orders[] = array(
                'type' => '线下调账',
                'amount' => round($item['money'] * 100),
                'orderId' => $item['id'],
                'time' => $item['time'],
                'payType' => $payTypes[$item['platform']]?:'10',
                'status' => $statusMap[$item['type']],
            );
        }

        return $orders;
    }

    /**
     * 订单退款
     */
    public function yeepayRevertOrder()
    {
        // 允许发起退款的用户
        $whiteList = array('admin');
        $adminInfo = \es_session::get(md5(conf("AUTH_KEY")));
        if (!in_array($adminInfo['adm_name'], $whiteList)) {
            return $this->error('无权限操作');
        }

        $id = isset($_REQUEST['id']) ? trim($_REQUEST['id']) : 0;

        $orderInfo = $GLOBALS['db']->get_slave()->getRow("SELECT * FROM firstp2p_payment_notice WHERE is_paid = 1 AND notice_sn = '{$id}'");
        if (empty($orderInfo))
        {
            return $this->error('订单不存在，或者未支付成功，不能退款');
        }

        $yeepayService = new YeepayPaymentService();
        $refundParams = [
            'requestno' => $id,
            'paymentyborderid' => $orderInfo['outer_notice_sn'],
            'amount' => bcadd($orderInfo['money'], 0, 2),
        ];
        $ret = $yeepayService->merchantQueryServerDirectRefund($refundParams);
        if (isset($ret['data']['errorcode']))
        {
            return $this->error($ret['data']['errormsg']);
        }
        else
        {
            $GLOBALS['db']->query("UPDATE firstp2p_payment_notice SET memo = '已退款' WHERE is_paid = 1 AND notice_sn = '{$id}'");
            return $this->success('退款申请成功，5-10个工作日内会到账');
        }
    }

    /**
     * 用户绑定银行卡解绑
     */
    public function yeepayResetCard()
    {
        // 允许发起退款的用户
        $whiteList = array('admin');
        $adminInfo = \es_session::get(md5(conf("AUTH_KEY")));
        if (!in_array($adminInfo['adm_name'], $whiteList)) {
            return $this->error('无权限操作');
        }

        $id = isset($_REQUEST['id']) ? trim($_REQUEST['id']) : 0;
        $bindId = isset($_REQUEST['bindId']) ? intval($_REQUEST['bindId']) : 0;
        $cardtop = isset($_REQUEST['cardtop']) ? intval($_REQUEST['cardtop']) : 0;
        $cardlast = isset($_REQUEST['cardlast']) ? intval($_REQUEST['cardlast']) : 0;

        $yeepayService = new YeepayPaymentService();
        $ret = $yeepayService->bankCardUnbind($id, $cardtop, $cardlast);
        if (isset($ret['respCode']) && $ret['respCode'] !== '00')
        {
            return $this->error($ret['respMsg']);
        }
        else
        {
            return $this->success('解除绑定成功');
        }
    }


    /**
     * 单笔订单查询接口
     */
    public function searchYeepayTrade()
    {
        $id = isset($_REQUEST['id']) ? trim($_REQUEST['id']) : 0;

        $yeepayService = new YeepayPaymentService();
        $ret = $yeepayService->queryOrder(YeepayPaymentService::SEARCH_TYPE_BINDPAY, $id);

        if (empty($ret)) {
            echo json_encode(array('status' => '', 'statusMessage' => '接口异常',));
            return;
        }

        if ($ret['respCode'] === 'TZ2010060') {
            echo json_encode(array('status' => '初始状态', 'statusMessage' => '订单不存在',));
            return;
        }

        $ybPayMsg = isset(YeepayPaymentService::$ybPayStatusConfig[$ret['data']['status']]) ? YeepayPaymentService::$ybPayStatusConfig[$ret['data']['status']] : '未知状态';
        echo json_encode(array(
            'status' => $ybPayMsg,
            'amount' => $ret['data']['amount'],
            'statusMessage' => $ybPayMsg,
        ));
        return;
    }

    public function searchOneTrade()
    {
        $id = isset($_REQUEST['id']) ? trim($_REQUEST['id']) : 0;
        $type = isset($_REQUEST['type']) ? trim($_REQUEST['type']) : 0;

        $statusMap = array(
            '00' => '成功',
            '01' => '失败',
            '02' => '处理中',
        );

        $ret = \libs\utils\PaymentApi::instance()->request('searchonetrade', array('businessType' => $type, 'outOrderId' => $id));

        if (empty($ret)) {
            echo json_encode(array('status' => '', 'statusMessage' => '接口异常',));
            return;
        }

        if ($ret['status'] === '30004') {
            echo json_encode(array('status' => '初始状态', 'statusMessage' => '订单不存在',));
            return;
        }

        echo json_encode(array(
            'status' => $statusMap[$ret['orderStatus']],
            'amount' => $statusMap[$ret['amount']],
            'statusMessage' => $statusMap[$ret['orderStatus']],
        ));
        return;
    }

    public function exportTermOrders() {
        ini_set('memory_limit', '1024M');
        set_time_limit(0);
        $term = isset($_REQUEST['term']) ? trim($_REQUEST['term']) : '';
        $type = isset($_REQUEST['type']) ? trim($_REQUEST['type']) : '';
        $btype = isset($_REQUEST['btype']) ? trim($_REQUEST['btype']) : '';
        if (empty($term) || !in_array($btype, array('10','17','14'))) {
            $this->error('参数不正确');
        }
        $bTypeCn = '';
        if ($btype == '10') {
            $bTypeCn = '充值';
            $exceptionOrders = $this->_exportTermChargeOrders($term);
        }
        else if ($btype == '14') {
            $bTypeCn = '提现';
            $exceptionOrders = $this->_exportTermWithdrawOrders($term);
        }
        exit;
    }

    private function _exportTermChargeOrders($term) {
        $result = array();
        $pageNumber = 1;
        $termUTCBegin = strtotime($term) -28800;
        $termUTCEnd = $termUTCBegin + 86400;

        $sql = "SELECT notice_sn,money,is_paid,pay_time FROM firstp2p_payment_notice WHERE pay_time between '{$termUTCBegin}' AND '{$termUTCEnd}' AND is_paid  = 1";
        $p2pOrderInfo = $GLOBALS['db']->getAll($sql);
        do {
            $params = array(
                'businessType' => '10',
                'pageSize' => '1000',
                'pageNumber' => $pageNumber ++,
                'searchDate' => $term,
            );
            $response = PaymentApi::instance()->request('searchtrades', $params);
            if ($response['status'] != '00') {
                $this->error($response['respMsg']);
                exit;
            }
            $payOrderStatus = array(
                '00' => 'S',
                '01' => 'F',
                '02' => 'I',
                '03' => 'F',
            );
            foreach ($response['listP2pSearchAmountInOutDetailResult'] as $item) {
                unset($item['curType']);
                unset($item['businessType']);
                $item['orderStatus'] = $payOrderStatus[$item['orderStatus']];
                if ($item['orderStatus'] != 'S') {
                    continue;
                }
                $k = $item['outOrderId'];
                $result[$k] = $item;
            }
        } while ($response['isEndPage'] != 1);
        $exceptionOrders = array();
        $p2pOrderStatus = array(
            '1' => 'S',
            '2' => 'I',
            '3' => 'F',
            '0' => 'U',
        );
        $orderStatusCn = array(
            'S' => '成功',
            'I' => '处理中',
            'F' => '失败',
            'U' => '未处理',
        );
        $p2pOrders = array();
        foreach ($p2pOrderInfo as $k => $item) {
            $item['amount'] = round($item['money'] * 100);
            $item['is_paid'] = $p2pOrderStatus[$item['is_paid']];
            $item['gmtFinished'] = date('YmdHis', $item['pay_time'] + 28800);
            unset($item['money']);
            unset($item['id']);
            $p2pOrders[$item['notice_sn']] = $item;
        }

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . urlencode($term) . '.csv"');
        header('Cache-Control: max-age=0');
        $fp = fopen('php://output', 'a');

        $total = 0;
        $payTotal = 0;

        $title = array(
            "P2P付款完成时间", "支付付款完成时间", "订单号", "P2P金额(元)", "支付金额（元）",
            "P2P状态", "支付状态", "对账结果"
        );

        $title = iconv("utf-8", "gbk", implode(',', $title));
        fputcsv($fp, explode(',', $title));

        foreach ($p2pOrders as $outOrderId => $item) {
            if (isset($result[$outOrderId])) {
                $_payOrderItem = $result[$outOrderId];
                if ($_payOrderItem['orderStatus'] != $item['is_paid']
                    || $_payOrderItem['amount'] != $item['amount']) {
                    $exceptionOrders = array(
                        'gmtFinished' => $item['gmtFinished'],
                        'paySuccessTime' => $result['gmt_finished'],
                        'outOrderId' => $item['notice_sn'],
                        'amount' => bcdiv($item['amount'], 100, 2),
                        'payAmount' => bcdiv($_payOrderItem['amount'], 100, 2),
                        'status' => iconv('utf-8', 'gbk', $orderStatusCn[$item['is_paid']]),
                        'payStatus' => iconv('utf-8', 'gbk', $orderStatusCn[$_payOrderItem['orderStatus']]),
                        'msg' => iconv('utf-8', 'gbk', '<span style="color:red;">订单状态或者金额异常</span>'),
                    );
                    $total += $exceptionOrders['amount'];
                    $payTotal += $exceptionOrders['payAmount'];
                    fputcsv($fp, $exceptionOrders);
                }
                unset($result[$outOrderId]);
            } //end ifisset
            else {
                if ($item['is_paid'] === 'S' || $item['is_paid'] === 'I') {
                    $exceptionOrders = array(
                        'gmtFinished' => $item['gmtFinished'],
                        'paySuccessTime' => $result['gmt_finished'],
                        'outOrderId' => $item['notice_sn'],
                        'amount' => bcdiv($item['amount'], 100, 2),
                        'payAmount' => '0.00',
                        'status' => iconv('utf-8', 'gbk', $orderStatusCn[$item['is_paid']]),
                        'payStatus' => iconv('utf-8', 'gbk', '不存在'),
                        'msg' => iconv("utf-8", "gbk", '<span style="color:red;">支付订单不存在</span>'),
                    );
                    $total += $exceptionOrders['amount'];
                    $payTotal += $exceptionOrders['payAmount'];
                    fputcsv($fp, $exceptionOrders);
                }
            }
        } //end foreach
        foreach ($result as $outOrderId => $item) {
            $exceptionOrders = array(
                'gmtFinished' => '',
                'paySuccessTime' => $item['gmt_finished'],
                'outOrderId' => $outOrderId,
                'amount' => '0.00',
                'payAmount' => bcdiv($item['amount'], 100, 2),
                'status' => iconv('utf-8', 'gbk', '不存在'),
                'payStatus' => iconv('utf-8', 'gbk', $orderStatusCn[$item['orderStatus']]),
                'msg' => iconv('utf-8', 'gbk', '<span style="color:red;">P2P订单不存在</span>'),
            );
            $total += $exceptionOrders['amount'];
            $payTotal += $exceptionOrders['payAmount'];
            fputcsv($fp, $exceptionOrders);
        }
        $totalDesc = iconv('utf-8', 'gbk', 'P2P总额:' .$total);
        $payTotalDesc = iconv('utf-8', 'gbk', '支付总额:' .$total);
        fputcsv($fp, array($totalDesc, $payTotalDesc));
    }

    private function _exportTermWithdrawOrders($term) {
        $result = array();
        $pageNumber = 1;
        $termUTCBegin = strtotime($term) - 28800;
        $termUTCEnd = $termUTCBegin + 86400;
        $sql = "SELECT id,money,withdraw_status,withdraw_time FROM firstp2p_user_carry WHERE update_time between '{$termUTCBegin}' AND '{$termUTCEnd}'";
        $p2pOrderInfo = $GLOBALS['db']->getAll($sql);
        do {
            $params = array(
                'businessType' => '14',
                'pageSize' => '1000',
                'pageNumber' => $pageNumber ++,
                'searchDate' => $term,
            );
            $response = PaymentApi::instance()->request('searchtrades', $params);
            if ($response['status'] != '00') {
                $this->error($response['respMsg']);
                exit;
            }
            $payStatus = array( '00' => 'S', '01' => 'F', '02' => 'I', '03' => 'F',);
            foreach ($response['listP2pSearchAmountInOutDetailResult'] as $item) {
                unset($item['curType']);
                unset($item['businessType']);
                $k = $item['outOrderId'];
                $item['orderStatus'] = $payStatus[$item['orderStatus']];
                $result[$k] = $item;
            }
        } while ($response['isEndPage'] != 1);
        $exceptionOrders = array();
        $p2pOrderStatus = array(
            '1' => 'S',
            '3' => 'I',
            '2' => 'F',
            '0' => 'U',
        );
        $orderStatusCn = array(
            'S' => '成功',
            'I' => '处理中',
            'F' => '失败',
            'U' => '未处理',
        );
        $p2pOrders = array();
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . urlencode($term) . '.csv"');
        header('Cache-Control: max-age=0');
        $fp = fopen('php://output', 'a');

        $total = 0;
        $payTotal = 0;

        $title = array(
            "P2P付款完成时间", "支付付款完成时间", "订单号", "P2P金额(元)", "支付金额（元）",
            "P2P状态", "支付状态", "对账结果"
        );

        $title = iconv("utf-8", "gbk", implode(',', $title));
        fputcsv($fp, explode(',', $title));
        foreach ($p2pOrderInfo as $k => $item) {
            $item['withdraw_status'] = $p2pOrderStatus[$item['withdraw_status']];
            $item['amount'] = round($item['money'] * 100);
            $item['gmtFinished'] = date('YmdHis', $item['withdraw_time'] + 28800);
            unset($item['money']);
            $p2pOrders[$item['id']] = $item;
        }

        foreach ($p2pOrders as $outOrderId => $item) {
            if (isset($result[$outOrderId])) {
                $_payOrderItem = $result[$outOrderId];
                if ($_payOrderItem['orderStatus'] != $item['withdraw_status']
                    || $_payOrderItem['amount'] != $item['amount']) {
                    $exceptionOrders = array(
                        'gmtFinished' => $item['gmtFinished'],
                        'paySuccessTime' => $result['gmt_finished'],
                        'outOrderId' => $item['id'],
                        'amount' => bcdiv($item['amount'], 100, 2),
                        'payAmount' => $_payOrderItem['amount'],
                        'status' => iconv('utf-8', 'gbk', $orderStatusCn[$item['withdraw_status']]),
                        'payStatus' => iconv('utf-8', 'gbk', $orderStatusCn[$_payOrderItem['orderStatus']]),
                        'msg' => iconv('utf-8', 'gbk', '<span style="color:red;">订单状态或者金额异常</span>'),
                    );
                    $total += $exceptionOrders['amount'];
                    $payTotal += $exceptionOrders['payAmount'];
                    fputcsv($fp, $exceptionOrders);
                }
                unset($result[$outOrderId]);
            } //end ifisset
            else {
                if ($item['withdraw_status'] === 'S' || $item['withdraw_status'] === 'I') {
                    $exceptionOrders = array(
                        'gmtFinished' => $item['gmtFinished'],
                        'paySuccessTime' => $result['gmt_finished'],
                        'outOrderId' => $item['id'],
                        'amount' => bcdiv($item['amount'], 100, 2),
                        'payAmount' => '0.00',
                        'status' => iconv('utf-8', 'gbk', $orderStatusCn[$item['withdraw_status']]),
                        'payStatus' => iconv('utf-8', 'gbk', '不存在'),
                        'msg' => iconv('utf-8', 'gbk', '<span style="color:red;">支付订单不存在</span>'),
                    );
                }
                $total += $exceptionOrders['amount'];
                $payTotal += $exceptionOrders['payAmount'];
                fputcsv($fp, $exceptionOrders);
            }
        } //end foreach
        foreach ($result as $outOrderId => $item) {
            $exceptionOrders = array(
                'gmtFinished' => '',
                'paySuccessTime' => $item['gmt_finished'],
                'outOrderId' => $outOrderId,
                'amount' => '0.00',
                'payAmount' => bcdiv($item['amount'], 100, 2),
                'status' => iconv('utf-8', 'gbk', '不存在'),
                'payStatus' => iconv('utf-8', 'gbk', $orderStatusCn[$item['orderStatus']]),
                'msg' => iconv('utf-8', 'gbk', '<span style="color:red;">P2P订单不存在</span>'),
            );
            $total += $exceptionOrders['amount'];
            $payTotal += $exceptionOrders['payAmount'];
            fputcsv($fp, $exceptionOrders);
        }
        $totalDesc = iconv('utf-8', 'gbk', 'P2P总额:' .$total);
        $payTotalDesc = iconv('utf-8', 'gbk', '支付总额:' .$total);
        fputcsv($fp, array($totalDesc, $payTotalDesc));
    }

    /**
     * 用户资金记录检查
     */
    public function userLog()
    {
        $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

        $userInfo = $GLOBALS['db']->get_slave()->getRow("SELECT * FROM firstp2p_user WHERE id='{$id}'");
        // 根据id 计算用户数据表存放位置
        $ulModel= new \core\dao\UserLogModel();
        $idx = $ulModel->getDescriptor($id);
        $userLog = $GLOBALS['db']->get_slave()->getAll("SELECT * FROM firstp2p_user_log_{$idx} WHERE user_id='{$id}' ORDER BY id ASC");

        $typeMap = array(
            '提现申请' => '提现',
            '提现成功' => '提现',
            '提现失败' => '提现',
            '投标冻结' => '投标',
            '投资放款' => '投标',
            '取消投标' => '投标',
            '转账申请' => '后台转账',
            '转出资金' => '后台转账',
            '转账申请失败' => '后台转账',
        );

        $stat = array();
        $lockMoneySum = 0;
        $moneySum = 0;
        foreach ($userLog as $item)
        {
            $type = isset($typeMap[$item['log_info']]) ? $typeMap[$item['log_info']] : '其他';

            $stat[$type][$item['log_info']]['money'][] = $item['money'];
            $stat[$type][$item['log_info']]['lock_money'][] = $item['lock_money'];

            $lockMoneySum += $item['lock_money'];
            $moneySum += $item['money'];
        }

        krsort($stat);

        $chargeSuccess = $GLOBALS['db']->get_slave()->getRow("SELECT sum(money) total_money FROM firstp2p_payment_notice WHERE user_id='{$id}' AND is_paid=1");
        $withdrawSuccess = $GLOBALS['db']->get_slave()->getRow("SELECT sum(money) total_money FROM firstp2p_user_carry WHERE user_id='{$id}' AND withdraw_status=1");
        $withdrawDoing = $GLOBALS['db']->get_slave()->getRow("SELECT sum(money) total_money, count(*) total FROM firstp2p_user_carry WHERE user_id='{$id}' AND withdraw_status IN (0, 3) AND create_time>".strtotime('20140814'));

        $this->assign('userInfo', $userInfo);
        $this->assign('stat', $stat);
        $this->assign('moneySum', $moneySum);
        $this->assign('lockMoneySum', $lockMoneySum);
        $this->assign('chargeSuccess', $chargeSuccess);
        $this->assign('withdrawSuccess', $withdrawSuccess);
        $this->assign('withdrawDoing', $withdrawDoing);
        $this->display();
    }

    /**
     * 匹配字符串中的用户名、用户Id
     */
    private function _matchUserIds($content)
    {
        $contentArray = preg_split('/\s+/si', $content);

        $userIds = array();
        $userNames = array();
        $mobiles = array();
        foreach ($contentArray as $value) {
            //手机号
            if (preg_match('/^1\d{10}$/', $value)) {
                $mobiles[] = \libs\utils\DBDes::encryptOneValue($value);
            //用户ID
            } elseif (preg_match('/^\d+$/', $value) && $value > 0) {
                $userIds[] = intval($value);
            //用户名
            } elseif (preg_match('/^[\w-]{4,}$/', $value)) {
                $userNames[] = $value;
            }
        }

        //手机号
        if (!empty($mobiles)) {
            $sql = 'SELECT id FROM firstp2p_user WHERE mobile IN ("'.implode('","', $mobiles).'")';
            $ret = $GLOBALS['db']->get_slave()->getAll($sql);
            foreach ($ret as $item) {
                $userIds[] = intval($item['id']);
            }
        }

        //用户名
        if (!empty($userNames)) {
            $sql = 'SELECT id FROM firstp2p_user WHERE user_name IN ("'.implode('","', $userNames).'")';
            $ret = $GLOBALS['db']->get_slave()->getAll($sql);
            foreach ($ret as $item) {
                $userIds[] = intval($item['id']);
            }
        }

        return array_unique($userIds);
    }

    /**
     * 清空[易宝补单重试列表]
     */
    public function clearRetryList()
    {
        $result = array('status' => -1, 'msg' => '清理失败');
        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST')
        {
            $yeepayPaymentService = new \core\service\YeepayPaymentService();
            $ret = $yeepayPaymentService->clearRepairRetryList();
            $result = $ret ? array('status' => 0, 'msg' => '清理完毕') : array('code' => -2, 'msg' => '已清理');
        }
        ajax_return($result);
    }


    /**
     * 存管批量拆单补单
     */
    public function orderSplitRetry()
    {
        $result = array('status' => -1, 'msg' => '处理失败');
        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST')
        {
            // 获取外部交易流水号
            $idsArray = explode(',', addslashes($_POST['ids']));
            $idsCnt = count($idsArray);
            $orderSplitModel = new \core\dao\SupervisionOrderSplitModel();
            for ($i = 0; $i < $idsCnt; $i += 1)
            {
                $ret = $orderSplitModel->orderSplitRetryRedis($idsArray[$i]);
                $result = true === $ret['ret'] ? array('status' => 0, 'msg' => $ret['errMsg'], 'data'=>$ret['data']) : array('code' => -2, 'msg' => $ret['errMsg'], 'data'=>$ret['data']);
            }
        }
        ajax_return($result);
        exit;
    }
}
