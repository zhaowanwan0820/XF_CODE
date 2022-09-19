<?php

namespace web\controllers\bind;

use libs\web\Form;
use libs\web\Bind;
use \libs\utils\Logger;
use web\controllers\BaseAction;
use core\service\MobileCodeService;

class Clean extends BaseAction {

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            'backUrl'   => array('filter' => 'string'),
        );
        $this->form->validate();
    }

    public function invoke() {
        $backUrl  = $this->form->data['backUrl'];
        $userBind = \es_session::get("user_bind");
        if (!$userBind) {  //非法进入
            header("location:".$backUrl);
            exit;
        }
        Bind::unSetBindSign();
        header("location:".$backUrl);
        exit;
    }

}
