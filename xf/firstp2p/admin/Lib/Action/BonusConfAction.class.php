<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

class BonusConfAction extends CommonAction {

    protected $is_log_for_fields_detail = true;

    public function index() {
        parent::index();
    }

    public function add() {
        $this->assign("start_time", date('Y-m-d H:i:s',time()));
        $this->display();
    }

    public function insert() {
        $_POST['start_time'] = to_timespan(trim($_POST['start_time']));
        $_POST['end_time'] = to_timespan(trim($_POST['end_time']));
        if($_POST['end_time'] <= $_POST['start_time']){
            $this->error('开始时间不能小于结束时间！');
        }
        parent::insert();
    }

    public function update() {
        $_POST['start_time'] = to_timespan(trim($_POST['start_time']));
        $_POST['end_time'] = to_timespan(trim($_POST['end_time']));
        if($_POST['end_time'] <= $_POST['start_time']){
            $this->error('开始时间不能小于结束时间！');
        }
        parent::update();
    }

}

?>
