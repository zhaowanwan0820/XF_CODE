<?php

use core\service\feedback\FeedbackService;
use core\service\user\UserService;

class AnswerAction extends CommonAction{

    public function index() {
        $this->model = MI ("Feedback");
        $map = $this->_search ();
        $map['type'] = 1;

        if($_REQUEST['begin'] && $_REQUEST['end']){
            $map['create_time'] = array('between',array(strtotime($_REQUEST['begin']),strtotime($_REQUEST['end'])));
        }else if($_REQUEST['begin']){
            $map['create_time'] = array('egt',strtotime($_REQUEST['begin']));
        }else if($_REQUEST['end']){
            $map['create_time'] = array('elt',strtotime($_REQUEST['end']));
        }
        if (! empty ( $_REQUEST ['contact_mobile'] )) {
            $user_info = UserService::getUserByMobile ( $_REQUEST ['contact_mobile'] );
            if (! empty ( $user_info )) {
                $map ['user_id'] = $user_info ['id'];
                unset($map['contact_mobile']);
            } else {
                $map ['user_id'] = '-1';
            }
        }
        if (! empty ( $this->model )) {
            $this->_list ( $this->model, $map );
        }

        $this->assign("event_list",FeedbackService::$event_info_answer);
        $this->display ();
    }

    public function reply(){
        $id = intval($_REQUEST['id']);
        $this->assign("id",$id);
        $this->display ();
    }

    public function doReply(){
        $this->model = M ("Feedback");
        $id = intval($_REQUEST['id']);
        $reply_content = addslashes($_REQUEST['reply_content']);
        if(empty($reply_content)){
            $this->ajaxReturn($result,'回复内容不能为空',0);
        }
        $adm_session = es_session::get(md5(conf( "AUTH_KEY" )));
        $data = array(
                'id'=>$id,
                'reply_content' => $reply_content,
                'status' => 2,
                'admin_id'=>$adm_session ["adm_id"],
                'update_time'=>time()
        );
        $result = $this->model->save($data);
        if($result){
            $this->ajaxReturn($result,'操作成功',1);
        }else{
            $this->ajaxReturn($result,'操作失败',0);
        }
    }

    public function view(){
        $this->model = MI ("Feedback");
        $id = intval($_REQUEST['id']);
        $data=$this->model->find($id);
        $this->assign("reply_content",$data['reply_content']);
        $this->display ();
    }

    protected function form_index_list(&$list) {
        foreach ( $list as &$item ) {
            $item['event_type'] = FeedbackService::$event_info_answer[$item['event_type']];
            $item['statusText'] = $item['status'] == 1? "未回复":"已回复";
            $item['create_time']=$item['create_time']-28800;
            $userInfo = UserService::getUserById($item['user_id']);
            if(!empty($userInfo)){
                $item['contact_name']=$userInfo['user_name'];
                $item['contact_mobile']=moblieFormat($userInfo['mobile']);
            }
        }
    }

}

