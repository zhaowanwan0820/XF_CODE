<?php

namespace api\controllers\ncfph;

use api\controllers\NcfphRedirect;
use libs\web\Form;

/**
 * 随心约普惠预约详情页面
 */
class DealReserveDetail extends NcfphRedirect {
    const IS_H5 = true;

    private $phAction = '/deal/reserveDetail';

    public function init() {
        parent::init();
        $this->form = new Form();

        $this->form->rules = array(
            'token' => array(
                'filter' => 'required',
                'message' => 'ERR_AUTH_FAIL'
            ),
            'line_unit' => array(
                'filter' => 'required',
                'message' => 'line_unit is required'
            ),
            'deal_type' => array('filter' => 'int'),
            'loantype' => array('filter' => 'int'),
            'rate' => array('filter' => 'string'),
            'userClientKey' => array(
                'filter' => 'string',
                'option' => array('optional' => true)
            ),
        );

        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
        }
    }

    public function invoke() {
        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
        }

        $data = $this->form->data;
        return $this->ncfphRedirect($this->phAction, $data);
    }
}
