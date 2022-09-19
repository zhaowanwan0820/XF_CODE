<?php
/**
 * 预约协议
 *
 * @date 2016-11-17
 * @author guofeng@ucfgroup.com
 */

namespace api\controllers\deal;

use libs\web\Form;
use api\controllers\ReserveBaseAction;
use core\service\contract\ContractPreService;

class ReserveProtocol extends ReserveBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'amount' => array(
                'filter' => 'float',
                'option' => array('optional' => true)
            ),
            'invest' => array(
                'filter' => 'string',
                'option' => array('optional' => true)
            ),
            'rate' => array(
                'filter' => 'float',
                'option' => array('optional' => true)
            ),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);

        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        if (!$this->isOpenReserve()) {
            return false;
        }

        $userInfo = $this->getUserBaseInfo();
        if (empty($userInfo)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $data = $this->form->data;
        if (!empty($data['invest']) && false === strpos($data['invest'], '_')) {
            $this->setErr('ERR_MANUAL_REASON', '投资期限参数不合法');
            return false;
        }

        $investDeadline = $investDeadlineUnit = '';
        if (!empty($data['invest'])) {
            list($investDeadline, $investDeadlineUnit) = explode('_', $data['invest']);
        }
        $amount = !empty($data['amount']) ? $data['amount'] : '';
        $rate = !empty($data['rate']) ? $data['rate'] : '';

        //获取预约规则
        $content = ContractPreService::getReservationContract($userInfo['id'], $amount, $investDeadline, $investDeadlineUnit, $rate, time());

        $this->json_data = array(
            'content' => $content,
        );
    }
}
