<?php

/**
 * Confirm.php
 *
 * @date 2018-11-22
 * @author liguizhi <liguizhi@ucfgroup.com>
 */

namespace api\controllers\ncfph;

use api\controllers\NcfphRedirect;
use libs\web\Form;
use libs\utils\Aes;

class DealConfirm extends NcfphRedirect {
    const IS_H5 = true;

    private $phAction = '/deal/confirm';

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'id' => array('filter' => 'required', 'message' => 'ERR_DEAL_NOT_EXIST'),
            'discount_id' => array(
                'filter' => 'int',
                'option' => array('optional' => true),
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
            'discount_type' => array(
                'filter' => 'int',
                'option' => array('optional' => true),
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
            'discount_group_id' => array(
                'filter' => 'int',
                'option' => array('optional' => true),
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
            'discount_sign' => array(
                'filter' => 'string',
                'option' => array('optional' => true),
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
            'discount_bidAmount' => array(
                'filter' => 'string',
                'option' => array('optional' => true),
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),

        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
        }

        $dealId = $data['id'];
        if (is_numeric($data['id'])) {
            $dealId = Aes::encryptForDeal($data['id']);
        }
        $ncfphData = array(
            'dealid' => $dealId,
            'token' => $data['token'],
            // isset为了避免notice 存在用户不用券的情况
            'discount_id' => isset($data['discount_id']) ? $data['discount_id'] : '',
            'discount_group_id' => isset($data['discount_group_id']) ? $data['discount_group_id'] : '',
            'discount_bidAmount' => isset($data['discount_bidAmount']) ? $data['discount_bidAmount'] : '',
        );

        return $this->ncfphRedirect($this->phAction, $ncfphData);
    }

}
