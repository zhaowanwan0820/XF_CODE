<?php

namespace api\controllers\face;

use api\controllers\AppBaseAction;
use libs\web\Form;
use core\service\face\FaceService;

/**
 * 人脸识别检查check
 */
class Check extends AppBaseAction
{
    // 是否需要授权
    protected $needAuth = false;

    public function init() {
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
            "type" => array("filter" => "required", "message" => "type is required"),
            'token' => array('filter' => 'string', 'option' => array('optional' => true)),
            'mobile' => array('filter' => 'string', 'option' => array('optional' => true)),
            'idno' => array('filter' => 'string', 'option' => array('optional' => true)),
        );

        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }

        return true;
    }

    public function invoke() {
        $formData = $this->form->data;

        $cmd = FaceService::createCmd($formData);
        if (!$cmd) {
            $this->setErr("ERR_PARAMS_ERROR", 'type illegal');
        }

        $result = $cmd->check();
        if ($cmd->hasError()) {
            $this->setErr($cmd->errorNo, $cmd->errorMsg);
        }

        $this->json_data = $result;
    }
}
