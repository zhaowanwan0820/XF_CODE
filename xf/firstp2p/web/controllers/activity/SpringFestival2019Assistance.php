<?php
namespace web\controllers\activity;

use web\controllers\BaseAction;
use libs\web\Form;
use NCFGroup\Common\Library\ApiService;
use libs\weixin\Weixin;

/**
 * 分享页
 */
class SpringFestival2019Assistance extends BaseAction
{

    public function init()
    {
        $this->form = new Form();
        $this->form->rules = [
            'c' => ['filter' => 'string', 'option' => ['optional' => true]],
            'token' => ['filter' => 'string', 'option' => ['optional' => true]],
        ];
        if (!$this->form->validate()) {
            echo json_encode(['code' => 10000, 'message' => $this->form->getErrorMsg()]);
            return false;
        }
    }

    public function invoke()
    {
        $this->template = "web/views/activity/spring_festival_2019_assistance.html";
    }

}
