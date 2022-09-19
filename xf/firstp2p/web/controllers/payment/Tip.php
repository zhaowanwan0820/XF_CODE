<?php
/**
 * 个人中心充值操作
 * @author caolong<caolong@ucfgroup.com>
 */

namespace web\controllers\payment;

use libs\web\Form;
use web\controllers\BaseAction;
use core\dao\PaymentNoticeModel;
use libs\web\Url;

class Tip extends BaseAction {

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
        $data = $this->form->data;
        $payment_notice = PaymentNoticeModel::instance()->find($data['id']);
        if(empty($payment_notice)) {
            return app_redirect(url("index"));
        }
        $actionUrl = Url::gene('payment','pay',array('id'=>$payment_notice['id'],'check'=>1));
        $this->tpl->assign('actionUrl',$actionUrl);
        $reUrl = Url::gene('order','modify',array('id'=>$payment_notice['order_id']));
        $this->tpl->assign("reUrl",$reUrl);
    }
}
