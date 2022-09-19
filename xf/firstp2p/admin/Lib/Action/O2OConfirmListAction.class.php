<?php
/**
 * O2OConfirmList class file.
 *
 * @author luzhengshuai<luzhengshuai@ucfgroup.com>
 * */
use core\service\O2OService;
use core\service\UserService;
use core\dao\OtoConfirmLogModel;

class O2OConfirmListAction extends CommonAction{

    public function __construct() {
        \libs\utils\PhalconRPCInject::init();
        parent::__construct();
        $this->model = M('OtoConfirmLog', 'Model', true);
    }

    public function index() {
        $userService = new UserService();
        //定义条件
        $where = ' 1=1';
        if (!isset($_GET['trans_status'])) {
            $_GET['trans_status'] = 10000;
        }

        $id = intval($_GET['id']);
        $userId = intval($_GET['user_id']);
        $storeId = intval($_GET['store_id']);
        $giftCode = trim($_GET['gift_code']);
        $timeStart = trim($_GET['time_start']);
        $timeEnd = trim($_GET['time_end']);
        $transStatus = intval($_GET['trans_status']);

        if ($id) {
            $where .= " AND id = " . $id;
        }

        if ($userId) {
            $where .= " AND user_id = " . $userId;
        }

        if ($storeId) {
            $where .= " AND store_id = " . $storeId;
        }

        if ($giftCode) {
            $where .= " AND gift_code = " . $giftCode;
        }

        if ($timeStart) {
            $where .= " AND create_time >= '". strtotime($timeStart) ."'";
        }

        if ($timeEnd) {
            $where .= " AND create_time <= '". strtotime($timeEnd) ."'";
        }

        if ($transStatus === 0) {
            $where .= " AND update_time = 0 AND id > 129688";
        } else if ($transStatus === 1) {
            $where .= " AND update_time > 0";
        }

        $this->_list($this->model, $where);
        $result = $this->get('list');

        $this->assign('list', $result);
        $this->display();
    }

    public function reTransfer() {

        $ajax = 1;
        $logId = intval($_REQUEST['id']);
        if (!$logId) {
            $this->error('id不能为空', $ajax);
        }
        $confirmLog = OtoConfirmLogModel::instance()->find($logId, 'store_id, id, gift_id', true);
        if (empty($confirmLog)) {
            $this->error('没有对应的兑换记录', $ajax);
        }
        $o2oService = new O2OService();
        $res = $o2oService->p2pConfirmCoupon($confirmLog['gift_id'], $confirmLog['store_id']);
        if (!$res) {
            $this->error('重发失败', $ajax);
        }

        $this->success('重发成功', $ajax);
    }
}
?>
