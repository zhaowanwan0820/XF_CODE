<?php
/**
 * DealOrderService class file.
 * @author 王一鸣<wangyiming@ucfgroup.com>
 **/

namespace core\service;
use core\dao\DealOrderModel;
use core\dao\PaymentModel;
use core\dao\PaymentNoticeModel; 

/**
 * DealOrderService
 */
class DealOrderService extends BaseService {

    /**
     * 生成一条充值记录
     * @param int $payment_id
     * @param float $money 充值金额
     * @return 0|bool
     */
    public function createDealOrder($payment_id, $money, $bank_id, $platform = PaymentNoticeModel::PLATFORM_WEB) {
        $payment_info = PaymentModel::instance()->find($payment_id);
        if (!$payment_info) {
            return 0;
        }

        $deal_order_dao = new DealOrderModel();
        //记录类型：1代表什么？
        $deal_order_dao->type = 1;
        $deal_order_dao->user_id = $GLOBALS['user_info']['id'];
        $deal_order_dao->create_time = get_gmtime();

        // 定额收取手续费--走此分支
        if($payment_info['fee_type'] == 0) {
            $deal_order_dao->total_price = $money + $payment_info['fee_amount'];
        } else {
            $handling_charge = $payment_info['max_fee'];
            $fm = round($money * $payment_info['fee_amount'] / ( 1 - $payment_info['fee_amount']), 2); // 收取的手续费
            if($fm > $handling_charge) {
                $fm = $handling_charge;
                $total_price = $money + $fm;
            } else {
                $order['total_price'] = $money / (1 - $payment_info['fee_amount']); // 支付总额
                $total_price = $money / (1 - $payment_info['fee_amount']); // 支付总额
            }
            $deal_order_dao->total_price = round($total_price, 2);
        }
        //订单待处理总额？
        $deal_order_dao->deal_total_price = $money;
        //支付金额？？
        $deal_order_dao->pay_amount = 0;
        //支付状态？
        $deal_order_dao->pay_status = 0;
        //配送状态（废弃）
        $deal_order_dao->delivery_status = 5;
        //订单状态??
        $deal_order_dao->order_status = 0;
        //支付ID
        $deal_order_dao->payment_id = $payment_id;
        //银行ID（废弃？？）
        $deal_order_dao->bank_id = $bank_id;


        if ($payment_info['fee_type'] == 0) {
            //如果定额，配置的手续费给订单赋值
            $deal_order_dao->payment_fee = $payment_info['fee_amount'];
        } else {
            $deal_order_dao->payment_fee = $fm;
        }
        try{
            $GLOBALS['db']->startTrans();
            //订单号规则：当前时间（年月日时分秒）+随机数
            $order_sn = date('YmdHis').mt_rand(1000000, 9999999);
//             $deal_order_dao->order_sn = $order_sn;
//             $deal_order_dao->save();
            $order_id = 0;//$deal_order_dao->id;
//             if (empty($order_id)){
//                 throw new \Exception("创建充值订单失败");
//             }
            \FP::import("libs.libs.cart");
            //创建支付单
            $payment_notice_id = make_payment_notice($deal_order_dao->total_price, $order_id, $payment_info['id'], '', $platform, $order_sn, $GLOBALS['user_info']['id']);
            if (empty($payment_notice_id)) {
                throw new \Exception("创建支付单失败");
            } 
            $GLOBALS['db']->commit();
            //支付同步
            return array(
                        'code'=>0,//order_paid($order_id),
                        'result'=>array('order_id'=>$order_id,'payment_notice_id'=>$payment_notice_id)
                    );
        } catch (\Exception $e){ 

            $GLOBALS['db']->rollback();
        }  
        //1为异常场景
        return array('code'=>1,'result'=>array());
       
    }
}
