<?php

namespace api\controllers\apis;

use core\service\WeiXinService;
use core\service\UserService;
use NCFGroup\Common\Library\SignatureLib;
use libs\web\Form;
use libs\utils\Logger;

class WeixinBindCallback extends ApisBaseAction
{

    public function init()
    {
        parent::init();
        $this->form = new Form('post');

        $this->form->rules = array_merge($this->generalFormRule, [
            'openId' => ['filter' => 'required', 'message' => '参数错误'],
        ]);

        if (!$this->form->validate()) {
            return $this->echoJson(10001, $this->form->getErrorMsg());
        }
    }

    public function invoke()
    {
        (new WeiXinService)->bindSuccessCallback($this->form->data['openId']);
        return $this->echoJson(0, 'ok', $res);
    }

}
