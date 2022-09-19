<?php
/**
 * 充值查询接口
 * 
 */
namespace web\controllers\supervision;

use web\controllers\BaseAction;
use libs\web\Form;

class RechargeQuery extends BaseAction
{
    public function init()
    {
        $this->form = new Form();
        $this->form->rules = array(
            'orderId' => array('filter' => 'string', 'optional' => false),
        );

        if (!$this->form->validate()) {
            return $this->show_error($this->form->getErrorMsg(), "", 1);
        }
    }

    public function invoke()
    {
        $orderId = $this->form->data['orderId'];

        $fs = new \core\service\SupervisionFinanceService();
        $transStatus = $fs->getTransferStatusByOutId($orderId);

        // 0 未处理，1成功，2失败
        if($transStatus == 0){
            $data = array(
                'status' => 0,
                'msg' => '资金划转中',
            );
        }elseif($transStatus == 1){
            $data = array(
                'status' => 1,
                'msg' => '资金划转成功',
            );
        }else{
            $data = array(
                'status' => 2,
                'msg' => '资金划转异常，请稍后再试',
            );
        }
        ajax_return($data);
    }
}