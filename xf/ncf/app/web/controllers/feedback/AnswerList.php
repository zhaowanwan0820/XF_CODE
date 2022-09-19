<?php

/**
 *咨询答疑页面
 */

namespace web\controllers\feedback;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\feedback\FeedbackService;
use libs\utils\Logger;

class AnswerList extends BaseAction{
    public function init(){
        if (!$this->check_login()) return false;
        $this->form = new Form();
        $this->form->rules = array(
            'is_all_read' => array('filter' => 'int','require' => true,'message' =>"是否已读不能为空"),
        );
        if (!$this->form->validate()) {
            return $this->show_error($this->form->getErrorMsg(), "", 1);
        }
    }

    public function invoke(){
        $params = $this->form->data;
        Logger::info(implode(" | ",array(__CLASS__, __FUNCTION__,'params:'.json_encode($params))));
        $data=array();
        $userInfo = $GLOBALS['user_info'];
        $type=1;
        Logger::info(implode(" | ",array(__CLASS__, __FUNCTION__,'userId:'.$userInfo['id'])));
        $feedbackService= new FeedbackService($userInfo['id'],$type);
        //统计用户提问次数总数量
        $askTotalAmount=$feedbackService->askTotalAmount();
        Logger::info(implode(" | ",array(__CLASS__, __FUNCTION__,'askTotalAmount:'.$askTotalAmount)));
        if(intval($askTotalAmount)>0){
            //获取用户历史提问数据
            $answerList=$feedbackService->answerList();
            Logger::info(implode(" | ",array(__CLASS__, __FUNCTION__,'answerList:'.json_encode($answerList))));
            if(!empty($answerList)){
                foreach ($answerList as $key=>$value) {
                    $data[$key]['content']=$answerList[$key]['content'];
                    if(empty($answerList[$key]['reply_content'])){
                        $data[$key]['reply']='暂无';
                    }else {
                        $data[$key]['reply'] = $answerList[$key]['reply_content'];
                    }
                    $data[$key]['time']=date('Y-m-d',$answerList[$key]['create_time']);
                }
            }
            //为1是是有未读数据
            if (intval($params['is_all_read'])==1) {
                $is_read = 1;
                $status=2;
                $feedbackService = new FeedbackService($userInfo['id'], $type, $status, $is_read);
                $result=$feedbackService->updateIsReadByUserId();
            }
        }
        Logger::info(implode(" | ",array(__CLASS__, __FUNCTION__,'data:'.json_encode($data),'result:'.$result)));
        echo json_encode($data);
    }

}
