<?php
namespace api\controllers\bonus;

use api\controllers\BonusBaseAction;
use libs\web\Form;
use core\dao\BonusConsumeModel;

/**
 * ConsumeNotify
 * 消费回调
 */
class ConsumeNotify extends BonusBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array(
            'userId' => array('filter' => 'int', 'message' => '用户id不合法'),
            'outOrderId' => array('filter' => 'required', 'message' => '消费订单号不能为空！'),
            'consumeResult' => array('filter' => 'required', 'message' => '消费结果不能为空！')
        );
        $this->form->rules = array_merge($this->generalFormRules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {

        $data = $this->form->data;

        if ($data['consumeResult'] != 1) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', '错误的消费结果');
            return false;
        }

        $consumeLog = $this->rpc->local('BonusService\getConsumeByOutOid', array('outOrderId' => $data['outOrderId']));

        if (empty($consumeLog)) {
            $this->setErr('0', '消费记录不存在');
            return false;
        }

        if ($consumeLog['user_id'] != $data['userId']) {
            $this->setErr('0', '消费和消费成功用户不匹配');
            return false;
        }

        if (!empty($consumeLog) && $consumeLog['status'] != BonusConsumeModel::STATUS_CONSUME) {
            $this->json_data = array('userId' => $data['userId'], 'outOrderId' => $data['outOrderId']);
            return false;
        }

        try {
            $result = $this->rpc->local('BonusService\bonusConsumeFinish', array('id' => $consumeLog['id'], 'consumeLog' => $consumeLog));
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $this->json_data = array('userId' => $data['userId'], 'outOrderId' => $data['outOrderId']);
                return false;
            } else {
                $this->setErr(0, $e->getMessage());
                return false;
            }
        }

        $this->json_data = array('userId' => $data['userId'], 'outOrderId' => $data['outOrderId']);
    }
}
