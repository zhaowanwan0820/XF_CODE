<?php
namespace api\controllers\bonus;

use api\controllers\BonusBaseAction;
use libs\web\Form;

/**
 * 用户消费接口
 */
class Consume extends BonusBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array(
            'userId' => array('filter' => 'int', 'message' => '用户id不合法'),
            'amount' => array('filter' => 'int', 'message' => '消费金额不合法'),
            'outOrderId' => array('filter' => 'required', 'message' => '消费订单号不能为空'),
            'channel' => array('filter' => 'required', 'message' => '消费渠道不能为空'),
            'info' => array('filter' => 'required', 'message' => '消费描述不能为空'),
        );
        $this->form->rules = array_merge($this->generalFormRules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;

        try {
            $ret = $this->rpc->local('BonusService\payConsume', array(
                'userId' => $data['userId'],
                'amount' => intval($data['amount']),
                'outOrderId' => $data['outOrderId'],
                'channel' => $data['channel'],
                'info' => $data['info'],
            ));
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                return $this->json_data = array('userId' => $data['userId'], 'outOrderId' => $data['outOrderId'], 'actualAmount' => $data['amount']);
            } else {
                return $this->setErr(0, '消费失败:'.$e->getMessage());
            }
        }

        return $this->json_data = array('userId' => $data['userId'], 'outOrderId' => $data['outOrderId'], 'actualAmount' => $data['amount']);
    }

}
