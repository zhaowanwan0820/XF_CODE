<?php
/**
 * 冻结资金
 */
namespace api\controllers\stock;

use libs\web\Form;
use api\controllers\FundBaseAction;

class Lock extends FundBaseAction
{

    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'signature' => array('filter' => 'required', 'message'=> '签名不能为空'),
            'userId' => array('filter' => 'required', 'message' => 'userId不能为空'),
            'outOrderId' => array('filter' => 'required', 'message' => 'outOrderId不能为空'),
            'amount' => array('filter' => 'required', 'message' => 'amount不能为空'),
            'note' => array('filter' => 'required', 'message' => 'note不能为空'),
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
            if (!$this->rpc->local('StockService\lock', array($data['outOrderId'], $data['userId'], $data['amount'], $data['note']))) {
                return $this->setErr('ERR_SYSTEM', '操作失败');
            }
        } catch (\Exception $e) {
            return $this->setErr('ERR_SYSTEM', '操作失败:'.$e->getMessage());
        }

        return $this->json_data = array();
    }

}
