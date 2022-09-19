<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

class VipBidConfAction extends CommonAction {

    public function __construct()
    {
        parent::__construct();
        $this->model = MI('VipBidConf', 'vip', 'master');
    }

    public function index() {
        $where['is_delete'] = 0;
        $list = $this->_list($this->model, $where);
        if ($list) {
            foreach($list as &$value) {
                $conf = json_decode($value['conf'],true);
                foreach($conf as $k=>$v) {
                    $value[$k] = $v;
                }
                $value['status'] = ($value['status'] == 1) ? '有效' : '无效';
            }
        }
        $this->assign('list', $list);
        $this->display();
        return;

    }

    public function add() {
        $this->display();
    }

    public function edit()
    {
        $id = isset($_REQUEST [$this->pk_name]) ? trim($_REQUEST [$this->pk_name]) : "";
        if (empty($id)) {
            $this->error(l("INVALID_OPERATION"));
        }
        $condition[$this->pk_name] = $id;
        $vo = $this->model->where($condition)->find();
        $vo['conf'] = json_decode($vo['conf'],true);
        $this->assign('vo', $vo);
        $this->display();
    }

    public function insert() {
        $hasConf = $this->model->where("status=1 AND is_delete=0")->find();
        if ($hasConf && ($_POST['status'] == 1)) {
            $this->error("已存在有效的配置", 0);
        }

        $data = array();
        $data['create_time'] = time();
        $data['update_time'] = $data['create_time'];
        $data['status'] = intval($_POST['status']);
        $conf = $_POST['conf'];
        $data['conf'] = json_encode($conf,JSON_UNESCAPED_UNICODE);
        $this->model->flush();
        $result = $this->model->add($data);

        //日志信息
        $logInfo = "新增VIP投资配置文案id[" . $result . "]";

        if ($result !== false) {
            save_log($logInfo . L("INSERT_SUCCESS"), 1, '', $data);
            $this->display_success(L("INSERT_SUCCESS"));
        } else {
            save_log($logInfo . L("INSERT_FAILED"), 0);
            $this->error(L("INSERT_FAILED"), 0);
        }
    }

    public function update() {
        $hasConf = $this->model->where("status=1 AND is_delete=0 AND id !={$_POST['id']}")->find();
        if ($hasConf && ($_POST['status'] == 1)) {
            $this->error("已存在有效的配置", 0);
        }

        $data = array();

        $data['update_time'] = time();
        $data['status'] = $_POST['status'];
        $conf = $_POST['conf'];
        $data['conf'] = json_encode($conf,JSON_UNESCAPED_UNICODE);

        $oldData = $this->model->where("id={$_POST['id']}")->find();

        $result = $this->model->where("id={$_POST['id']}")->save($data);

        $logInfo = "VIP投资文案配置更新id[{$_POST['id']}]";
        if ($result !== false) {
            save_log($logInfo . L("UPDATE_SUCCESS"), 1, $oldData, $data);
            $this->display_success(L("UPDATE_SUCCESS"));
        } else {
            save_log($logInfo . L("UPDATE_FAILED"), 0);
            $this->error(L("UPDATE_FAILED"), 0);
        }
    }

    public function delete() {
        $this->model->flush();
        $id = intval($_REQUEST['id']);
        $data['is_delete'] = 1;
        $data['update_time'] = time();
        $result = $this->model->where("id=$id")->save($data);
        $logInfo = "VIP投资文案配置删除id[$id]";
        if ($result !== false) {
            save_log($logInfo . L("DELETE_SUCCESS"), 1);
            $this->display_success(L("DELETE_SUCCESS"));
        } else {
            save_log($logInfo . L("DELETE_FAILED"), 0);
            $this->error(L("DELETE_FAILED"), 0);
        }
    }
}
