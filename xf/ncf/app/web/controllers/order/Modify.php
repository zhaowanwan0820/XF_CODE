<?php
/**
 * 个人中心充值操作
 * @author wangyiming<wangyiming@ucfgroup.com>
 */

namespace web\controllers\order;

use libs\web\Form;
use web\controllers\BaseAction;
use core\dao\PaymentNoticeModel;

class Modify extends BaseAction {

    public function init() {
        if(!$this->check_login()) return false;
        $this->form = new Form("get");
        $this->form->rules = array(
                'id' => array('filter' => 'int'),
        );
        if(!$this->form->validate()) {
           return app_redirect(url("index"));
        } 
    }

    public function invoke() {
        $data     = $this->form->data;
        if(empty($data['id']))
        {
            return $this->show_error($GLOBALS['lang']['NOTICE_SN_NOT_EXIST'], "", 0,0,APP_ROOT."/");
        }
        $user_id  = intval($GLOBALS['user_info']['id']);
        $order_info = PaymentNoticeModel::instance()->getInfoByIdUserId($data['id'], $user_id);
        if(!$order_info)
        {
            return $this->show_error($GLOBALS['lang']['INVALID_ORDER_DATA']);
        }
        if($order_info['is_paid'] == 1)
        {
            return $this->show_success('支付已完成','提示',0,0,url("index","account"));
        }
        return app_redirect(url("index","account/charge"));
    }
}
