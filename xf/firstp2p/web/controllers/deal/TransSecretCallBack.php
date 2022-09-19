<?php
/**
 * 划转轮询接口
 */

namespace web\controllers\deal;

use core\dao\SupervisionTransferModel;
use libs\web\Form;
use web\controllers\BaseAction;
use libs\web\Url;


class TransSecretCallBack extends BaseAction {

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            'orderId' => array('filter' => 'string', 'optional' => true),
         );
        if (!$this->form->validate()) {
            return $this->show_error($this->form->getErrorMsg(), "", 1);
        }
    }

    /**
     * status
     * @return bool|void
     */
    public function invoke() {
        $user = $GLOBALS['user_info'];

        $data = array(
            0 => array('status'=>0,'msg'=>'处理中','data'=>''),
            1 => array('status'=>1,'msg'=>'系统异常','data'=>''),
            2 => array('status'=>2,'msg'=>'划转成功','data'=>''),
            3 => array('status'=>3,'msg'=>'划转失败,请稍后查看资金记录','data'=>''),
        );

        if(!$user){
            return ajax_return($data[1]);
        }

        $orderId = trim($this->form->data['orderId']);
        $orderInfo = SupervisionTransferModel::instance()->getTransferRecordByOutId($orderId);


        if(!$orderInfo){
            return ajax_return($data[0]); // 可能订单还没有保存ajax请求已发出
        }
        if($orderInfo['user_id'] != $user['id']){
            return ajax_return($data[1]);
        }

        if($orderInfo['transfer_status'] == SupervisionTransferModel::TRANSFER_STATUS_NORMAL){
            return ajax_return(($data[0]));
        }elseif($orderInfo['transfer_status'] == SupervisionTransferModel::TRANSFER_STATUS_SUCCESS){
            return ajax_return($data[2]);
        }else{
            ajax_return($data[3]);
        }
    }
}