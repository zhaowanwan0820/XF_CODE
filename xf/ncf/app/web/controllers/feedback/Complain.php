<?php

/**
 *投诉举报页面
 */

namespace web\controllers\feedback;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\feedback\FeedbackService;
use libs\utils\Logger;

class Complain extends BaseAction{

    public function init(){
        if(!$this->check_login()) return false;
        $this->form = new Form('post');
        $this->form->rules = array(
            'contact_name' => array('filter' => 'string'),
            'contact_mobile' => array('filter' => 'string'),
            'contact_email' => array('filter' => 'string'),
            'for_name' => array('filter' => 'string','require' => true,'message' =>"举报对象姓名/昵称不能为空"),
            'for_type' => array('filter' => 'int','require' => true,'message' =>"请选择举报对象类型"),
            'for_product' => array('filter' => 'string','require' => true,'message' =>"关联产品/项目不能为空"),
            'title' => array('filter' => 'string','require' => true,'message' =>"标题不能为空"),
            'content' => array('filter' => 'string','require' => true,'message' =>"内容不能为空"),
            'event_type' => array('filter' => 'int','require' => true,'message' =>"请选择事件类别"),
            'image_url'=> array('filter' => 'string','require' => true,'message' =>"图片不能为空"),
            'is_anony' => array('filter' => 'int'),
        );
        if (!$this->form->validate()) {
            return $this->show_error($this->form->getErrorMsg(), "", 1);
        }
    }

    public function invoke(){
        $res=array('errCode'=>1,'msg'=>'','data'=>false);
        $params = $this->form->data;
         Logger::info(implode(" | ",array(__CLASS__, __FUNCTION__,'params:'.json_encode($params))));
         if(intval($params['is_anony'])==0){
             $res['msg']='请选择是否匿名';
         }elseif(intval($params['is_anony'])==1&&(empty($params['contact_name'])||empty($params['contact_mobile'])||empty($params['contact_email']))){
             $res['msg']='举报人信息不能为空';
         }elseif(!empty($params['contact_email'])&&!check_email($params['contact_email'])){
             $res['msg']='举报人邮箱格式错误';
         }elseif(!empty($params['contact_email'])&&!check_mobile($params['contact_mobile'])){
             $res['msg']='举报人手机号错误';
         }else{
             $userInfo = $GLOBALS['user_info'];
             $type=2;
             Logger::info(implode(" | ",array(__CLASS__, __FUNCTION__,'userId:'.$userInfo['id'])));
             $feedbackService= new FeedbackService($userInfo['id'],$type);

             $res = $feedbackService->checkData($params);
             Logger::info(implode(" | ",array(__CLASS__, __FUNCTION__,'res:'.json_encode($res))));
         }
         echo json_encode($res);
    }

}

