<?php

/**
 * DealLoadDetail.php 普惠新接口
 *
 * @date 2018-11-22
 */

namespace api\controllers\ncfph;

use api\controllers\NcfphRedirect;
use libs\web\Form;

class DealLoadDetail extends NcfphRedirect {
    const IS_H5 = true;

    private $phAction = '/account/deal_load_detail';

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            'id' => array("filter" => "int", "message" => "id is error"),
            "token" => array("filter" => "required", "message" => "token is required"),
        );

        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
        }

        if (empty($this->form->data['id'])) {
            $this->setErr("ERR_PARAMS_ERROR", "id is error");
        }

        $this->form->data['id'] = intval($this->form->data['id']);
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

