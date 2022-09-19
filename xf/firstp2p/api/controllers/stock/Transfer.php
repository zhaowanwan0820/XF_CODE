<?php
/**
 * 转账
 */
namespace api\controllers\stock;

use libs\web\Form;
use api\controllers\FundBaseAction;

class Transfer extends FundBaseAction
{

    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'signature' => array('filter' => 'required', 'message'=> '签名不能为空'),
            'outOrderId' => array('filter' => 'required', 'message' => 'outOrderId不能为空'),
            'payerId' => array('filter' => 'required', 'message' => 'payerId不能为空'),
            'receiverId' => array('filter' => 'required', 'message' => 'receiverId不能为空'),
            'amount' => array('filter' => 'required', 'message' => 'amount不能为空'),
            'payerNote' => array('filter' => 'required', 'message' => 'payerNote不能为空'),
            'receiverNote' => array('filter' => 'required', 'message' => 'receiverNote不能为空'),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;

        try {
            if (!$this->rpc->local('StockService\transfer', array($data['outOrderId'], $data['payerId'], $data['receiverId'], $data['amount'], $data['payerNote'], $data['receiverNote']))) {
                return $this->setErr('ERR_SYSTEM', '操作失败');
            }
        } catch (\Exception $e) {
            return $this->setErr('ERR_SYSTEM', '操作失败:'.$e->getMessage());
        }

        return $this->json_data = array();
    }

}
