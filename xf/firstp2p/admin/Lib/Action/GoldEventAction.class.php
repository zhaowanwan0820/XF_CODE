<?php

use NCFGroup\Common\Library\Idworker;

class GoldEventAction extends CommonAction{
    public function __construct() {
        parent::__construct();
    }

    public static $eventType = array(
            1=>"鑫里有底儿活动赠金",
            2=>"豪底气活动赠金"
    );

    public function record($tpl="record") {
        $pageNum = intval($_REQUEST['p']);
        $pageNum = $pageNum > 0 ? $pageNum : 1;
        $pageSize = C('PAGE_LISTROWS');
        $limit = (($pageNum-1)*$pageSize).",".$pageSize;
        $map = array();
        $userId = intval($_REQUEST['user_id']);
        if($userId){
            $map['user_id'] = $userId;
        }
        $eventId = intval($_REQUEST['event_id']);
        if($eventId){
            $map['event_id'] = $eventId;
        }
        $remark = trim($_REQUEST['remark']);
        if($remark){
            $map['remark'] = array('like', '%' . $remark . '%');
        }
        if($tpl == "audit"){
            $map['status'] = 0;
        }

        $list = MI("GoldEventRecord")->where($map)->order('id desc')->limit($limit)->findAll();
        $count = MI("GoldEventRecord")->where($map)->count();
        $this->form_record_list($list);
        $this->assign('list', $list);
        $p = new Page ($count, $pageSize);
        $page = $p->show ();
        $this->assign ( "page", $page );
        $this->assign ( "nowPage", $p->nowPage );
        $this->display($tpl);
    }

    public function audit(){
        $this->record('audit');
    }

    public function doAudit(){
        $ajax = intval($_REQUEST['ajax']);
        $ids = isset($_REQUEST ['id']) ? explode(',', $_REQUEST ['id']) : array();
        if(empty($ids)){
            $this->error("请选择记录",$ajax);
        }
        $ids = array_map('intval',$ids);
        $status = intval($_REQUEST['status']) == 1? 1:-1;
        $adm_session = es_session::get ( md5 ( conf ( "AUTH_KEY" ) ) );
        $result = M("GoldEventRecord")->where(array('id' => array('in', $ids),'status' => 0))->data(array('status'=>$status,'audit_id'=>$adm_session['adm_id']))->save();
        if($result){
            $this->success ("操作成功",$ajax);
        }else{
            $this->error ("操作失败",$ajax);
        }
    }

    public function importRecord(){
        $data = $this->getCsvdata('upfile');
        if($data['status'] != 0){
            $this->error ($data['error']);
            return false;
        }
        $result = $this->batchInsert($data['data']);
        if(!$result){
            $this->error ("导入失败");
        }else{
            $this->success ("导入成功");
        }
    }

    public function exportRecord(){
        $map = array();
        $userId = intval($_REQUEST['user_id']);
        if($userId){
            $map['user_id'] = $userId;
        }
        $eventId = intval($_REQUEST['event_id']);
        if($eventId){
            $map['event_id'] = $eventId;
        }
        $remark = trim($_REQUEST['remark']);
        if($remark){
            $map['remark'] = array('like', '%' . $remark . '%');
        }
        $map['status'] = 0;

        $list = MI("GoldEventRecord")->where($map)->order('id asc')->findAll();
        $this->form_record_list($list);
        $data_str = '';
        if(!empty($list)){
            foreach($list as $val){
                $data_str .= $val['id'].",".$val['user_id'].",".$val['gold'].",".$val['event_id'].",".$val['remark'].",".$val['admin_id'].",".$val['create_time'].",".$val['finish_time'].",".$val['status_txt']."\n";
            }
        }
        $content = implode ( ',', array (
                '编号',
                '用户ID',
                '赠金克重',
                '活动来源',
                '备注',
                '操作人',
                '创建时间',
                '执行时间',
                '状态'
        ) ) . "\n";
        $content .= $data_str;
        $datatime = date ( "YmdHis", get_gmtime () );
        header ( "Content-Disposition: attachment; filename=gold_event_records_{$datatime}.csv" );
        echo iconv ( 'utf-8', 'gbk//ignore', $content );
        exit ();
    }

    public function deleteRecord(){
        $ajax = intval($_REQUEST['ajax']);
        $ids = isset($_REQUEST ['id']) ? explode(',', $_REQUEST ['id']) : array();
        if(empty($ids)){
            $this->error("请选择记录",$ajax);
        }
        $ids = array_map('intval',$ids);
        $result = M("GoldEventRecord")->where(array('id' => array('in', $ids),'status' => array('in', array(0,-1))))->delete();
        if($result){
            $this->success ("删除成功",$ajax);
        }else{
            $this->error ("删除失败",$ajax);
        }
    }

    public function batchInsert ($data){
        try {
            $adm_session = es_session::get ( md5 ( conf ( "AUTH_KEY" ) ) );
            $create_time = date("Y-m-d H:i:s");
            $pay_user_id = app_conf('GOLD_EVENT_PAY_USER_ID');
            $model = M("GoldEventRecord");
            $model->startTrans();
            foreach ($data as $value){
                $value['admin_id'] = $adm_session['adm_id'];
                $value['create_time'] = $create_time;
                $value['pay_user_id'] = intval($pay_user_id);
                $value['order_id'] = Idworker::instance()->getId();
                $id = M("GoldEventRecord")->add($value);
                if (!$id) {
                    throw new Exception('插入失败');
                }
            }
            $model->commit();
        } catch (Exception $e) {
            $model->rollback();
            return false;
        }
        return true;
    }


    private function getCsvdata($fileName){
        $data = array('status'=>0,'error'=>'','data' => array());
        if (empty ( $_FILES [$fileName] ['name'] )) {
            $data['status'] = 1;
            $data['error'] = '上传文件为空';
            return $data;
        }
        if (end ( explode ( '.', $_FILES ['upfile'] ['name'] ) ) != 'csv') {
            $data['status'] = 1;
            $data['error'] = '上传文件类型必须为csv';
            return $data;
        }

        if (($handle = fopen ( $_FILES ['upfile'] ['tmp_name'], "r" ))) {
            $i = 0;
            while ($line = fgetcsv($handle)) {
                if(!empty($line)){
                    $data['data'][$i]['user_id'] = intval($line[0]);
                    $data['data'][$i]['gold'] = floatval($line[1]);
                    $data['data'][$i]['event_id'] = intval($line[2]);
                    $data['data'][$i]['remark'] = iconv ('gbk', 'utf-8', trim($line[3]));
                    $i++;
                }
            }
        }
        array_shift($data['data']);

        if(empty($data['data'])){
            $data['status'] = 1;
            $data['error'] = '上传内容不能为空';
        }

        return $data;
    }

    private function form_record_list(&$list){
        if(!empty($list)){
            foreach ($list as &$value){
                $value['event_id'] = self::$eventType[$value['event_id']];
                if($value['status'] == 0){
                    $value['status_txt'] = "待审核";
                }elseif($value['status'] == 1){
                    $value['status_txt'] = "待执行";
                }elseif($value['status'] == 2){
                    $value['status_txt'] = "已完成";
                }else{
                    $value['status_txt'] = "驳回";
                }
                $value['finish_time'] = $value['finish_time'] == '0000-00-00 00:00:00'?'-':$value['finish_time'];
                $value['admin_id'] = get_admin_name($value['admin_id']);
                $value['audit_id'] = $value['audit_id']?get_admin_name($value['audit_id']):"-";
            }
        }
    }
}
