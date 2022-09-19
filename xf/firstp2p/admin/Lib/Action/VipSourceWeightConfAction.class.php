<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

class VipSourceWeightConfAction extends CommonAction {

    public function __construct()
    {
        parent::__construct();
        $this->model = MI('VipSourceWeightConf', 'vip', 'master');
    }

    public function index() {
        parent::index();

    }

    public function add() {
        $this->display();
    }

    public function insert() {

        $data = array();

        $data['update_time'] = time();
        $data['create_time'] = $data['update_time'];
        $data['name'] = $_POST['name'];
        $data['expire_month'] = $_POST['expire_month'];
        $data['weight'] = $_POST['weight'];

        $this->model->flush();
        $result = $this->model->add($data);

        //日志信息
        $logInfo = "新增VIP来源权重配置id[" . $result . "]";

        if ($result !== false) {
            save_log($logInfo . L("INSERT_SUCCESS"), 1, '', $data);
            $this->display_success(L("INSERT_SUCCESS"));
        } else {
            save_log($logInfo . L("INSERT_FAILED"), 0);
            $this->error(L("INSERT_FAILED"), 0);
        }
    }

    public function update() {

        $data = array();

        $data['update_time'] = time();
        $data['name'] = $_POST['name'];
        $data['expire_month'] = $_POST['expire_month'];
        $data['weight'] = $_POST['weight'];

        $oldData = $this->model->where("id={$_POST['id']}")->find();

        $result = $this->model->where("id={$_POST['id']}")->save($data);

        $logInfo = "VIP来源权重配置更新id[{$_POST['id']}]";
        if ($result !== false) {
            save_log($logInfo . L("UPDATE_SUCCESS"), 1, $oldData, $data);
            $this->display_success(L("UPDATE_SUCCESS"));
        } else {
            save_log($logInfo . L("UPDATE_FAILED"), 0);
            $this->error(L("UPDATE_FAILED"), 0);
        }
    }

}

?>
