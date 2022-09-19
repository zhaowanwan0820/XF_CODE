<?php
// +----------------------------------------------------------------------
// | 收款单列表
// +----------------------------------------------------------------------
// | Author: wenyanlei@ucfgroup.com
// +----------------------------------------------------------------------

use core\dao\PaymentNoticeModel;
use core\dao\SupervisionChargeModel;
use core\dao\SupervisionWithdrawModel;
use core\dao\SupervisionTransferModel;
use core\dao\BankModel;
use core\service\SupervisionFinanceService;
use core\service\SupervisionDealService;
use core\service\SupervisionBaseService;
use core\service\P2pIdempotentService;
use libs\common\WXException;
use core\service\YeepayPaymentService;
use core\service\ncfph\SupervisionService as PhSupervisionService;

class PaymentNoticeAction extends CommonAction
{
    const PAYMENT_YEEPAY = 'Yeepay';
    const PAYMENT_XFJR = 'Xfjr';
    const PAYMENT_ZJTG = 'Zjtg';
    const PAYMENT_HKSUPERVISION = 'HkSupervision';

    private static $input_type_list = array(
        '0' => '文本输入',
        '1' => '下拉框输入',
        '5' => '日期时间',
    );

    /**
     * 后台可用的充值平台
     * @var array
     */
    private static $enablePaymentName = [self::PAYMENT_YEEPAY, self::PAYMENT_XFJR, self::PAYMENT_ZJTG, self::PAYMENT_HKSUPERVISION];

    /**
     * 充值平台-海口联合农商银行配置
     * @var array
     */
    private static $supervisionPaymentConfig = [
        'id' => 100, 'class_name' => 'HkSupervision','is_effect' => 1,'online_pay' => 1,
        'fee_amount' => '0.0000','name' => '海口联合农商银行','description' => '','total_amount' => '0.0000',
        'config' => '','logo' => '','sort' => 8,'fee_type' => 1,'max_fee' => '0.0000',
    ];

    /**
     * 存管系统-业务补单列表
     * @var array
     */
    private static $supervisionBusinessConfig = [
        1 => ['id'=>1, 'platform'=>'海口联合农商银行', 'name'=>'提现', 'type'=>1, 'notifyMethod'=>'withdrawNotify'],
        2 => ['id'=>2, 'platform'=>'海口联合农商银行', 'name'=>'网信理财账户余额 划转到 网贷P2P账户余额', 'type'=>1, 'notifyMethod'=>'superRechargeNotify', 'businessType'=>SupervisionTransferModel::DIRECTION_TO_SUPERVISION],
        3 => ['id'=>3, 'platform'=>'海口联合农商银行', 'name'=>'网贷P2P账户余额 划转到 网信理财账户余额', 'type'=>1, 'notifyMethod'=>'superRechargeNotify', 'businessType'=>SupervisionTransferModel::DIRECTION_TO_WX],
        4 => ['id'=>4, 'platform'=>'海口联合农商银行', 'name'=>'放款', 'type'=>2, 'notifyMethod'=>'dealGrantNotify', 'businessType'=>SupervisionFinanceService::BATCHORDER_TYPE_GRANT],
        5 => ['id'=>5, 'platform'=>'海口联合农商银行', 'name'=>'还款', 'type'=>2, 'notifyMethod'=>'dealRepayNotify', 'businessType'=>SupervisionFinanceService::BATCHORDER_TYPE_REPAY],
        6 => ['id'=>6, 'platform'=>'海口联合农商银行', 'name'=>'流标', 'type'=>2, 'notifyMethod'=>'dealCancelNotify', 'businessType'=>SupervisionFinanceService::BATCHORDER_TYPE_DEALCANCEL],
    ];

    /**
     * 存管系统普惠-业务补单列表
     * @var array
     */
    private static $supervisionBusinessCnConfig = [
        1 => ['id'=>1, 'platform'=>'海口联合农商银行', 'name'=>'提现', 'type'=>1, 'notifyMethod'=>'withdrawNotify'],
        4 => ['id'=>4, 'platform'=>'海口联合农商银行', 'name'=>'放款', 'type'=>2, 'notifyMethod'=>'dealGrantNotify', 'businessType'=>SupervisionFinanceService::BATCHORDER_TYPE_GRANT],
        5 => ['id'=>5, 'platform'=>'海口联合农商银行', 'name'=>'还款', 'type'=>2, 'notifyMethod'=>'dealRepayNotify', 'businessType'=>SupervisionFinanceService::BATCHORDER_TYPE_REPAY],
        6 => ['id'=>6, 'platform'=>'海口联合农商银行', 'name'=>'流标', 'type'=>2, 'notifyMethod'=>'dealCancelNotify', 'businessType'=>SupervisionFinanceService::BATCHORDER_TYPE_DEALCANCEL],
    ];

    public function index(){
//         if(trim($_REQUEST['order_sn'])!=''){
//             $condition['order_id'] = MI("DealOrder")->where("order_sn='".trim($_REQUEST['order_sn'])."'")->getField("id");
//         }

        if(trim($_REQUEST['notice_sn'])!=''){
            $condition['notice_sn'] = $_REQUEST['notice_sn'];
        }

        // 会员名称，精确匹配，避免慢查询
        if(trim($_REQUEST['user_name']) != '')
        {
            $uid = MI('User')->where(array('user_name'=>array('eq', trim($_REQUEST['user_name']))))->getField('id');
            $condition['user_id'] = array('eq', $uid);
            $this->assign('user_name', $_REQUEST['user_name'] );
            unset($_REQUEST['user_name']);
        }

        $user_num = trim($_REQUEST['user_num']);
        if($user_num){
            $condition['user_id'] = array('eq', de32Tonum($user_num));
        }

        // 付款时间
        $pay_start = $pay_end = 0;
        if (!empty($_REQUEST['pay_time_start'])) {
            $pay_start = to_timespan($_REQUEST['pay_time_start']);
            $condition['pay_time'] = array('egt', $pay_start);
        }

        if (!empty($_REQUEST['pay_time_end'])) {
            $pay_end = to_timespan($_REQUEST['pay_time_end']);
            $condition['pay_time'] = array('between', sprintf('%s,%s', $pay_start, $pay_end));
        }

        if(intval($_REQUEST['payment_id'])==0) unset($_REQUEST['payment_id']);

        $chargeSourceId = intval($_REQUEST['charge_source_id']);
        if(!empty($chargeSourceId)){
            $platform_config_id = PaymentNoticeModel::$chargeResourceGroupConfig[$chargeSourceId];
            $condition['platform'] = array('in', $platform_config_id);
            if ($chargeSourceId == PaymentNoticeModel::RESOURCE_FASTPAY) {
                $condition['payment_id'] = array('neq', PaymentNoticeModel::PAYMENT_YEEPAY);
            }
            if ($chargeSourceId == PaymentNoticeModel::RESOURCE_YEEPAY) {
                $condition['payment_id'] = array('eq', PaymentNoticeModel::PAYMENT_YEEPAY);
            }
            if ($chargeSourceId == PaymentNoticeModel::RESOURCE_OFFLINEPAY) {
                $condition['payment_id'] = array('neq', PaymentNoticeModel::PAYMENT_YEEPAY);
            }
            if ($chargeSourceId == PaymentNoticeModel::RESOURCE_OPEN) {
                $condition['payment_id'] = array('neq', PaymentNoticeModel::PAYMENT_YEEPAY);
            }
            if ($chargeSourceId == PaymentNoticeModel::RESOURCE_PC) {
                $condition['payment_id'] = array('neq', PaymentNoticeModel::PAYMENT_YEEPAY);
            }
            if ($chargeSourceId == PaymentNoticeModel::RESOURCE_ORDER) {
                $condition['payment_id'] = array('neq', PaymentNoticeModel::PAYMENT_YEEPAY);
            }
        }

        $this->assign('charge_resource_list', PaymentNoticeModel::$chargeResourceShowConfig);
        $this->assign("default_map",$condition);
        $this->assign("payment_list",MI("Payment")->findAll());
        $this->assign('main_title', '充值订单列表');
        // 不再计算列表页总条数
        $this->_setPageEnable(false);
        parent::index();
    }

    public function export_payment() {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        $where_arr = array();

//         if (trim($_REQUEST['order_sn']) != '') {
//             $order_id = MI("DealOrder")->where("order_sn='" . trim($_REQUEST['order_sn']) . "'")->getField("id");
//             $where_arr[] = "pn.order_id = '".$order_id."'";
//         }

        if (trim($_REQUEST['notice_sn']) != '') {
            $where_arr[] = "pn.notice_sn = '".trim($_REQUEST['notice_sn'])."'";
        }

        if(intval($_REQUEST['payment_id']) != 0){
            $where_arr[] = "pn.payment_id = '".intval($_REQUEST['payment_id'])."'";
        }

        $pay_time_start = to_timespan($_REQUEST['pay_time_start']);
        $pay_time_end = to_timespan($_REQUEST['pay_time_end']);
        if($pay_time_start){
            $where_arr[] = "pn.pay_time >= ".$pay_time_start;
        }
        if($pay_time_end){
            $where_arr[] = "pn.pay_time <= ".$pay_time_end;
        }

        $chargeSourceId = intval($_REQUEST['charge_source_id']);
        if(!empty($chargeSourceId)){
            $platform_config_id = PaymentNoticeModel::$chargeResourceGroupConfig[$chargeSourceId];
            $where_arr[] = "pn.platform in "."(".implode(",", $platform_config_id).")";
            if ($chargeSourceId == PaymentNoticeModel::RESOURCE_FASTPAY) {
                $where_arr[] = "pn.payment_id != ".PaymentNoticeModel::PAYMENT_YEEPAY;
            }
            if ($chargeSourceId == PaymentNoticeModel::RESOURCE_YEEPAY) {
                $where_arr[] = "pn.payment_id = ".PaymentNoticeModel::PAYMENT_YEEPAY;
            }
            if ($chargeSourceId == PaymentNoticeModel::RESOURCE_OFFLINEPAY) {
                $where_arr[] = "pn.payment_id != ".PaymentNoticeModel::PAYMENT_YEEPAY;
            }
            if ($chargeSourceId == PaymentNoticeModel::RESOURCE_OPEN) {
                $where_arr[] = "pn.payment_id != ".PaymentNoticeModel::PAYMENT_YEEPAY;
            }
            if ($chargeSourceId == PaymentNoticeModel::RESOURCE_PC) {
                $where_arr[] = "pn.payment_id != ".PaymentNoticeModel::PAYMENT_YEEPAY;
            }
            if ($chargeSourceId == PaymentNoticeModel::RESOURCE_ORDER) {
                $where_arr[] = "pn.payment_id != ".PaymentNoticeModel::PAYMENT_YEEPAY;
            }
        }
        //$where_str = $where_arr ? ' AND '.implode(' AND ', $where_arr) : '';

        //$sql = 'SELECT pn.*,o.order_sn AS order_sn, u.user_name, p.name, bc.name AS bankname FROM firstp2p_payment_notice pn, firstp2p_user u, firstp2p_payment p, firstp2p_deal_order o, firstp2p_bank_charge bc WHERE pn.user_id=u.id AND pn.payment_id=p.id AND pn.order_id=o.id AND bc.short_name=o.bank_id '.$where_str.' ORDER BY pn.id DESC';

        $where_str = $where_arr ? ' where '.implode(' AND ', $where_arr) : '';
        //$sql = 'SELECT pn.*,o.order_sn AS order_sn,o.payment_id,o.bank_id,u.user_name, p.name FROM firstp2p_payment_notice pn left join firstp2p_user u on pn.user_id=u.id left join firstp2p_payment p on pn.payment_id=p.id left join  firstp2p_deal_order o on pn.order_id=o.id '.$where_str.' ORDER BY pn.id DESC';
        $sql = 'SELECT pn.*,u.user_name, p.name FROM firstp2p_payment_notice pn left join firstp2p_user u on pn.user_id=u.id left join firstp2p_payment p on pn.payment_id=p.id '.$where_str.' ORDER BY pn.id DESC';

        $res = $GLOBALS['db']->get_slave()->query($sql);
        if ($res === false) {
            $this->error('收款单列表为空');
        }



        //记录导出日志
        setLog(
            array(
                'sensitive' => 'exportPaymentNotice',
                'analyze' => $sql
                )
        );




        $datatime = date("YmdHis", time());
        $file_name = 'payment_' . $datatime;
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $file_name . '.csv"');
        header('Cache-Control: max-age=0');

        $fp = fopen('php://output', 'a');
        $count = 1; // 计数器
        $limit = 10000; // 每隔$limit行，刷新一下输出buffer，不要太大，也不要太小

        //$content = iconv("utf-8", "gbk//IGNORE", "编号,付款单号,创建时间,支付时间,是否已支付,订单号,会员名称,收款方式,银行卡,付款单金额,支付平台交易号,付款单备注") . "\n";
        $head = array("编号", "付款单号", "创建时间", "支付时间", "支付状态", "订单号", "会员名称", "收款方式", "充值来源", "银行卡", "付款单金额", "支付平台交易号", "付款单备注", "扣除平台账户手续费 ", "手续费");
        foreach ($head as &$item) {
            $item = iconv("utf-8", "gbk//IGNORE", $item);
        }
        fputcsv($fp, $head);

        while($val = $GLOBALS['db']->fetchRow($res)) {
            // 过滤基金到账充值
            if ($val['platform'] == PaymentNoticeModel::PLATFORM_FUND_REDEEM)
            {
                continue;
            }
            $create_time = to_date($val['create_time']);
            $pay_time = to_date($val['pay_time']);
            $is_paid = '';
            if($val['is_paid'] == 0){
                $is_paid = '未支付';
            }else if ($val['is_paid'] == 1){
                $is_paid =  '支付成功';
            } else if ($val['is_paid'] == 2){
                $is_paid = '待支付';
            } else if ($val['is_paid'] == 3) {
                $is_paid = '支付失败';
            }
            $money = sprintf("%.2f",$val['money']);
            $fee_charged = $val['is_platform_fee_charged'] == 0 ? '否' : '是';
            $fee = sprintf("%.2f",$val['fee']);
            $platform_name = PaymentNoticeModel::$chargeResourceNameConfig[$val['payment_id']][$val['platform']];

            $bank_name = '';
//             if($val['payment_id'] && $val['bank_id']){
//                 $bank_where = ($val['payment_id'] == 4) ? "short_name='".$val["bank_id"]."'" : "value like '".$val["bank_id"]."-%'";
//                 $bank_name = MI("bankCharge")->where($bank_where)->getField('name');
//                 if(empty($bank_name)){
//                     $bank_name = $val["bank_id"];
//                 }
//             }

            $count++;
            if ($count % $limit == 0) { //刷新一下输出buffer，防止由于数据过多造成问题
                ob_flush();
                flush();
                $count = 0;
            }
            $arr = array(
                    $val['id'],
                    "" . $val['notice_sn'] . "\t",
                    $create_time,
                    $pay_time,
                    $is_paid,
                    "" . $val['notice_sn'] . "\t",
                    $val['user_name'],
                    $val['name'],
                    $platform_name,
                    $bank_name,
                    $money,
                    "\t".$val['outer_notice_sn'],
                    $val['memo'],
                    $fee_charged,
                    $fee,
            );
            foreach ($arr as &$item) {
                $item = iconv("utf-8", "gbk//IGNORE", $item);
            }
            fputcsv($fp, $arr);
        }
        EXIT;
    }

    public function export_payment_bak(){
        set_time_limit(0);
        @ini_set('memory_limit','128M');
        $condition = array();

        if(trim($_REQUEST['order_sn'])!=''){
            $condition['order_id'] = MI("DealOrder")->where("order_sn='".trim($_REQUEST['order_sn'])."'")->getField("id");
        }

        if(trim($_REQUEST['notice_sn'])!=''){
            $condition['notice_sn'] = trim($_REQUEST['notice_sn']);
        }

        $sql = 'SELECT pn.*,o.order_sn AS order_sn, u.user_name, p.name, bc.name AS bankname FROM firstp2p_payment_notice pn, firstp2p_user u, firstp2p_payment p, firstp2p_deal_order o, firstp2p_bank_charge bc WHERE pn.user_id=u.id AND pn.payment_id=p.id AND pn.order_id=o.id AND bc.short_name=o.bank_id ORDER BY pn.id DESC';
        $voList = $GLOBALS['db']->getAll($sql);
        $content = iconv("utf-8","gbk//IGNORE","编号,付款单号,创建时间,支付时间,是否已支付,订单号,会员名称,收款方式,银行卡,付款单金额,支付平台交易号,付款单备注")."\n";

        foreach ( $voList as $k=>$val ){
            $create_time = to_date($val['create_time']);
            $pay_time = to_date($val['pay_time']);
            $is_paid = $val['is_paid'] == 1 ? '是' : '否';
            $money = $val['money'];

            /*
            $row = sprintf("%d,\t%s,\t%s,\t%s,%s,充值订单：\t%s,\t%s,%s,\"%s\",\"%s\",%s,%s",
                    $val['id'],$val['notice_sn'],$create_time,$pay_time,$is_paid,$order_sn,$user_name,$payment_name,$bank_name,$money,$val['outer_notice_sn'],$val['memo']
            );
            */
            $arr = array($val['id'],"".$val['notice_sn']."\t",$create_time,$pay_time,iconv("utf-8","gbk//IGNORE",$is_paid),"".$val['order_sn']."\t",iconv("utf-8","gbk//IGNORE",$val['user_name']),iconv("utf-8","gbk//IGNORE",$val['name']),iconv("utf-8","gbk//IGNORE",$val['bankname']),$money,$val['outer_notice_sn'],$val['memo']);
            $content .= implode(",",$arr). "\n";
            unset($voList[$k]);
        }
        $datatime = date("YmdHis",time());
        header("Content-Disposition: attachment; filename=payment_$datatime.csv");
        header('Cache-Control: max-age=0');
        echo $content;
        EXIT;
    }

    /**
     * 充值补单
     */
    public function supplement_order()
    {
        // 获取可用的充值平台
        $paymentList = $this->_getEnablePaymentList();
        if (empty($_POST)) {
            $this->assign('payment_list', $paymentList);
            $this->display();
            return ;
        }
        $GLOBALS['db']->startTrans();
        try {
            // 以下处理表单提交
            $paymentString = addslashes($_POST['payment_id']);
            $paymentData = explode('-', $paymentString);
            // 充值平台ID
            $payment_id = isset($paymentData[0]) ? intval($paymentData[0]) : 0;
            // 充值平台标识
            $payment_flag = isset($paymentData[1]) ? addslashes($paymentData[1]) : '';
            // 订单号
            $order_id = addslashes(trim($_POST['r6_Order']));
            if (!is_numeric($payment_id) || empty($payment_flag)) {
                throw new \Exception('充值平台参数错误');
            }
            if (empty($order_id)) {
                throw new \Exception('订单号不能为空');
            }
            // 检查充值平台是否存在
            if (empty($paymentList) || empty($paymentList[$payment_id])) {
                throw new \Exception('充值平台不存在');
            }
            $chargeOrder = $payment = array();
            // 充值平台配置信息
            $payment = $paymentList[$payment_id];
            // 充值方式名称
            $payment_name = $payment['name'];

            // 查询充值数据
            $chargeOrder = $this->_getPaymentInfo($order_id, $payment_flag, $payment_name, $payment_id);

            switch ($payment['class_name']) {
                case self::PAYMENT_YEEPAY: // 易宝支付
                    $yeepayPaymentService = new YeepayPaymentService();
                    // 调用【易宝新投资通-4.6交易接口查询】
                    $orderResult = $yeepayPaymentService->queryOrder(YeepayPaymentService::SEARCH_TYPE_BINDPAY, $order_id);
                    if (isset($orderResult['respCode']) && $orderResult['respCode'] === '00')
                    {
                        $orderResult['data']['identityid'] = $chargeOrder['user_id'];
                        switch ($orderResult['data']['status'])
                        {
                            case YeepayPaymentService::YBPAY_STATUS_FAILURE: // 失败
                            case YeepayPaymentService::YBPAY_STATUS_SUCCESS: // 成功
                                $yeepayCallbackRet = $yeepayPaymentService->payYeepayChargeCallback($orderResult['data']);
                                if (isset($yeepayCallbackRet['respCode']) && $yeepayCallbackRet['respCode'] !== '00')
                                {
                                    throw new \Exception($payment_name . '：' . $yeepayCallbackRet['respMsg']);
                                }
                                // 更新该订单的memo字段
                                !empty($orderResult['data']['requestno']) && $GLOBALS['db']->update('firstp2p_payment_notice', array('memo'=>''), "notice_sn='{$orderResult['data']['requestno']}' AND user_id={$orderResult['data']['identityid']} AND is_paid=1");
                                // 从[易宝补单重试列表]中踢出该订单ID，有可能会删空重试列表
                                $yeepayPaymentService->remRepairRetryIdByProcess($chargeOrder['id'], 1);
                                break;
                            case YeepayPaymentService::YBPAY_STATUS_ACCECPT: // 已接收
                                throw new \Exception($payment_name . '：已接收');
                                break;
                            case YeepayPaymentService::YBPAY_STATUS_ING: // 处理中
                                throw new \Exception($payment_name . '：处理中');
                                break;
                            case YeepayPaymentService::YBPAY_STATUS_FAIL: // 系统异常
                                throw new \Exception($payment_name . '：系统异常');
                            case YeepayPaymentService::YBPAY_STATUS_TIME_OUT: // 超时失败
                                throw new \Exception($payment_name . '：超时失败');
                                break;
                            default:
                                throw new \Exception($payment_name . '：未知状态');
                                break;
                        }
                        // 易宝的交易流水号
                        $outer_notice_sn = $orderResult['data']['yborderid'];
                    }else{
                        throw new \Exception($payment_name . '：' . $orderResult['respMsg']);
                    }
                    break;
                case self::PAYMENT_XFJR: // 先锋支付
                    FP::import('libs.payment.Xfjr_payment');
                    $payment_xfjr = new Xfjr_payment();
                    $result = $payment_xfjr->queryOrd($order_id);
                    if (empty($result)) {
                        throw new \Exception($payment_name . '订单不存在');
                    }
                    $ordInfo = base64_decode($result);
                    $ordInfo = iconv("UTF-8", "GB2312//IGNORE", $ordInfo);
                    $ordInfo = simplexml_load_string(stripslashes($ordInfo));
                    if (empty($ordInfo)) {
                        throw new \Exception($payment_name . '订单不存在');
                    }
                    if (!empty($ordInfo->errorCode)) {
                        throw new \Exception($payment_name . '：' . $ordInfo->errorMsg);
                    }
                    // 0-“未支付”；1-“已支付”；2-“支付失败”ß
                    switch ($ordInfo->tranStat) {
                        case '0':
                            throw new \Exception($payment_name . '：未支付');
                            break;
                        case '1':
                            break;
                        default:
                            throw new \Exception($payment_name . '：支付失败');
                            break;
                    }
                    $outer_notice_sn = trim($ordInfo->tranSerialNo); // 交易流水号

                    FP::import('libs.libs.cart');
                    // 处理收款单
                    $rs = payment_paid($chargeOrder['id']); // 付款单处理
                    if (!$rs) {
                        throw new \Exception('处理收款单失败');
                    }
                    // 同步充值订单
//                     $order_rs = order_paid($chargeOrder['order_id']);
//                     if (!$order_rs) {
//                         throw new \Exception('同步充值订单失败');
//                     }
                    break;
                case self::PAYMENT_ZJTG: // 先锋支付资金托管平台
                    require_once APP_ROOT_PATH . '/core/service/PaymentService.php';
                    $service = new core\service\PaymentService();
                    $_result = $service->chargeResultInfoQuery($order_id);
                    if (!empty($_result)) {
                        switch ($_result['orderStatus']) {
                            case '00':
                            break;
                            default:
                            $_msg = $payment_name;
                            if (!empty($_result['respMsg'])) {
                                $_msg .= ':' . $_result['respMsg'];
                            }
                            throw new \Exception($_msg);
                            break;
                        }
                    }
                    else {
                        throw new \Exception('查询支付接口失败');
                    }
                    // 修复payment_id = 4
                    $payment_id = 4;

//                     FP::import('libs.libs.cart');
//                     // 处理收款单
//                     $rs = payment_paid($chargeOrder['id']); // 付款单处理
//                     if (!$rs) {
//                         throw new \Exception('处理收款单失败');
//                     }
//                     // 同步充值订单
//                     $order_rs = order_paid($chargeOrder['order_id']);
//                     if (!$order_rs) {
//                         throw new \Exception('同步充值订单失败');
//                     }
                    // 支付成功的处理
                    $chargeService = new \core\service\ChargeService();
                    $handlResult = $chargeService->paidSuccess($chargeOrder);
                    // 先锋支付-充值成功处理成功、触发O2O请求
                    $handlResult === true && $service->chargeTriggerO2O($chargeOrder);
                    break;
                case self::PAYMENT_HKSUPERVISION: // 海口联合农商银行
                    // 根据充值订单号，调用支付的订单查询接口，查询该笔订单的状态
                    $supervisionFinanceObj = new SupervisionFinanceService();
                    $orderCheckInfo = $supervisionFinanceObj->orderSearch($order_id);
                    // 存管订单查询接口，调用失败
                    if ($orderCheckInfo['status'] == SupervisionBaseService::RESPONSE_FAILURE) {
                        throw new \Exception($payment_name . '：' . $orderCheckInfo['respMsg']);
                    }
                    // 存管系统返回的金额
                    $checkAmount = $orderCheckInfo['data']['amount'];
                    // 充值订单表的金额
                    $orderAmount = $chargeOrder['amount'];
                    if (bccomp($checkAmount, $orderAmount) != 0) {
                        throw new WXException('ERR_CHARGE_AMOUNT');
                    }

                    switch ($orderCheckInfo['data']['status'])
                    {
                        case SupervisionBaseService::RESPONSE_FAILURE: // 失败
                        case SupervisionBaseService::RESPONSE_SUCCESS: // 成功
                            $chargeCallbackRet = $supervisionFinanceObj->chargeNotify($orderCheckInfo['data']);
                            if ($chargeCallbackRet['status'] !== SupervisionBaseService::RESPONSE_SUCCESS) {
                                throw new \Exception($payment_name . '：' . $chargeCallbackRet['respMsg']);
                            }
                            // 交易流水号
                            $outer_notice_sn = trim($orderCheckInfo['data']['orderId']);
                            break;
                        case SupervisionBaseService::RESPONSE_PROCESSING: // 处理中
                        case SupervisionFinanceService::WITHDRAW_PROCESSING: // 处理中
                            throw new \Exception($payment_name . '：处理中');
                            break;
                        default:
                            throw new \Exception($payment_name . '：未知状态');
                            break;
                    }
                    break;
                default:
                    throw new \Exception('未知类型');
                    break;
            }
            // 提交事务
            $GLOBALS['db']->commit();
            // 投log
            FP::import('libs.utils.logger');
            $admin_data = es_session::get(md5(conf("AUTH_KEY")));
            $log = array(
                'type' => 'payment',
                'user_name' => 'admId:' . $admin_data['adm_id'] . 'userId:' . $chargeOrder['user_id'],
                'money' => $payment_flag === self::PAYMENT_HKSUPERVISION ? $chargeOrder['amount'] : $chargeOrder['money'],
                'notice_sn' => $payment_flag === self::PAYMENT_HKSUPERVISION ? $chargeOrder['out_order_id'] : $chargeOrder['notice_sn'],
                'outer_notice_sn' => $outer_notice_sn,
                'path' => __FILE__,
                'function' => 'paymentNotice->supplement_order',
                'msg' => "后台操作（{$payment_name}）补单",
                'time' => time(),
            );
            logger::wLog($log);
            save_log('网信理财-充值补单，会员id['.$chargeOrder['user_id'].']，订单号['.$order_id.']' . L('UPDATE_SUCCESS'), 1, [], $log);
            $this->success('操作成功', 0, "m.php?&notice_sn={$order_id}&payment_id={$payment_id}&m=PaymentNotice&a=index");
        }
        catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            $this->error($e->getMessage());
        }
    }

    /**
     * 存管系统-业务补单
     */
    public function supplement_business()
    {
        if ($this->is_cn) {
            $orderTypeList = self::$supervisionBusinessCnConfig;
        } else {
            $orderTypeList = self::$supervisionBusinessConfig;
        }
        if (empty($_POST)) {
            $this->assign('ot', (int)$_GET['ot']);
            $this->assign('orderTypeList', $orderTypeList);
            $this->display();
            return ;
        }
        $GLOBALS['db']->startTrans();
        try {
            // 业务类型
            $orderTypeId = (int)$_POST['orderTypeId'];
            // 订单号
            $orderId = addslashes(trim($_POST['orderId']));
            if (!is_numeric($orderTypeId)) {
                throw new \Exception('业务类型参数错误');
            }
            if (empty($orderId)) {
                throw new \Exception('订单号不能为空');
            }
            // 检查业务类型是否存在
            if (empty($orderTypeList) || empty($orderTypeList[$orderTypeId])) {
                throw new \Exception('充值平台不存在');
            }
            $this->assign('jumpUrl', u(MODULE_NAME . '/supplement_business', ['ot'=>$orderTypeId]));
            // 业务类型配置信息
            $orderTypeConfig = $orderTypeList[$orderTypeId];
            // 业务类型所在平台
            $orderTypePlatform = $orderTypeConfig['platform'];
            // 业务类型名称
            $orderTypeName = $orderTypeConfig['name'];
            // 业务类型回调方法
            $orderTypeNotifyMethod = $orderTypeConfig['notifyMethod'];

            switch ($orderTypeId) {
                case 0: // 充值
                case 1: // 提现
                case 2: // 网信理财账户余额划转到网贷P2P账户余额
                case 3: // 网贷P2P账户余额划转到网信理财账户余额
                    // 查询业务数据
                    $orderInfo = $this->_getBusinessInfo($orderId, $orderTypeId, $orderTypeName);
                    // 根据充值订单号，调用支付的订单查询接口，查询该笔订单的状态
                    $supervisionFinanceObj = new SupervisionFinanceService();
                    $orderCheckInfo = $supervisionFinanceObj->orderSearch($orderId);
                    // 存管订单查询接口，调用失败
                    if ($orderCheckInfo['status'] == SupervisionBaseService::RESPONSE_FAILURE) {
                        throw new \Exception($orderTypeName . '-' . $orderCheckInfo['respMsg']);
                    }
                    // 存管系统返回的金额
                    $checkAmount = $orderCheckInfo['data']['amount'];
                    // 充值订单表的金额
                    $orderAmount = $orderInfo['amount'];
                    if (bccomp($checkAmount, $orderAmount) != 0) {
                        throw new \Exception($orderTypeName . '-金额不一致');
                    }

                    switch ($orderCheckInfo['data']['status'])
                    {
                        case SupervisionBaseService::RESPONSE_FAILURE: // 失败
                        case SupervisionBaseService::RESPONSE_SUCCESS: // 成功
                            if (in_array($orderTypeId, [2, 3])) { // 余额划转的回调逻辑
                                $callbackRet = $supervisionFinanceObj->{$orderTypeNotifyMethod}($orderCheckInfo['data']['orderId'], $orderTypeConfig['businessType']);
                            } else {
                                // 提现的回调逻辑，提现表的状态是未处理
                                if ($orderTypeId == 1 && $orderInfo['withdraw_status'] == SupervisionWithdrawModel::WITHDRAW_STATUS_NORMAL) {
                                    // 需要先把提现记录，更新为处理中
                                    $tmpSupervisionData = $orderCheckInfo['data'];
                                    $tmpSupervisionData['status'] = SupervisionFinanceService::WITHDRAW_PROCESSING;
                                    $supervisionFinanceObj->{$orderTypeNotifyMethod}($tmpSupervisionData);
                                }
                                $callbackRet = $supervisionFinanceObj->{$orderTypeNotifyMethod}($orderCheckInfo['data']);
                            }
                            if ($callbackRet['status'] !== SupervisionBaseService::RESPONSE_SUCCESS) {
                                throw new \Exception($orderTypeName . '-' . $callbackRet['respMsg']);
                            }
                            // 交易流水号
                            $outer_notice_sn = trim($orderCheckInfo['data']['orderId']);
                            break;
                        case SupervisionBaseService::RESPONSE_PROCESSING: // 处理中
                        case SupervisionFinanceService::WITHDRAW_PROCESSING: // 处理中
                            throw new \Exception($orderTypeName . '-处理中');
                            break;
                        default:
                            throw new \Exception($orderTypeName . '-未知状态');
                            break;
                    }
                    break;
                case 4: // 放款
                case 5: // 还款
                case 6: // 流标
                    $orderInfo = $this->_getBusinessInfo($orderId, $orderTypeId);
                    if (empty($orderInfo)) {
                        throw new WXException('ERR_OUT_ORDER_NOT_EXIST');
                    }

                    $supervisionFinanceObj = new SupervisionFinanceService();
                    $orderCheckInfo = $supervisionFinanceObj->batchOrderSearch($orderId, $orderTypeConfig['businessType']);
                    // 存管订单查询接口，调用失败
                    if ($orderCheckInfo['status'] == SupervisionBaseService::RESPONSE_FAILURE) {
                        throw new \Exception($orderTypeName . '-' . $orderCheckInfo['respMsg']);
                    }

                    $supervisionDealObj = new SupervisionDealService();
                    switch ($orderCheckInfo['data']['status'])
                    {
                        case SupervisionBaseService::RESPONSE_FAILURE: // 失败
                        case SupervisionBaseService::RESPONSE_SUCCESS: // 成功
                            $callbackRet = $supervisionDealObj->{$orderTypeNotifyMethod}($orderCheckInfo['data']);
                            if ($callbackRet['status'] !== SupervisionBaseService::RESPONSE_SUCCESS) {
                                throw new \Exception($orderTypeName . '-' . $callbackRet['respMsg']);
                            }
                            // 交易流水号
                            $outer_notice_sn = trim($orderCheckInfo['data']['orderId']);
                            break;
                        case SupervisionBaseService::RESPONSE_PROCESSING: // 处理中
                        case SupervisionFinanceService::WITHDRAW_PROCESSING: // 处理中
                            throw new \Exception($orderTypeName . '-处理中');
                            break;
                        default:
                            throw new \Exception($orderTypeName . '-未知状态');
                            break;
                    }
                    break;
                default:
                    throw new \Exception($orderTypeName . '-未知类型');
                    break;
            }
            // 提交事务
            $GLOBALS['db']->commit();
            // 投log
            FP::import('libs.utils.logger');
            $admin_data = es_session::get(md5(conf("AUTH_KEY")));
            $log = array(
                'type' => 'payment',
                'user_name' => 'admId:' . $admin_data['adm_id'] . ',userId:' . $orderInfo['user_id'],
                'money' => $orderInfo['amount'],
                'notice_sn' => $orderInfo['out_order_id'],
                'outer_notice_sn' => $outer_notice_sn,
                'path' => __FILE__,
                'function' => 'paymentNotice->supplement_business',
                'msg' => "后台操作（存管系统-{$orderTypeName}）补单",
                'time' => time(),
            );
            logger::wLog($log);
            save_log('存管系统-业务补单，会员id['.$orderInfo['user_id'].']，订单号['.$orderId.']' . L('UPDATE_SUCCESS'), 1, [], $log);
            $this->success('操作成功');
        }catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            $this->error($orderTypePlatform . '：' . $e->getMessage());
        }
    }

    /**
     * 获取可用的充值平台
     * @param int $paymentId
     */
    private function _getEnablePaymentList($paymentId = 0) {
        $enablePaymentList = [];
        $paymentList = M('Payment')->findAll();
        $paymentList[] = self::$supervisionPaymentConfig;
        foreach ($paymentList as $key => $item) {
            if ($item['is_effect'] != 1 || !in_array($item['class_name'], self::$enablePaymentName)) {
                continue;
            }
            $enablePaymentList[$item['id']] = $item;
        }
        if ($paymentId > 0) {
            return !empty($enablePaymentList[$paymentId]) ? true : false;
        }
        return $enablePaymentList;
    }

    /**
     * 查询充值数据
     * @param string $orderId
     * @param string $paymentFlag
     * @param string $paymentName
     * @throws \Exception
     */
    private function _getPaymentInfo($orderId, $paymentFlag, $paymentName, $paymentId = 0) {
        $chargeOrder = [];
        switch ($paymentFlag) {
            case self::PAYMENT_HKSUPERVISION: // 海口联合农商银行
                // 查询【海口联合农商银行】的订单
                $chargeOrder = SupervisionChargeModel::instance()->getChargeRecordByOutId($orderId);
                if (empty($chargeOrder)) {
                    throw new WXException('ERR_OUT_ORDER_NOT_EXIST');
                }
                if ($chargeOrder['pay_status'] == SupervisionChargeModel::PAY_STATUS_SUCCESS) {
                    throw new \Exception($paymentName . '：收款订单已经支付成功');
                }
                // 充值订单已经失败
                if ($chargeOrder['pay_status'] == SupervisionChargeModel::PAY_STATUS_FAILURE) {
                    throw new \Exception($paymentName . '：收款订单已经支付失败');
                }
                break;
            case self::PAYMENT_YEEPAY: // 易宝支付
            case self::PAYMENT_XFJR: // 先锋支付
                // 查询【易宝支付、先锋支付】的订单
                $_payment_notice_sql = 'SELECT * FROM ' . DB_PREFIX . "payment_notice WHERE notice_sn = '{$orderId}' AND payment_id = '{$paymentId}'";
                $chargeOrder = $GLOBALS['db']->getRow($_payment_notice_sql);
                if (empty($chargeOrder)) {
                    throw new \Exception($paymentName . '：收款订单号不存在');
                }
                if ($chargeOrder['is_paid'] == PaymentNoticeModel::IS_PAID_SUCCESS) {
                    throw new \Exception($paymentName . '：收款订单已经支付成功');
                }
                // 充值订单已经失败
                if ($chargeOrder['is_paid'] == PaymentNoticeModel::IS_PAID_FAIL) {
                    throw new \Exception($paymentName . '：收款订单已经支付失败');
                }
                break;
            case self::PAYMENT_ZJTG: // 先锋支付资金托管平台
                // 查询【先锋支付资金托管平台】的订单
                $_payment_notice_sql = 'SELECT * FROM ' . DB_PREFIX . "payment_notice WHERE notice_sn = '{$orderId}'";
                $chargeOrder = $GLOBALS['db']->getRow($_payment_notice_sql);
                if (empty($chargeOrder)) {
                    throw new \Exception($paymentName . '：收款订单号不存在');
                }
                if ($chargeOrder['is_paid'] == PaymentNoticeModel::IS_PAID_SUCCESS) {
                    throw new \Exception($paymentName . '：收款订单已经支付成功');
                }
                // 充值订单已经失败
                if ($chargeOrder['is_paid'] == PaymentNoticeModel::IS_PAID_FAIL) {
                    throw new \Exception($paymentName . '：收款订单已经支付失败');
                }
                break;
        }
        return $chargeOrder;
    }

    /**
     * 根据业务订单号，查询存管系统数据表数据
     * @param int $orderId 业务订单号
     * @param int $orderTypeId 业务类型ID
     * @param string $orderTypeName 业务类型名称
     */
    private function _getBusinessInfo($orderId, $orderTypeId, $orderTypeName = '') {
        $data = [];
        switch ($orderTypeId) {
            case 0: // 充值
                $data = SupervisionChargeModel::instance()->getChargeRecordByOutId($orderId);
                if (empty($data)) {
                    throw new WXException('ERR_OUT_ORDER_NOT_EXIST');
                }
                // 订单状态已经成功
                if ($data['pay_status'] == SupervisionChargeModel::PAY_STATUS_SUCCESS) {
                    throw new \Exception($orderTypeName . '-该订单已经支付成功');
                }
                // 订单状态已经失败
                if ($data['pay_status'] == SupervisionChargeModel::PAY_STATUS_FAILURE) {
                    throw new \Exception($orderTypeName . '-该订单已经支付失败');
                }
                break;
            case 1: // 提现
                $data = SupervisionWithdrawModel::instance()->getWithdrawRecordByOutId($orderId);
                if (empty($data)) {
                    throw new WXException('ERR_OUT_ORDER_NOT_EXIST');
                }
                // 订单状态已经成功
                if ($data['withdraw_status'] == SupervisionWithdrawModel::WITHDRAW_STATUS_SUCCESS) {
                    throw new \Exception($orderTypeName . '-该订单已经提现成功');
                }
                // 订单状态已经失败
                if ($data['withdraw_status'] == SupervisionWithdrawModel::WITHDRAW_STATUS_FAILED) {
                    throw new \Exception($orderTypeName . '-该订单已经提现失败');
                }
                break;
            case 2: // 网信理财账户余额划转到网贷P2P账户余额
            case 3: // 网贷P2P账户余额划转到网信理财账户余额
                $data = SupervisionTransferModel::instance()->getTransferRecordByOutId($orderId);
                if (empty($data)) {
                    throw new WXException('ERR_OUT_ORDER_NOT_EXIST');
                }
                // 订单状态已经成功
                if ($data['transfer_status'] == SupervisionTransferModel::TRANSFER_STATUS_SUCCESS) {
                    throw new \Exception($orderTypeName . '-该订单已经提现成功');
                }
                // 订单状态已经失败
                if ($data['transfer_status'] == SupervisionTransferModel::TRANSFER_STATUS_FAILURE) {
                    throw new \Exception($orderTypeName . '-该订单已经提现失败');
                }
                break;
            case 4: // 放款
            case 5: // 还款
                $data = P2pIdempotentService::getInfoByOrderId($orderId);
                if (empty($data)) {
                    throw new WXException('ERR_OUT_ORDER_NOT_EXIST');
                }
                break;
            case 6: // 流标
                $data = P2pIdempotentService::getCancelOrderByDealId($orderId);
                if (empty($data)) {
                    throw new WXException('ERR_OUT_ORDER_NOT_EXIST');
                }
                break;
        }
        return $data;
    }

    /**
     * 本地充值限额列表
     */
    public function limit_list() {
        $channel = !empty($_REQUEST['channel']) ? addslashes($_REQUEST['channel']) : PaymentNoticeModel::CHARGE_QUICK_CHANNEL;
        // 获取指定充值渠道的银行限额列表，单位元
        $limitList = PhSupervisionService::getPhChargeLimitList($channel);
        if (!empty($limitList)) {
            foreach ($limitList as $key => $item) {
                if ((int)$item['max_quota'] >= 0) {
                    $limitList[$key]['max_quota'] = !empty($item['max_quota']) ? (int)$item['max_quota'] . '元' : '';
                } else {
                    $limitList[$key]['max_quota'] = '无限额';
                }
                if ((int)$item['day_quota'] >= 0) {
                    $limitList[$key]['day_quota'] = !empty($item['day_quota']) ? (int)$item['day_quota'] . '元' : '';
                } else {
                    $limitList[$key]['day_quota'] = '无限额';
                }
                if ((int)$item['month_quota'] >= 0) {
                    $limitList[$key]['month_quota'] = !empty($item['month_quota']) ? (int)$item['month_quota'] . '元' : '';
                } else {
                    $limitList[$key]['month_quota'] = '无限额';
                }
                $limitList[$key]['limit_intro'] = nl2br($item['limit_intro']);
            }
        }

        // 获取充值渠道
        foreach (PaymentNoticeModel::$chargeChannelConfig as $key => $val) {
            $channelList[] = ['id'=>$key, 'name'=>$val];
        }
        $this->assign('channel_list', $channelList);

        $this->assign('main_title', '充值限额列表');
        $this->assign('limit_list', $limitList);
        parent::index();
    }

    public function limit_add() {
        // 渠道名称列表
        $this->assign('charge_channel_list', PaymentNoticeModel::$chargeChannelConfig);
        // 获取银行名称、银行简码
        $bankMap = [];
        $bankList = BankModel::instance()->getAllByStatusOrderByRecSortId(0, true);
        if (!empty($bankList)) {
            $existBank = [];
            foreach ($bankList as $item) {
                if (empty($item['short_name'])) continue;
                if (isset($existBank[$item['short_name']])) continue;
                $existBank[$item['short_name']] = 1;
                $bankMap[] = ['name'=>$item['name'], 'code'=>$item['short_name']];
            }
            unset($bankList);
        }
        $this->assign('bank_map', $bankMap);
        $this->display();
    }

    public function limit_edit() {
        $channel = !empty($_REQUEST['channel']) ? addslashes($_REQUEST['channel']) : '';
        if (empty($channel)) {
            $this->error('参数错误');
        }
        $code = !empty($_REQUEST['code']) ? addslashes($_REQUEST['code']) : '';
        if (empty($code)) {
            $this->error('参数错误');
        }

        $limitInfo = PhSupervisionService::getPhChargeLimitOne($channel, $code);
        if (empty($limitInfo)) {
            $this->error('暂无限额数据');
        }
        if ((int)$limitInfo['min_quota'] >= 0) {
            $limitInfo['min_quota'] = !empty($limitInfo['min_quota']) ? bcdiv($limitInfo['min_quota'], 100, 0) : '';
        }else{
            $limitInfo['min_quota'] = -1;
        }
        if ((int)$limitInfo['max_quota'] >= 0) {
            $limitInfo['max_quota'] = !empty($limitInfo['max_quota']) ? bcdiv($limitInfo['max_quota'], 100, 0) : '';
        }else{
            $limitInfo['max_quota'] = -1;
        }
        if ((int)$limitInfo['day_quota'] >= 0) {
            $limitInfo['day_quota'] = !empty($limitInfo['day_quota']) ? bcdiv($limitInfo['day_quota'], 100, 0) : '';
        }else{
            $limitInfo['day_quota'] = -1;
        }
        if ((int)$limitInfo['month_quota'] >= 0) {
            $limitInfo['month_quota'] = !empty($limitInfo['month_quota']) ? bcdiv($limitInfo['month_quota'], 100, 0) : '';
        }else{
            $limitInfo['month_quota'] = -1;
        }
        $limitJson = !empty($limitInfo['limit_json']) ? $limitInfo['limit_json'] : '[]';
        $limitStep = json_decode($limitJson, true);
        $this->assign('limitStep', $limitStep);
        $this->assign('vo', $limitInfo);

        // 渠道名称列表
        $this->assign('charge_channel_list', PaymentNoticeModel::$chargeChannelConfig);
        // 获取银行名称、银行简码
        $bankMap = [];
        $bankList = BankModel::instance()->getAllByStatusOrderByRecSortId(0, true);
        if (!empty($bankList)) {
            $existBank = [];
            foreach ($bankList as $item) {
                if (empty($item['short_name'])) continue;
                if (isset($existBank[$item['short_name']])) continue;
                $existBank[$item['short_name']] = 1;
                $bankMap[] = ['name'=>$item['name'], 'code'=>$item['short_name']];
            }
            unset($bankList);
        }
        $this->assign('bank_map', $bankMap);
        $this->display();
    }

    public function limit_update() {
        $limitData = [];
        $limitData['id'] = !empty($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
        // 充值渠道
        $limitData['payChannel'] = !empty($_REQUEST['pay_channel']) ? addslashes($_REQUEST['pay_channel']) : PaymentNoticeModel::CHARGE_QUICK_CHANNEL;
        // 充值类型
        if (empty(PaymentNoticeModel::$chargeTypeConfig[$limitData['payChannel']])) {
            $this->error('参数错误', 0);
        }
        $limitData['type'] = PaymentNoticeModel::$chargeTypeConfig[$limitData['payChannel']];
        // 银行名称
        $limitData['bankName'] = !empty($_REQUEST['name']) ? addslashes($_REQUEST['name']) : '';
        // 银行简码
        $limitData['bankCode'] = !empty($_REQUEST['code']) ? addslashes($_REQUEST['code']) : '';
        // 单笔限额
        if (strlen($_REQUEST['max_quota']) > 0 && bccomp($_REQUEST['max_quota'], '0.00', 2) == 0) {
            $this->error('单笔限额不能为0', 0);
        }
        $limitData['maximumQuota'] = bccomp($_REQUEST['max_quota'], '0.00', 2) >= 0 ? intval(bcmul($_REQUEST['max_quota'], 100, 0)) : -1;
        // 当日限额
        if (strlen($_REQUEST['day_quota']) > 0 && bccomp($_REQUEST['day_quota'], '0.00', 2) == 0) {
            $this->error('当日限额不能为0', 0);
        }
        $limitData['dayQuota'] = bccomp($_REQUEST['day_quota'], '0.00', 2) >= 0 ? intval(bcmul($_REQUEST['day_quota'], 100, 0)) : -1;
        // 当月限额
        if (strlen($_REQUEST['month_quota']) > 0 && bccomp($_REQUEST['month_quota'], '0.00', 2) == 0) {
            $this->error('当月限额不能为0', 0);
        }
        $limitData['monthQuota'] = bccomp($_REQUEST['month_quota'], '0.00', 2) >= 0 ? intval(bcmul($_REQUEST['month_quota'], 100, 0)) : -1;
        // 限额描述
        $limitData['limitIntro'] = !empty($_REQUEST['limit_intro']) ? addslashes($_REQUEST['limit_intro']) : '';

        if ($limitData['maximumQuota'] >= 0) {
            // 限制阶梯
            $min = !empty($_REQUEST['min']) ? $_REQUEST['min'] : [];
            $max = !empty($_REQUEST['max']) ? $_REQUEST['max'] : [];
            $single = !empty($_REQUEST['single']) ? $_REQUEST['single'] : [];
            if (count($min) != count($max) || count($max) != count($single)) {
                $this->error('限额阶梯数据不正确', 0);
            }
            $limitStep = [];
            foreach ($min as $key => $val) {
                $limitStep[] = [
                    'min' => bcmul($val, 100), //格式化分
                    'max' => bcmul($max[$key], 100),
                    'single' => bcmul($single[$key], 100),
                ];
            }
            $limitData['limitJson'] = $limitStep ? json_encode($limitStep) : '';
        } else {
            // 无限额的时候，需要同时把阶梯限额清空
            $limitData['limitJson'] = '';
        }

        // 新增限额
        if (empty($limitData['id'])) {
            $successTips = L("INSERT_SUCCESS");
            $failTips = L("INSERT_FAILED");
        }else{
            $successTips = L("UPDATE_SUCCESS");
            $failTips = L("UPDATE_FAILED");
        }
        $log_info = json_encode($limitData);
        $limitRet = PhSupervisionService::setPhChargeLimit($limitData['payChannel'], $limitData['bankCode'], $limitData['type'], $limitData);
        if ($limitRet) {
            //成功提示
            save_log($log_info . $successTips, 1);
            $this->assign("jumpUrl", u(MODULE_NAME . "/limit_list"));
            $this->success($successTips);
        } else {
            //错误提示
            save_log($log_info . $failTips, 0);
            $this->error($failTips, 0);
        }
    }

    /**
     * 彻底删除限额记录
     */
    public function foreverdelete() {
        $ajax = intval($_REQUEST['ajax']);
        $id = !empty($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
        if (empty($id)) {
            $this->error(l("FOREVER_DELETE_FAILED"), $ajax);
        }

        $log_info = json_encode(['id'=>$id, 'ajax'=>$ajax]);
        $ret = PhSupervisionService::delPhChargeLimitOne($id);
        if ($rs !== false) {
            save_log($log_info . l("FOREVER_DELETE_SUCCESS"), 1);
            $this->display_success(l("FOREVER_DELETE_SUCCESS"), $ajax);
        } else {
            save_log($log_info . l("FOREVER_DELETE_FAILED"), 0);
            $this->error(l("FOREVER_DELETE_FAILED"), $ajax);
        }
    }

    /**
     * 充值方式系统配置
     */
    public function conf() {
        $siteId = 0;
        $title = isset($_REQUEST['title']) ? addslashes(trim($_REQUEST['title'])) : '';

        $model = M('Conf');
        //$condition = $this->_search();

        $condition = "site_id='{$siteId}'";
        if ($title !== '') {
            $condition .= "AND (name LIKE '%{$title}%' OR title LIKE '%{$title}%')";
        }

        //充值方式系统配置表
        $confList = trim(app_conf('CHARGE_METHOD_CONF_LIST'));
        $confArr = explode(',', $confList);
        $confArr[] = 'CHARGE_METHOD_CONF_LIST';
        $confArr = array_map('trim', $confArr);
        $condition .= sprintf(" AND name in ('%s')", implode("','", $confArr));

        $_REQUEST['_sort'] = 1;
        $_REQUEST['listRows'] = 1000;

        if (!empty ($model)) {
            $this->_list($model, $condition);
        }
        $list = $this->get('list');

        foreach ($list as $k => $item) {
            $list[$k]['input_type'] = self::$input_type_list[$item['input_type']];
        }

        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        if ($redis) {
            $lastUpdateTime = $redis->get('conf_last_update_time');
        }

        $this->assign('list', $list);
        $this->assign('lastUpdateTime', $lastUpdateTime);
        $this->assign("main_title", '充值方式系统配置');
        $this->display();

    }

    private function check_name() {
        $name = trim($_REQUEST['name']);
        $result = ereg("^[A-Z0-9_]+$", $name);
        if (empty($result)) {
            return false;
        }
        $condition['name'] = $name;
        $id = trim($_REQUEST['id']);
        if (isset($_REQUEST['site_id'])) {
            $condition['site_id'] = $_REQUEST['site_id'];
        }
        $exist_conf = M('Conf')->where($condition)->findAll();
        if (empty($exist_conf)) {
            return true;
        } else if (empty($id)) { // 新增
            return false;
        } else { // 编辑
            foreach ($exist_conf as $exist_item) {
                if ($exist_item['id'] != $id) {
                    return false;
                }
            }
            return true;
        }
    }

    /**
     * 添加配置
     */
    public function conf_add() {
        $model = M('Conf');
        //充值方式系统配置表
        $confList = trim(app_conf('CHARGE_METHOD_CONF_LIST'));
        $confArr = explode(',', $confList);

        if (isset($_SERVER['REQUEST_METHOD']) && strtolower($_SERVER['REQUEST_METHOD']) === 'post') {
            $data = $model->create();
            if (empty($data['title']) || empty($data['name'])) {
                $this->error('缺少参数');
            }
            if (!$this->check_name($data['name'])) {
                $this->error('键名由大写字母,数字,下划线组成,且不能与现有的键名重复');
            }
            if ($data['name'] != 'CHARGE_METHOD_CONF_LIST' && !in_array($data['name'], $confArr)) {
                $this->error('请在CHARGE_METHOD_CONF_LIST中加入键名');
            }
            $model->__set('is_effect', 1);
            $model->__set('is_conf', 1);
            $model->add();
            $this->success('添加成功');
        }
        $this->display();
    }

    /**
     * 更新配置
     */
    public function conf_edit() {
        $model = M('Conf');
        //充值方式系统配置表
        $confList = trim(app_conf('CHARGE_METHOD_CONF_LIST'));
        $confArr = explode(',', $confList);

        $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        $vo = $model->getById($id);
        if (isset($_SERVER['REQUEST_METHOD']) && strtolower($_SERVER['REQUEST_METHOD']) === 'post') {
            $data = $model->create();
            if (empty($data['id']) || empty($data['title']) || empty($data['name'])) {
                $this->error('缺少参数');
            }
            if ($data['name'] != $vo['name'] && !$this->check_name($data['name'])) {
                $this->error('键名由大写字母,数字,下划线组成,且不能与现有的键名重复');
            }
            if ($data['name'] != 'CHARGE_METHOD_CONF_LIST' && !in_array($data['name'], $confArr)) {
                $this->error('请在CHARGE_METHOD_CONF_LIST中加入键名');
            }
            $model->save();
            $this->success('更新成功');
        }
        $this->assign('vo', $vo);
        $this->assign('input_type_list', self::$input_type_list);
        $this->display();
    }

    /**
     * 删除配置
     */
    public function conf_del() {
        $model = M('Conf');
        $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        if (empty($id)) {
            $this->error('缺少参数');
        }
        $condition = array('id' => array('in', explode(',', $id)));
        $model->where($condition)->delete();
        $this->success('删除成功');
    }

}
