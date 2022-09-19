<?php

use core\dao\vip\VipPointResendModel;
use core\service\vip\VipService;

class VipPointResendAction extends CommonAction
{
    public $adminName = '';

    public function __construct()
    {
        parent::__construct();
        $this->assign('send_way_list', VipPointResendModel::$sendType);
        $this->model = MI('VipPointResend', 'vip', 'slave');
        $adm_session = es_session::get(md5(conf("AUTH_KEY")));
        $this->adminName = $adm_session['adm_name'];

    }

    public function index()
    {
        $condition = ' 1=1';
        $status = $_REQUEST['status'] ?: "-1";
        if ($status != "-1") {
            $condition .= ' AND status ='. intval($status);
        }
        $_REQUEST ['listRows'] = 20;
        $this->_list($this->model, $condition);
        $list = $this->get('list');
        $list = $this->formatList($list);

        $this->assign('list', $list);
        $this->assign('statusList', VipPointResendModel::$status);
        $this->display();

    }

    private function formatList($list) {
        foreach($list as &$item) {
            $item['status_desc'] = VipPointResendModel::$status[$item['status']];
            $item['type_desc'] = VipPointResendModel::$sendType[$item['type']];
            $item['send_desc'] = VipPointResendModel::$send_status[$item['send_status']];
        }
        return $list;
    }

    public function manage() {
        $condition = ' 1=1';
        $status = $_REQUEST['status'] ?: "-1";
        if ($status != "-1") {
            $condition .= ' AND status ='. intval($status);
        }
        $_REQUEST ['listRows'] = 20;
        $this->_list($this->model, $condition);
        $list = $this->get('list');
        $list = $this->formatList($list);

        $this->assign('list', $list);
        $this->assign('statusList', VipPointResendModel::$status);
        $this->display();
    }


    public function add()
    {
        $this->display();
    }

    public function insert()
    {
        $data['create_time'] = time();
        $data['point'] = intval($_POST['point']);
        $data['remark'] = $_POST['remark'];
        $data['type'] = $_POST['type'];
        $data['send_condition'] = $_POST['send_condition'];
        $data['source_name'] = $_POST['source_name'];
        $data['create_user'] = $this->adminName;
        $data['send_status'] = VipPointResendModel::SEND_STATUS_INIT;

        $id = VipPointResendModel::instance()->addTask($data);
        if (!$id) {
            $this->error(L("INSERT_FAILED"));
        }

        $this->success("创建成功");
        $this->redirect(u(MODULE_NAME."/index"));

    }


    public function view() {
        $id = intval($_REQUEST['id']);
        $task = VipPointResendModel::instance()->getTask($id);
        $this->assign('item', $task);
        $this->display();
    }

    public function verify() {
        $id = intval($_REQUEST['id']);
        $task = VipPointResendModel::instance()->getTask($id);
        $this->assign('item', $task);
        $this->display();
    }

    public function doVerify() {
        $result = array("status" => 1, "errorMsg" => '');
        $id = intval($_REQUEST['id']);
        $type = $_REQUEST['type'];
        $reason = trim($_REQUEST['reason']);
        if ($type == VipPointResendModel::STATUS_FAILED && empty($reason)) {
            $result['status'] = 0;
            $result['errorMsg'] = "审批意见不能为空";
        } else {
            $data = array("id" => $id, "status" => $type, "reason" => $reason, "verify_user" => $this->adminName, 'verify_time' => time(), 'send_status' => ($type == VipPointResendModel::STATUS_SUCCESS) ? VipPointResendModel::SEND_STATUS_WORKING : VipPointResendModel::SEND_STATUS_INIT);
            $res = VipPointResendModel::instance()->updateTask($data);
            if($res && ($type == VipPointResendModel::STATUS_SUCCESS)) {
                VipService::resendPointTask($id);
            }
        }
        echo json_encode($result);
        return;
    }
}
