<?php

use core\service\O2OService;
use libs\utils\Curl;
use core\dao\OtoCouponResendModel;

class O2OCouponResendAction extends CommonAction
{
    public $adminName = '';
    public static $sendTypeList = array(
        '1' => '用户id',
        '2' => '导入csv',
    );

    public function __construct()
    {
        parent::__construct();
        $this->assign('send_way_list', self::$sendTypeList);
        $this->model = M('OtoCouponResend', 'Model', true);
        $adm_session = es_session::get(md5(conf("AUTH_KEY")));
        $this->adminName = $adm_session['adm_name'];

    }

    public function index()
    {
        $condition = ' 1=1';
        $couponGroupId = $_REQUEST['couponGroupId'] ?: 0;
        if ($couponGroupId) {
            $condition .= ' AND coupon_group_id = '. $couponGroupId;
        }
        $status = $_REQUEST['status'] ?: "-1";
        if ($status != "-1") {
            $condition .= ' AND status ='. intval($status);
        }
        $_REQUEST ['listRows'] = 20;
        $this->_list($this->model, $condition);
        $list = $this->get('list');
        $list = $this->formatList($list);

        $this->assign('list', $list);
        $this->assign('statusList', OtoCouponResendModel::$status);
        $this->display();

    }

    private function formatList($list) {
        $static_host = app_conf('STATIC_HOST');
        foreach($list as &$item) {
            if ($item['type'] == OtoCouponResendModel::TYPE_CSV) {
                $item['user_id_list'] = (substr($static_host, 0, 4) == 'http' ? '' : 'http:') . $static_host .'/' . $item['user_id_list'];
            }
            $item['status_desc'] = OtoCouponResendModel::$status[$item['status']];
        }
        return $list;
    }

    public function manage() {
        $condition = '1=1';
        $couponGroupId = $_REQUEST['couponGroupId'] ?: 0;
        if ($couponGroupId) {
            $condition .= ' AND coupon_group_id = '. $couponGroupId;
        }
        $status = $_REQUEST['status'] ?: "-1";
        if ($status != "-1") {
            $condition .= ' AND status ='. intval($status);
        } else {
            $condition .= " AND status in (". OtoCouponResendModel::STATUS_INIT. "," . OtoCouponResendModel::STATUS_SUCCESS.")";
        }
        $_REQUEST ['listRows'] = 20;
        $this->_list($this->model, $condition);
        $list = $this->get('list');
        $list = $this->formatList($list);

        $this->assign('list', $list);
        $this->assign('statusList', OtoCouponResendModel::$status);
        $this->display();
    }


    public function add()
    {
        $this->display();
    }

    public function insert()
    {
        $data['create_time'] = time();
        $data['coupon_group_name'] = $_POST['couponGroupName'];
        $data['coupon_group_id'] = $_POST['couponGroupId'];
        $data['remark'] = $_POST['remark'];
        $data['type'] = $_POST['send_way'];
        $data['user_id_list'] = $_POST['send_condition'];
        $data['create_user'] = $this->adminName;
        if (!empty($_FILES['send_condition']['tmp_name'])) {
            $uploadFileInfo = array(
                'file' => $_FILES['send_condition'],
                'asAttachment' => 1,
            );
            $result = uploadFile($uploadFileInfo);
            if(empty($result['full_path'])) {
                $this->error('上传失败！');
            } else {
                //屏蔽导入手机号的选项
                $data['import_type'] = 1;
                $data['user_id_list'] = $result['full_path'];
            }
        }

        $id = OtoCouponResendModel::instance()->addTask($data);
        if (!$id) {
            $this->error(L("INSERT_FAILED"));
        }

        $this->success("创建成功");
        $this->redirect(u(MODULE_NAME."/index"));

    }


    public function view() {
        $id = intval($_REQUEST['id']);
        $task = OtoCouponResendModel::instance()->getTask($id);
        $this->assign('item', $task);
        $this->display();
    }

    public function verify() {
        $id = intval($_REQUEST['id']);
        $task = OtoCouponResendModel::instance()->getTask($id);
        $this->assign('item', $task);
        $this->display();
    }

    public function doVerify() {
        $result = array("status" => 1, "errorMsg" => '');
        $id = intval($_REQUEST['id']);
        $type = $_REQUEST['type'];
        $reason = trim($_REQUEST['reason']);
        if ($type == OtoCouponResendModel::STATUS_FAILED && empty($reason)) {
            $result['status'] = 0;
            $result['errorMsg'] = "审批已经不能为空";
        } else {
            $data = array("id" => $id, "status" => $type, "reason" => $reason, "verify_user" => $this->adminName, 'verify_time' => time());
            $res = OtoCouponResendModel::instance()->updateTask($data);
            if($res && ($type == OtoCouponResendModel::STATUS_SUCCESS)) {
                O2OService::addResendTask($id);
            }
        }
        echo json_encode($result);
        return;
    }

    public function getGroupName() {
        $result = array('status' => 1, 'name' => '');
        $id = intval($_REQUEST['id']);
        $o2oService = new O2OService();
        $res = $o2oService->getCouponGroupInfoById($id);
        if ($res) {
            $result['name'] = $res['productName'];
        } else {
            $result['status'] = 0;
            $result['errorMsg'] = '券组不存在';
        }
        echo json_encode($result);
        return;

    }

    /**
     * 下载模板
     */
    public function download()
    {
        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment; filename=直推任务模板.csv");
        header('Cache-Control: max-age=0');
        $fp = fopen('php://output', 'a');
        fputcsv($fp, array("userId/mobile"));
    }
}
