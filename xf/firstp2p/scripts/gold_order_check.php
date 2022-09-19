<?php
/**
 * 用户余额核对
 */
ini_set('memory_limit', '2048M');
set_time_limit(0);

require_once(dirname(__FILE__) . '/../app/init.php');

use libs\utils\PaymentApi;
use libs\utils\Rpc;
use libs\db\Db;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;
use NCFGroup\Protos\Gold\RequestCommon;
use core\dao\MoneyOrderModel;
use NCFGroup\Protos\Ptp\Enum\MoneyOrderEnum;
use NCFGroup\Protos\Gold\Enum\GoldMoneyOrderEnum;

/**
 * 定义个异常类型，便于处理对账错误
 */
class OrderCheckException extends \Exception {
}

/**
 * 对账类
 */
class GoldOrderCheck {

    public $bizName; // 对账业务名称，同rpc名称

    public $bizType;

    public $orderType;

    public $date; // 对账日期

    public $logPre; // 日志前缀

    public $step; // 单次订单获取数量或者单次获取时间段

    public $orderCount;

    public static $orderMap = [
        'money_order',
        'transfer_order'
    ];

    public static $orderDesc = [
        'money_order' => '资金订单',
        'transfer_order' => '转账订单'
    ];

    // 不需要检查用户的类型
    public static $noCheckUser = [
        GoldMoneyOrderEnum::BIZ_SUBTYPE_GOLD_LOAN_PAY_FEE,
        GoldMoneyOrderEnum::BIZ_SUBTYPE_GOLD_LOAN_FEE
    ];

    public function __construct($direction, $orderType, $date, $step)
    {
        $this->startTime = microtime(true);
        $this->direction = $direction;
        $this->bizName = 'gold';
        $this->bizType = MoneyOrderEnum::BIZ_TYPE_GOLD;
        $this->date = $date;
        $this->orderType = $orderType;
        $this->logPrefix = 'GoldOrderCheck,';
        $this->orderType = $orderType;
        $this->step = $step;
        foreach (self::$orderMap as $orderType) {
            $this->orderCount[$orderType] = 0;
            $this->errOrderCount[$orderType] = 0;
            $this->exceptionOrderCount[$orderType] = 0;
        }
    }

    public function run() {
        if ($this->direction == MoneyOrderEnum::CHECK_WX_BIZ) {
            $this->WXToBiz();
        } else {
            $this->bizToWX();
        }
        $this->_notice();
    }

    /*
     * 网信对账业务
     */
    public function WXToBiz()
    {
        if (empty($this->orderType)) {
            foreach (self::$orderMap as $orderType) {
                $this->_wxOrderProcess($orderType);
            }
        } else {
            $this->_wxOrderProcess($this->orderType);
        }
    }

    /**
     * 业务对账网信
     */
    public function bizToWX()
    {
        if (empty($this->orderType)) {
            foreach (self::$orderMap as $orderType) {
                $this->_bizOrderProcess($orderType);
            }
        } else {
            $this->_bizOrderProcess($this->orderType);
        }
    }

    /**
     * 网信对账业务逻辑
     */
    private function _wxOrderProcess($orderType)
    {
        $timeStart = $this->date;
        $timeEnd = $timeStart + 24 * 3600;
        $sql = "SELECT * FROM firstp2p_$orderType WHERE biz_type = {$this->bizType} AND check_status = 0 AND create_time >= $timeStart AND create_time < $timeEnd";
        $this->_log($sql);
        $totalCount = 0;
        $res = Db::getInstance('firstp2p', 'slave')->query($sql);
        while ($orderInfo = mysql_fetch_assoc($res)) {
            ++$totalCount;
            $this->_checkWXOrder($orderInfo, $orderType);
        }
        $this->orderCount[$orderType] = $totalCount;
        $this->_log('WXOrder ' .$orderType. ' TotalCount:' . $totalCount);
    }

    /**
     * 业务对账网信逻辑
     */
    private function _bizOrderProcess($orderType)
    {
        $start = 0;
        $timeStart = $this->date;
        $timeEnd = $timeStart + 24 * 3600;
        $totalCount = 0;
        while($timeStart < $timeEnd) {
            $timeStart += $this->step;
            try {
                $params = [
                    'timeStart' => $timeStart,
                    'timeEnd' => $timeStart+$this->step,
                    'isTransfer' => $orderType == 'transfer_order' ? 1 : 0
                ];
                $request = new RequestCommon();
                $request->setVars($params);
                $response = $this->_requestBiz($request, 'searchOrderByTime');
                if (empty($response)) {
                    throw new \Exception('黄金服务异常');
                }

                if ($response['errCode'] != 0) {
                    throw new \Exception('黄金服务报错' . $response['errMsg']);
                }

                if (empty($response['data'])) {
                    continue;
                }
                $bizOrderList = $response['data'];
                $totalCount += count($bizOrderList);
                foreach ($bizOrderList as $bizOrderInfo) {
                    $this->_checkBizOrder($bizOrderInfo, $orderType);
                }
            } catch (\Exception $e) {
                $this->_log($e->getMessage());
                continue;
            }
        }
        $this->orderCount[$orderType] = $totalCount;
        $this->_log('BizOrder ' .$orderType. ' TotalCount:' . $totalCount);
    }

    /**
     * 网信对业务具体逻辑
     */
    private function _checkWXOrder($wxOrder, $orderType)
    {
        // request to biz
        $params = [
            'orderId' => $wxOrder['biz_order_id'],
            'bizSubtype' => $wxOrder['biz_subtype'],
            'isTransfer' => $orderType == 'transfer_order' ? 1 : 0
        ];
        $request = new RequestCommon();
        $request->setVars($params);
        try {
            $bizOrder = [];
            $response = $this->_requestBiz($request, 'searchOrder');
            if (empty($response)) {
                throw new \Exception('黄金接口异常');
            }

            if ($response['errCode'] !== 0) {
                throw new \Exception('黄金接口报错' . $response['errMsg']);
            }

            if (empty($response['data'])) {
                throw new OrderCheckException('业务订单不存在', MoneyOrderEnum::CHECK_ERROR_NO_ORDER);
            }

            $bizOrder = $response['data'];
            if ($bizOrder['orderStatus'] != 1) {
                throw new OrderCheckException('业务订单类型错误', MoneyOrderEnum::CHECK_ERROR_ORDER_STATUS);
            }

            $this->_checkOrderDetail($wxOrder, $bizOrder, $orderType);
            $this->_markOrder($wxOrder, $orderType);
        } catch (OrderCheckException $e) {
            $this->_saveErrorOrder($wxOrder, $bizOrder, $orderType, MoneyOrderEnum::CHECK_WX_BIZ, $e->getCode());
        } catch (\Exception $e) {
            $this->exceptionOrderCount[$orderType]++;
            //TODO Log
            $msg = 'WxToBizCheckFail:'. $e->getMessage() . ', bizOrderId:' . $wxOrder['biz_order_id'];
            $this->_log($msg);
        }

        return true;
    }

    /**
     * 业务对网信具体逻辑
     */
    private function _checkBizOrder($bizOrder, $orderType)
    {
        $modelName = '\core\dao\\' . str_replace(' ', '', ucwords(str_replace('_', ' ', $orderType))) . 'Model';
        $wxOrder = $modelName::instance()->searchOrder($bizOrder['orderId'], $this->bizType, $bizOrder['bizSubtype']);
        try {
            if (empty($wxOrder) && $bizOrder['orderStatus'] == 1) {
                throw new OrderCheckException('网信订单不存在', MoneyOrderEnum::CHECK_ERROR_NO_ORDER);
            }
            $this->_checkOrderDetail($wxOrder,  $bizOrder, $orderType);
        } catch (OrderCheckException $e) {
            $this->_saveErrorOrder($wxOrder, $bizOrder, $orderType, MoneyOrderEnum::CHECK_BIZ_WX, $e->getCode());
        } catch (\Exception $e) {
            $this->exceptionOrderCount[$orderType]++;
            //TODO Log
            $msg = 'BizToWXCheckFail:'. $e->getMessage() . ', bizOrderId:' . $bizOrder['orderId'];
            $this->_log($msg);
        }
    }

    /**
     * 订单信息比对逻辑
     */
    private function _checkOrderDetail($wxOrder, $bizOrder, $orderType)
    {
        if (bccomp($wxOrder['amount'], $bizOrder['amount']) !== 0) {
            throw new OrderCheckException('订单金额不一致', MoneyOrderEnum::CHECK_ERROR_AMOUNT);
        }

        if (in_array($wxOrder['biz_subtype'], self::$noCheckUser)) {
            return true;
        }

        if ($orderType == 'transfer_order') {
            if ($wxOrder['payer_id'] != $bizOrder['payerId']) {
                throw new OrderCheckException('付款方不一致', MoneyOrderEnum::CHECK_ERROR_PAYER);
            }

            if ($wxOrder['receiver_id'] != $bizOrder['receiverId']) {
                throw new OrderCheckException('收款方不一致', MoneyOrderEnum::CHECK_ERROR_RECEIVER);
            }
        } else {
            if ($wxOrder['user_id'] != $bizOrder['userId']) {
                throw new OrderCheckException('用户不一致', MoneyOrderEnum::CHECK_ERROR_USER);
            }
        }
        return true;
    }

    /**
     * 对账成功的订单置为已对账
     */
    private function _markOrder($order, $orderType)
    {
        $modelName = '\core\dao\\' . str_replace(' ', '', ucwords(str_replace('_', ' ', $orderType))) . 'Model';
        $data['check_status'] = MoneyOrderEnum::CHECK_STATUS_DONE;
        $data['check_time'] = time();
        return $modelName::instance()->updateOrder($order['id'], $data);
    }

    /**
     * 保存错误订单及信息
     */
    private function _saveErrorOrder($wxOrder, $bizOrder, $orderType, $direction, $errorType)
    {
        ++$this->errOrderCount[$orderType];
        $paymentDb = Db::getInstance('firstp2p_payment');
        if (is_object($wxOrder)) {
            $wxOrder = $wxOrder->getRow();
        }
        unset($wxOrder['id']);
        unset($wxOrder['check_status']);
        unset($wxOrder['check_time']);
        unset($wxOrder['create_time']);
        unset($wxOrder['update_time']);
        ksort($wxOrder);
        ksort($bizOrder);
        $orderData = ['wx' => $wxOrder, 'biz' => $bizOrder];
        $data = [
            'order_data' => json_encode($orderData, JSON_UNESCAPED_UNICODE),
            'date' => date('Ymd', $this->date),
            'direction' => $direction,
            'order_type' => $orderType == 'transfer_order' ? 2 : 1,
            'error_type' => $errorType,
            'create_time' => time()
        ];
        if ($direction == MoneyOrderEnum::CHECK_WX_BIZ) {
            $data['biz_order_id'] = $wxOrder['biz_order_id'];
            $data['biz_type'] = $wxOrder['biz_type'];
            $data['biz_subtype'] = $wxOrder['biz_subtype'];
        } else {
            $data['biz_order_id'] = $bizOrder['orderId'];
            $data['biz_type'] = MoneyOrderEnum::BIZ_TYPE_GOLD;
            $data['biz_subtype'] = $bizOrder['bizSubtype'];
        }
        $where = "biz_order_id = '{$data['biz_order_id']}' AND biz_type ='{$data['biz_type']}' AND biz_subtype = '{$data['biz_subtype']}'";
        try {
            $sql = 'SELECT * FROM firstp2p_money_order_error WHERE ' . $where;
            if ($paymentDb->getOne($sql)) {
                $paymentDb->update('firstp2p_money_order_error', $data, $where);
            } else {
                $paymentDb->insert('firstp2p_money_order_error', $data);
            }
        } catch (\Exception $e) {
            $this->_log('保存错误订单失败' . $e->getMessage());
        }
    }

    /**
     * 请求对应业务订单查询接口
     */
    private function _requestBiz($request, $method)
    {
        $service = "NCFGroup\Gold\Services\Order";
        try {
            $rpc = new Rpc($this->bizName . 'Rpc');
            $response = $rpc->go($service, $method, $request);
        } catch (\Exception $e) {
            $this->_log("Request $this->bizName error:" . $e->getMessage());
            return false;
        }
        return $response;
    }

    private function _log($msg)
    {
        $msg = $this->logPrefix . $msg;
        PaymentApi::log($msg);
    }

    private function _notice()
    {
        $content = '';
        $totalCount = 0;
        $errTotalCount = 0;
        foreach ($this->orderCount as $orderType => $count) {
            $content .= self::$orderDesc[$orderType] . ':'. $count . ',';
            $totalCount += $count;
        }
        $taskDesc = sprintf(MoneyOrderEnum::$checkDirectionDesc[$this->direction], $this->bizName) . ':';
        $content = '订单总数量:' . $totalCount . ',' . $content;

        if (!empty($this->errOrderCount)) {
            $errContent = '';
            foreach ($this->errOrderCount as $orderType => $count) {
                $errContent .= self::$orderDesc[$orderType] . ':'. $count . ',';
                $errTotalCount += $count;
            }
            $content =  trim($content . '差异订单数:' . $errTotalCount . ',' . $errContent, ',');
        } else {
            $content .= '差异订单数：0';
        }

        if (!empty($this->exceptionOrderCount)) {
            $exceptionContent = '';
            foreach ($this->exceptionOrderCount as $orderType => $count) {
                $exceptionContent .= self::$orderDesc[$orderType] . ':'. $count . ',';
                $exceptionTotalCount += $count;
            }
            $content =  trim($content . '异常订单数:' . $exceptionTotalCount . ',' . $exceptionContent, ',');
        } else {
            $content .= '异常订单数：0';
        }

        $mobile = ['18910299230', '15010331849'];
        $ret = \libs\sms\SmsServer::sendAlertSms($mobile, date('Y年n月j日', $this->date). $taskDesc . $content);
        $this->_log('sms.end. ret:'.$ret);

        $mail = new \NCFGroup\Common\Library\MailSendCloud();
        $mailAddress = ['luzhengshuai@ucfgroup.com', 'quanhengzhuang@ucfgroup.com', 'zhaoxiaoan@ucfgroup.com', 'liangqiang@ucfgroup.com', 'wangzhen3@ucfgroup.com'];
        $subject = date('Y年n月j日', $this->date).'资金统一订单对账,' . $taskDesc;
        $body = '';
        $body .= "<h3>$subject</h3>";
        $body .= '<ul style="color:#1f497d;">';
        $body .= '<li>对账时间: '.date('Y-m-d H:i:s ~ ', $this->startTime).date('Y-m-d H:i:s').'</li>';
        $body .= '<li>对账耗时: '.round(microtime(true) - $this->startTime).' 秒</li>';
        $body .= '</ul>';
        $body .= '<p>' .$content. '</p>';
        $body .= '<a href="http://itil.firstp2p.com/moneyorder">点击查看详情</a>';
        $ret = $mail->send($subject, $body, $mailAddress);
        $this->_log('mail.end. ret:'.var_export($ret, true));

    }
}

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
set_time_limit(0);
ini_set('memory_limit', '1024M');


$usage = <<<EOT
Usage: gold_order_check.php [options] [-dr] wxtobiz [--] [args...]

-r 1      指定对账方向1为网信对业务或者2为业务对网信.
-t money_order      对账类型money_order或者transfer_order, 默认为全部.
--date 20170111     对账日期默认为当天
--step 300    单次对账拉取量, biztowx为时间间隔，单位秒，wxtobiz为数据获取量，等同于limit

EOT;
$options = getopt('r:t:', ['date:', 'step:']);
if (empty($options)) {
    echo $usage . PHP_EOL;
    die();
}

if (empty($options['r']) || !array_key_exists($options['r'], MoneyOrderEnum::$checkDirectionDesc)) {
    echo '请指定对账方向' . PHP_EOL;
    echo $usage . PHP_EOL;
    die();
}
$direction = $options['r'];
$orderType = !empty($options['t']) ? $options['t'] : '';
if (!empty($orderType) && !in_array($orderType, GoldOrderCheck::$orderMap)) {
    echo '错误的订单类型' . PHP_EOL;
    echo $usage . PHP_EOL;
    die();
}

$date = !empty($options['date']) ? strtotime($options['date']) : strtotime(date('Y-m-d', strtotime('-1 days')));
$step = !empty($options['step']) ? $options['step'] : 300;
\libs\utils\Script::start();
$orderCheckTask = new GoldOrderCheck($direction, $orderType, $date, $step);
$orderCheckTask->run();
\libs\utils\Script::end();
