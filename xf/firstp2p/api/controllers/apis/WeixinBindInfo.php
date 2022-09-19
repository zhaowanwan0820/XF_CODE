<?php

namespace api\controllers\apis;

use core\service\WeiXinService;
use core\service\UserService;
use NCFGroup\Common\Library\SignatureLib;
use libs\web\Form;

class WeixinBindInfo extends ApisBaseAction
{

    public function init()
    {
        parent::init();
        $this->form = new Form('post');

        $this->form->rules = array_merge($this->generalFormRule, [
            'uids' => ['filter' => 'required', 'message' => '参数错误'],
        ]);

        if (!$this->form->validate()) {
            return $this->echoJson(10001, $this->form->getErrorMsg());
        }
    }

    public function invoke()
    {
        $uids = $this->form->data['uids'];
        $uids = explode(',', $uids);

        $res = (new WeiXinService)->getOpenIdByUids($uids);
        return $this->echoJson(0, 'ok', $res);
    }

}
