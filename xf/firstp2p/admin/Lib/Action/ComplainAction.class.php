<?php
use core\service\FeedbackService;

class ComplainAction extends CommonAction{

    public static $event_info=array(
        1 => '纠纷案件',
        2 => '违法行为',
        3 => '服务质量',
        4 => '其他',
    );

    public static $for_type=array(
        1 => '投资方',
        2 => '融资方',
        3 => '担保方',
        4 => '咨询方',
        5 => '平台方',
        6 => '其他',
    );

    public function index(){
        $this->model = MI ("Feedback");
        $map = $this->_search ();
        $map['type'] = 2;

        if($_REQUEST['user_mobile']){

            if (! empty ( $_REQUEST ['user_mobile'] )) {
                $user_info = \core\dao\UserModel::instance ()->getUserinfoByUsername ( $_REQUEST ['user_mobile'] );

                if (! empty ( $user_info )) {
                    $map ['user_id'] = $user_info ['id'];
                    unset ( $map ['user_mobile'] );
                } else {
                    $map ['user_id'] = '-1';
                }
            }
        }

        if($_REQUEST['begin'] && $_REQUEST['end']){
            $map['create_time'] = array('between',array(strtotime($_REQUEST['begin']),strtotime($_REQUEST['end'])));
        }else if($_REQUEST['begin']){
            $map['create_time'] = array('egt',strtotime($_REQUEST['begin']));
        }else if($_REQUEST['end']){
            $map['create_time'] = array('elt',strtotime($_REQUEST['end']));
        }

        if (! empty ( $this->model )) {
            $this->_list ( $this->model, $map );
        }

        $this->assign("for_type",FeedbackService::$for_type);
        $this->assign("event_list",FeedbackService::$event_info_complain);
        $this->display();
    }

    public function doReply(){
        $this->model = M ("Feedback");
        $id = intval($_REQUEST['id']);
        $adm_session = es_session::get(md5(conf( "AUTH_KEY" )));
        $data = array(
                'id'=>$id,
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

    protected function form_index_list(&$list) {
        foreach ( $list as &$item ) {
            $item['event_type'] = FeedbackService::$event_info_complain[$item['event_type']];
            $item['for_type'] = FeedbackService::$for_type[$item['for_type']];
            $item['contact_mobile']=!empty($item['contact_mobile'])?moblieFormat($item['contact_mobile']):'';
            $item['statusText'] = $item['status'] == 1? "未回复":"已回复";
        }
    }

}
