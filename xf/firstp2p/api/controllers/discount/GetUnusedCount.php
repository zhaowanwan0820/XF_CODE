<?php

/**
 * GetUnusedCount.php
 *
 * Filename: GetUnusedCount.php
 * Descrition: 获得未使用的兑换券数量
 * Author: yutao@ucfgroup.com
 * Date: 16-2-22 下午2:48
 */

namespace api\controllers\discount;

use libs\web\Form;
use api\controllers\AppBaseAction;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;

class GetUnusedCount extends AppBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'site_id' => array('filter' => 'int', 'option' => array('optional' => true)),
            'discount_type' => array('filter' => 'int', 'option' => array('optional' => true)),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        if (app_conf('BONUS_DISCOUNT_MOMENTS_DISABLE')) {
            $this->json_data = ['count' => '0'];
            return true;
        }

        $data = $this->form->data;
        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        // 默认取0，表示取返现券和加息券
        $type = isset($data['discount_type']) ? intval($data['discount_type']) : 0;
        $consumeType = CouponGroupEnum::CONSUME_TYPE_P2P;
        // 470的版本可以看到黄金券
        if (isset($_SERVER['HTTP_VERSION']) && intval($_SERVER['HTTP_VERSION']) >= 460) {
            $consumeType = 0;
        }

        $rpcParams = array($loginUser['id'], $type, $consumeType);
        $count = $this->rpc->local('O2OService\getUserUnusedDiscountCount', $rpcParams);
        if ($count === false) {
            $count = 0;
        } else if ($count > 9) {
            //超过9的时候，只显示9+
            $count = '9+';
        }

        $this->json_data = array('count' => $count);
    }
}
