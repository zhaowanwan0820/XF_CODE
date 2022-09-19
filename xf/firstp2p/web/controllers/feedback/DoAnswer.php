<?php

/**
 * 咨询答疑
 */
namespace web\controllers\feedback;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\FeedbackService;

class DoAnswer extends BaseAction
{

    public function init(){
        return $this->check_login();
    }

    public function invoke()
    {

        $userInfo = $GLOBALS['user_info'];
        $type=1;
        $status=2;
        $isAllRead=2;
        $feedbackService= new FeedbackService($userInfo['id'],$type,$status);
        //同意用户提问并被回复
        $askTotalAmount=$feedbackService->askTotalAmount();
        if(intval($askTotalAmount)>0) {
            //判断是否有未读消息
            $result=$feedbackService->getAnswerInfo();
            $isAllRead=$result?$isAllRead:1;
        }
        $event_info_answer=FeedbackService::$event_info_answer;
        $this->tpl->assign("event_type",$event_info_answer);
        //是否有未读消息
        $this->tpl->assign("is_all_read", $isAllRead);
        $this->tpl->display();

    }
}
