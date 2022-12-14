<?php

class LogAction extends CommonAction{
    public function index()
    {
        if(trim($_REQUEST['log_info'])!='')
        {
            $map['log_info'] = array('like','%'.trim($_REQUEST['log_info']).'%');
            $_REQUEST['key'] = $_REQUEST['log_info'];
            unset($_REQUEST['log_info']);
        }

        $log_begin_time  = trim($_REQUEST['log_begin_time'])==''?0:to_timespan($_REQUEST['log_begin_time']);
        $log_end_time  = trim($_REQUEST['log_end_time'])==''?0:to_timespan($_REQUEST['log_end_time']);
        if($log_end_time==0)
        {
            $map['log_time'] = array('gt',$log_begin_time);
        }
        else
        $map['log_time'] = array('between',array($log_begin_time,$log_end_time));


        $this->assign("default_map",$map);
        $this->model = DI('Log');
        parent::index();
    }

    public function foreverdelete() {
        $this->success('您无权删除日志');
        //彻底删除指定记录
        $ajax = intval($_REQUEST['ajax']);
        $id = $_REQUEST ['id'];
        if (isset ( $id )) {
                $condition = array ('id' => array ('in', explode ( ',', $id ) ) );

                $list = M(MODULE_NAME)->where ( $condition )->delete();
                if ($list!==false) {

                    $this->success (l("FOREVER_DELETE_SUCCESS"),$ajax);
                } else {

                    $this->error (l("FOREVER_DELETE_FAILED"),$ajax);
                }
            } else {
                $this->error (l("INVALID_OPERATION"),$ajax);
        }
    }


    public function foreverdeletelog() {
        $this->success('您无权删除日志');
        //彻底删除指定记录
        $ajax = intval($_REQUEST['ajax']);
        $id = $_REQUEST ['id'];
        if (isset ( $id )) {
                $condition = array ('id' => array ('in', explode ( ',', $id ) ) );

                $list = M("Log")->where ( $condition )->delete();
                if ($list!==false) {

                    $this->success (l("FOREVER_DELETE_SUCCESS"),$ajax);
                } else {

                    $this->error (l("FOREVER_DELETE_FAILED"),$ajax);
                }
            } else {
                $this->error (l("INVALID_OPERATION"),$ajax);
        }
    }
}
?>
