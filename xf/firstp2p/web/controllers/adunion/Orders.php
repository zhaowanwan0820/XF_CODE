<?php

namespace web\controllers\adunion;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\AdunionDealServive;

class Orders extends BaseAction {

    public function init() {
        $this->form = new Form("get");

        $this->form->rules = array(
            'cn'    => array('filter' => 'string'),
            'stime' => array('filter' => 'int'),
            'etime' => array('filter' => 'int'),
            'sn'    => array('filter' => 'string'),
            'union' => array('filter' => 'int'),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {

        $data = $this->form->data;
        $cn = htmlspecialchars($data['cn']);
        $stime = intval($data['stime']);
        $etime = intval($data['etime']);
        $sn = htmlspecialchars($data['sn']);
        $limit = strtotime('-180 days');
        $union = intval($data['union']);

        //默认时间为1个月
        if (0 >= $etime){
            $etime = get_gmtime();
            $stime = $etime - 30 * 24 * 60 * 60;
        }

        if (!$cn) {
            return $this->output(array('error_code' => '10001', 'error_msg' => '请指定联盟id'));
        }

        foreach(array($stime, $etime) as $time) {
            if ($time) {
                if (intval($time) < $limit) {
                    return $this->output(array('error_code' => '10002', 'error_msg' => '时间范围错误，需在6个月以内'));
                }
            }
        }

        $deal_list = $this->rpc->local("AdunionDealService\getAdDealList", array($cn, $stime, $etime, $sn));

        $uid_euid_map = array();
        if ($union) { //取注册euid
            $uids = array();
            foreach ($deal_list as $item) {
                $uids[$item['uid']] = 1;
            }
            if (!empty($uids)) {
                $uid_euid_map = $this->rpc->local("AdunionDealService\getRegistEuidByUids", array($uids));
            }
        }

        $orders = array();
        foreach($deal_list as $order) {
            $orders[] = array(
                'euid' => $union && !empty($uid_euid_map[$order['uid']]) ? $uid_euid_map[$order['uid']] : $order['euid'],
                'mid' => intval($order['mid']),
                'order_time' => $order['order_time'],
                'order_sn' => $order['order_sn'],
                //'status' => $order['status'],
                'order_channel' => $order['order_channel'],
                'is_new_custom' => $order['is_new_custom'],
                'details' => array(
                    array(
                        'goods_id' => $order['goods_id'],
                        'goods_cate_name' => $order['goods_cn'],
                        'goods_cate' => $order['goods_type'],
                        'goods_name' => $order['goods_name'],
 //                       'goods_ta' => $order['goods_ta'],
                        'is_first_bid'=> $order['goods_ta'] == 2 ? 1 : 0,
                        'goods_price' => $order['goods_price'],
                        'totalPrice' => $order['total_price'],
                        'commission_type' => $order['days']
                    )
                )
            );
        }
        $data = array('success' => 1, 'errors' => '', 'orders' => $orders);
        return $this->output($data);
    }

    private function output($data) {
        header("Content-Type: application/json");
        header("Cache-Control: no-store");
        echo json_encode($data);
        return false;
    }

}
