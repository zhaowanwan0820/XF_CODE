<?php

namespace web\controllers\feedback;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\FeedbackService;

class DoComplain extends BaseAction{

    public function init(){
        $this->check_login();
    }

    public function invoke()
    { 
        $event_info_complain=FeedbackService::$event_info_complain;
        $for_type=FeedbackService::$for_type;
        $this->tpl->assign("event_type",$event_info_complain);
        $this->tpl->assign("for_type",$for_type);
        $this->tpl->display();
    }
}
