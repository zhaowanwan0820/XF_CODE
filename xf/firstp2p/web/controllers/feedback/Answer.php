<?php

/**
 * 咨询答疑
 */
namespace web\controllers\feedback;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\FeedbackService;
use libs\utils\Logger;

class Answer extends BaseAction
{

    public function init()
    {
        if (!$this->check_login()) return false;
        $this->form = new Form('post');
        $this->form->rules = array(
            'event_type' => array('filter' => 'int','require' => true,'message' =>"咨询类型不能为空"),
            'image_url'=> array('filter' => 'string','require' => true,'message' =>"图片不能为空"),
            'content' => array('filter' => 'string','require' => true,'message' =>"内容不能为空"),
        );
        if (!$this->form->validate()) {
            return $this->show_error($this->form->getErrorMsg(), "", 1);
        }
    }

    public function invoke()
    {
        $params = $this->form->data;
        Logger::info(implode(" | ",array(__CLASS__, __FUNCTION__,'params:'.json_encode($params))));
        $userInfo = $GLOBALS['user_info'];
        $type=1;
        Logger::info(implode(" | ",array(__CLASS__, __FUNCTION__,'userId:'.$userInfo['id'])));
        $feedbackService= new FeedbackService($userInfo['id'],$type);
        $res = $feedbackService->checkData($params);
        Logger::info(implode(" | ",array(__CLASS__, __FUNCTION__,'res:'.json_encode($res))));
        echo json_encode($res);
    }
}
