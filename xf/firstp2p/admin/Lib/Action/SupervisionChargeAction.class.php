<?php

use libs\utils\PaymentApi;
use core\service\UserService;
use core\service\SupervisionAccountService;
use libs\common\WXException;
use core\dao\SupervisionChargeModel;
use NCFGroup\Common\Library\Idworker;
use core\dao\PaymentNoticeModel;

ini_set('memory_limit', '2048M');
//ini_set('display_errors', 1);
//error_reporting(E_ALL);

class SupervisionChargeAction extends CommonAction
{

    public function index()
    {
        $condition = array();
        if(isset($_GET['pay_status'])) {
            $condition['pay_status'] =  intval($_GET['pay_status']);
            $status = isset($_GET['pay_status']) ? intval($_GET['pay_status']) : 0;
        }
        if(trim($_REQUEST['out_order_id'])!=''){
            $condition['out_order_id'] = $_REQUEST['out_order_id'];
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
            $pay_start = strtotime($_REQUEST['pay_time_start']);
            $condition['update_time'] = array('egt', $pay_start);
        }

        if (!empty($_REQUEST['pay_time_end'])) {
            $pay_end = strtotime($_REQUEST['pay_time_end']);
            $condition['update_time'] = array('between', sprintf('%s,%s', $pay_start, $pay_end));
        }


        $this->assign('main_title', '充值订单列表');
        $this->assign("default_map",$condition);
        $p = isset($_GET['p']) ? intval($_GET['p']) : 1;
        // 不再计算列表页总条数
        $this->_setPageEnable(false);
        $this->_list(MI('SupervisionCharge'), $condition);
        $this->assign('pay_status', $status);
        $this->assign('p', $p);
        $this->assign('charge_map', self::getChargeMap());
        $this->display('index');
    }

    public function export_payment() {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        $where_arr = array();

//         if (trim($_REQUEST['order_sn']) != '') {
//             $order_id = MI("DealOrder")->where("order_sn='" . trim($_REQUEST['order_sn']) . "'")->getField("id");
//             $where_arr[] = "pn.order_id = '".$order_id."'";
//         }

        if (trim($_REQUEST['out_order_id']) != '') {
            $where_arr[] = "sc.out_order_id = '".trim($_REQUEST['out_order_id'])."'";
        }

        $pay_time_start = strtotime($_REQUEST['pay_time_start']);
        $pay_time_end = strtotime($_REQUEST['pay_time_end']);
        if($pay_time_start){
            $where_arr[] = "sc.update_time >= ".$pay_time_start;
        }
        if($pay_time_end){
            $where_arr[] = "sc.update_time <= ".$pay_time_end;
        }

        // 会员名称，精确匹配，避免慢查询
        if(trim($_REQUEST['user_name']) != '')
        {
            $uid = MI('User')->where(array('user_name'=>array('eq', trim($_REQUEST['user_name']))))->getField('id');
            $where_arr[] = " sc.user_id = '{$uid}' ";
        }

        $user_num = trim($_REQUEST['user_num']);
        if($user_num){
            $uid = de32Tonum($user_num);
            $where_arr[] = " sc.user_id = '{$uid}' ";
        }

        //$where_str = $where_arr ? ' AND '.implode(' AND ', $where_arr) : '';

        //$sql = 'SELECT pn.*,o.order_sn AS order_sn, u.user_name, p.name, bc.name AS bankname FROM firstp2p_payment_notice pn, firstp2p_user u, firstp2p_payment p, firstp2p_deal_order o, firstp2p_bank_charge bc WHERE pn.user_id=u.id AND pn.payment_id=p.id AND pn.order_id=o.id AND bc.short_name=o.bank_id '.$where_str.' ORDER BY pn.id DESC';

        $where_str = $where_arr ? ' where '.implode(' AND ', $where_arr) : '';
        //$sql = 'SELECT pn.*,o.order_sn AS order_sn,o.payment_id,o.bank_id,u.user_name, p.name FROM firstp2p_payment_notice pn left join firstp2p_user u on pn.user_id=u.id left join firstp2p_payment p on pn.payment_id=p.id left join  firstp2p_deal_order o on pn.order_id=o.id '.$where_str.' ORDER BY pn.id DESC';
        $sql = 'SELECT sc.*,u.user_name FROM firstp2p_supervision_charge sc LEFT JOIN firstp2p_user u on sc.user_id=u.id '.$where_str.' ORDER BY sc.id DESC';

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
        $head = array("编号", "付款单号", "创建时间", "支付时间", "支付状态", "会员名称", "付款单金额");
        foreach ($head as &$item) {
            $item = iconv("utf-8", "gbk//IGNORE", $item);
        }
        fputcsv($fp, $head);

        while($val = $GLOBALS['db']->fetchRow($res)) {
            $create_time = date('Y-m-d H:i:s', $val['create_time']);

            $is_paid = '';
            $pay_time = '-';
            if($val['pay_status'] == \core\dao\SupervisionChargeModel::PAY_STATUS_NORMAL){
                $is_paid = '未支付';
            }else if ($val['pay_status'] == SupervisionChargeModel::PAY_STATUS_SUCCESS){
                $pay_time = date('Y-m-d H:i:s',$val['update_time']);
                $is_paid =  '支付成功';
            } else if ($val['pay_status'] == SupervisionChargeModel::PAY_STATUS_FAILURE) {
                $is_paid = '支付失败';
                $pay_time = date('Y-m-d H:i:s',$val['update_time']);
            }
            $money = sprintf("%.2f",bcdiv($val['amount'], 100, 2));

            $count++;
            if ($count % $limit == 0) { //刷新一下输出buffer，防止由于数据过多造成问题
                ob_flush();
                flush();
                $count = 0;
            }
            $arr = array(
                    $val['id'],
                    "" . $val['out_order_id'] . "\t",
                    $create_time,
                    $pay_time,
                    $is_paid,
                    $val['user_name'],
                    $money,
            );
            foreach ($arr as &$item) {
                $item = iconv("utf-8", "gbk//IGNORE", $item);
            }
            fputcsv($fp, $arr);
        }
        EXIT;
    }

    /**
     * 代扣充值列表
     */
    public function autorecharge_index()
    {
        $condition = array('platform'=>PaymentNoticeModel::PLATFORM_SUPERVISION_AUTORECHARGE);
        // 充值订单号
        if (trim($_REQUEST['out_order_id']) != '') {
            $condition['out_order_id'] = addslashes($_REQUEST['out_order_id']);
        }
        // 会员ID
        if (!empty($_REQUEST['user_id'])) {
            $condition['user_id'] = array('eq', (int)$_REQUEST['user_id']);
            $this->assign('user_id', (int)$_REQUEST['user_id'] );
        }
        // 会员名称，精确匹配，避免慢查询
        if (trim($_REQUEST['user_name']) != '') {
            $uid = MI('User')->where(array('user_name'=>array('eq', trim($_REQUEST['user_name']))))->getField('id');
            $condition['user_id'] = array('eq', $uid);
            $this->assign('user_name', $_REQUEST['user_name'] );
            unset($_REQUEST['user_name']);
        }
        // 会员编号
        $user_num = trim($_REQUEST['user_num']);
        if ($user_num) {
            $condition['user_id'] = array('eq', de32Tonum($user_num));
        }
        // 付款时间
        $pay_start = $pay_end = 0;
        if (!empty($_REQUEST['pay_time_start'])) {
            $pay_start = strtotime($_REQUEST['pay_time_start']);
            $condition['update_time'] = array('egt', $pay_start);
        }
        if (!empty($_REQUEST['pay_time_end'])) {
            $pay_end = strtotime($_REQUEST['pay_time_end']);
            $condition['update_time'] = array('between', sprintf('%s,%s', $pay_start, $pay_end));
        }

        $this->assign('main_title', '代扣充值列表');
        $this->assign('default_map', $condition);
        // 不再计算列表页总条数
        $this->_setPageEnable(false);
        $this->_list(MI('SupervisionCharge'), $condition);
        $p = isset($_GET['p']) ? (int)$_GET['p'] : 1;
        $this->assign('p', $p);
        $this->display('autorecharge_index');
    }

    /**
     * 获取充值来源映射配置
     * @return array
     */
    public static function getChargeMap() {
        return [
            PaymentNoticeModel::PLATFORM_WEB => 'WEB',
            PaymentNoticeModel::PLATFORM_ANDROID => 'ANDROID',
            PaymentNoticeModel::PLATFORM_IOS => 'IOS',
            PaymentNoticeModel::PLATFORM_MOBILEWEB => 'WAP',
            PaymentNoticeModel::PLATFORM_OFFLINE => '线下充值',
            PaymentNoticeModel::PLATFORM_MOBILEWEB => 'WAP',
            PaymentNoticeModel::PLATFORM_H5 => 'H5',
            PaymentNoticeModel::PLATFORM_AUTHCARD => '绑卡认证',
            PaymentNoticeModel::PLATFORM_FUND_REDEEM => '基金赎回',
            PaymentNoticeModel::PLATFORM_LCS => '理财师客户端',
            PaymentNoticeModel::PLATFORM_SUPERVISION => '存管',
            PaymentNoticeModel::PLATFORM_SUPERVISION_AUTORECHARGE => '存管代扣充值',
            PaymentNoticeModel::PLATFORM_OFFLINE_V2 => '大额充值',
        ];
    }
}