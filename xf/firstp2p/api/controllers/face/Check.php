<?php
/**
 * Created by PhpStorm.
 * User: liaoyebin
 * Date: 2017/12/20
 * Time: 15:34
 */

namespace api\controllers\face;

use api\controllers\AppBaseAction;
use libs\web\Form;
use core\service\face\FaceService;

class Check extends AppBaseAction
{
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
