<?php

use libs\utils\PaymentApi;
use core\service\UserService;
use core\service\UserCarryService;
use core\service\SupervisionBaseService;
use core\service\SupervisionAccountService;
use core\service\SupervisionFinanceService;
use core\service\SupervisionService;
use libs\common\WXException;
use core\dao\SupervisionBackendTransferModel;
use core\dao\SupervisionTransferModel;
use NCFGroup\Common\Library\Idworker;
use NCFGroup\Common\Library\GTM\GlobalTransactionManager;
use NCFGroup\Common\Library\GTM\Toolkit\EventMaker;

//ini_set('memory_limit', '2048M');
//ini_set('display_errors', 1);
//error_reporting(E_ALL);

class SupervisionAction extends CommonAction
{
    public function index()
    {
        $status = isset($_REQUEST['status']) ? intval($_REQUEST['status']) : -1;
        $auth_action = [];
        $is_auth = $this->is_have_action_auth(MODULE_NAME, 'doAudit');
        if ($is_auth && in_array($status, [-1, 0])) {
            $auth_action[] = array('a' => 'doAudit', 'p' => SupervisionBackendTransferModel::AUDIT_STATUS_PASS, 'r'=>SupervisionBackendTransferModel::AUDIT_STATUS_REFUSE, 'n' => 'A角色');
        }
        $is_auth_final = $this->is_have_action_auth(MODULE_NAME, 'doFinalAudit');
        if ($is_auth_final && in_array($status, [-1, 1])) {
            $auth_action[] = array('a' => 'doFinalAudit', 'p' => SupervisionBackendTransferModel::AUDIT_STATUS_FINAL_PASS, 'r' => SupervisionBackendTransferModel::AUDIT_STATUS_FINAL_REFUSE, 'n' => 'B角色');
        }
        $this->assign('auth_action', $auth_action);
        $map = array();
        if(isset($_GET['transfer_status'])) {
            $map['transfer_status'] =  intval($_GET['transfer_status']);
        }

        $_REQUEST['listRows'] = isset($_REQUEST['listRows']) ? intval($_REQUEST['listRows']) : 20;
        if ($status == -1 || !isset($_REQUEST['status'])) {
            $_REQUEST['status'] = -1;
            $map['audit_status'] = array('in' ,[0,1,2,3,4]);
        }
        else if($status === -2) {
            $map['audit_status'] = array('in', [2,4]);
        }
        else {
            $map['audit_status'] = $status;
        }

        $outOrderId = trim($_REQUEST['out_order_id']);
        if ($outOrderId != '') {
            $map['out_order_id'] = $outOrderId;
        }

        $applyUserName = trim($_REQUEST['apply_user']);
        if ($applyUserName != '') {
            $map['apply_user_name'] = $applyUserName;
        }

        $userName = trim($_REQUEST['user_name']);
        if ($userName != '') {
            $userId = SupervisionBackendTransferModel::instance()->db->get_slave()->getOne("SELECT id FROM firstp2p_user WHERE user_name = '{$userName}'");
            $map['user_id'] = $userId;
        }

        $apply_time_start = $apply_time_end = 0;
        if (!empty($_REQUEST['apply_time_start'])) {
            $apply_time_start = strtotime(trim($_REQUEST['apply_time_start']));
            $map['create_time'] = array('egt', $apply_time_start);
        }
        if (!empty($_REQUEST['apply_time_end'])) {
            $apply_time_end = strtotime(trim($_REQUEST['apply_time_end']));
            $map['create_time'] = array('between', sprintf('%s,%s', $apply_time_start, $apply_time_end));
        }

        $p = isset($_GET['p']) ? intval($_GET['p']) : 1;
        $this->_list(MI('SupervisionBackendTransfer'), $map);
        $this->assign('transfer_status', $status);
        $this->assign('p', $p);
        $this->display('index');

    }

    /**
     * A角色审核-批量
     * @throws WXException
     */
    public function doAudit() {
        $ret = ['failMsg'=>''];
        // 获取ID数组
        $ids = $this->get_id_list();
        $isBatch = intval($_REQUEST['is_batch']);
        foreach ($ids as $id) {
            $this->_doAuditOne($id, $isBatch, $ret);
        }
        if ($isBatch == 1) {
            $auditMsg = sprintf('一共执行%d笔，%d笔成功，%d笔失败或已审核。', count($ids), count($ret['success']), count($ret['fail']));
            ajax_return(['status'=>'OK', 'msg'=>$auditMsg, 'error'=>join(',', $ret['failMsg'])]);
        }
    }

    /**
     * A角色审核-单个
     * @param int $id
     * @param int $isBatch
     * @param array $ret
     * @throws WXException
     */
    private function _doAuditOne($id, $isBatch = 0, &$ret = []) {
        $result = ['id'=>$id, 'status' => -1, 'msg' => '操作失败'];
        $backendTransferModel = SupervisionBackendTransferModel::instance();
        try {
            $backendTransferModel->db->startTrans();
            if (empty($id) || empty($_REQUEST['audit_status'])) {
                throw new WXException('ERR_PARAM');
            }
            $id = intval($id);
            $ajax = intval($_REQUEST['ajax']);
            $status = intval($_REQUEST['audit_status']);
            $transferRecord = $backendTransferModel->find($id);
            if (empty($transferRecord)) {
                throw new WXException('ERR_PARAM');
            }
            if ($transferRecord['audit_status'] != SupervisionBackendTransferModel::AUDIT_STATUS_NORMAL) {
                throw new WXException('ERR_TRANSFER_STATUS_NOT_ALLOWED');
            }
            if ($status == SupervisionBackendTransferModel::AUDIT_STATUS_PASS) {
                if ($transferRecord['direction'] == SupervisionTransferModel::DIRECTION_TO_SUPERVISION) {
                    $usercarryService = new UserCarryService();
                    $canTransferAmount = $usercarryService->canTransferAmount($transferRecord['user_id'], bcdiv($transferRecord['amount'], 100, 2));
                    if (!$canTransferAmount) {
                        throw new WXException('ERR_MONEY_NOT_ENOUGH');
                    }
                }

                $checkMoneyBeforeTransfer = $backendTransferModel->checkMoneyBeforeTransfer($transferRecord);
                if(!$checkMoneyBeforeTransfer) {
                    throw new WXException('ERR_MONEY_NOT_ENOUGH');
                }
            }

            $doAuditResult = $backendTransferModel->doAudit($id, $status);
            if (!$doAuditResult) {
                throw new WXException('ERR_TRANSFER_ORDER_UPDATE');
            }
            $backendTransferModel->db->commit();
            $result['status'] = 'OK';
            $result['msg'] = '操作成功';
            $ret['success'][] = $result;
        } catch(\Exception $e) {
            $backendTransferModel->db->rollback();
            $result['msg'] = $e->getMessage();
            $result['status'] = $e->getCode();
            $ret['fail'][] = $result;
            $ret['failMsg'][] = $id . '-' . $result['msg'];
        }
        if ($isBatch != 1) {
            ajax_return($result);
            exit;
        }else{
            return $ret;
        }
    }

    public function transfer() {
        if (empty($_REQUEST['id'])) {
            return false;
        }
        $id = intval($_REQUEST['id']);
        $userService = new UserService();
        $accountService = new SupervisionAccountService;
        $userInfo = $userService->getUser($id);
        $userInfo['moneyFormat'] = format_price($userInfo['money']);
        //$isSvUser = $accountService->isSupervisionUser($userInfo);
        $accountInfo = (new \core\service\ncfph\AccountService())->getInfoByUserIdAndType($userInfo['id'], $userInfo['user_purpose']);
        $isSvUser = $accountInfo['isSupervisionUser'];
        if (!$isSvUser) {
            echo "<script>alert('用户尚未开通网贷P2P账户,操作取消'); $.weeboxs.close(); </script>";
            exit;
        }

        //$thirdBalanceInfo = MI('UserThirdBalance')->where(" user_id = '$id'")->find();
        //if (empty($thirdBalanceInfo)) {
        //    $thirdBalanceInfo = ['supervision_balance' => 0.00, 'supervision_lock_money' => 0.00];
        //}
        $userInfo['supervisionMoneyFormat'] = format_price($accountInfo['money']);
        $hasPrivilege = $accountService->checkUserPrivileges($id, [SupervisionBaseService::GRANT_WITHDRAW_TO_SUPER]);
        $this->assign('svMoney', $accountInfo['money']);
        $this->assign('ptMoney', $userInfo['money']);
        $this->assign('isSvUser', $hasPrivilege ? '1' : '0');
        $this->assign('userInfo', $userInfo);
        $this->display();
    }

    /**
     * B角色审核-批量
     */
    public function doFinalAudit() {
        $ret = ['failMsg'=>''];
        // 获取ID数组
        $ids = $this->get_id_list();
        $isBatch = intval($_REQUEST['is_batch']);
        foreach ($ids as $id) {
            $this->_doFinalAuditOne($id, $isBatch, $ret);
        }
        if ($isBatch == 1) {
            $auditMsg = sprintf('一共执行%d笔，%d笔成功，%d笔失败或已审核。', count($ids), count($ret['success']), count($ret['fail']));
            ajax_return(['status'=>'OK', 'msg'=>$auditMsg, 'error'=>join(',', $ret['failMsg'])]);
        }
    }

    /**
     * B角色审核-单个
     * @param int $id
     * @param int $isBatch
     * @param array $ret
     * @throws WXException
     */
    private function _doFinalAuditOne($id, $isBatch = 0, &$ret = []) {
        $result = array('id'=>$id, 'status' => -1, 'msg' => '操作失败');
        $id = intval($id);
        if (empty($id)) {
            $result['msg'] = '参数错误';
            if ($isBatch != 1) {
                ajax_return($result);
            }else{
                $ret['fail'][] = $result;
                $ret['failMsg'][] = $id . '-' . $result['msg'];
                return $ret;
            }
        }

        $backendTransferModel = SupervisionBackendTransferModel::instance();
        try {
            if (empty($id) || empty($_REQUEST['audit_status'])) {
                throw new WXException('ERR_PARAM');
            }
            $status = intval($_REQUEST['audit_status']);
            $transferRecord = $backendTransferModel->find($id);
            if (empty($transferRecord)) {
                throw new WXException('ERR_PARAM');
            }
            if ($transferRecord['audit_status'] != SupervisionBackendTransferModel::AUDIT_STATUS_PASS) {
                throw new WXException('ERR_TRANSFER_STATUS_NOT_ALLOWED');
            }
            // 检查用户余额
            $doFinalAuditResult = $backendTransferModel->doFinalAudit($id, $status);
            if ($doFinalAuditResult != true) {
                throw new \WXException('ERR_TRANSFER_ORDER_FAILED');
            }

            // {{{ 划转请求
            if ($status == SupervisionBackendTransferModel::AUDIT_STATUS_FINAL_PASS) {
                if ($transferRecord['direction'] == SupervisionTransferModel::DIRECTION_TO_SUPERVISION) {
                    $usercarryService = new UserCarryService();
                    $canTransferAmount = $usercarryService->canTransferAmount($transferRecord['user_id'], bcdiv($transferRecord['amount'], 100, 2));
                    if (!$canTransferAmount) {
                        throw new WXException('ERR_MONEY_NOT_ENOUGH');
                    }
                }
                //检查 存管余额是否足够
                $checkMoneyBeforeTransfer = $backendTransferModel->checkMoneyBeforeTransfer($transferRecord);
                if (!$checkMoneyBeforeTransfer) {
                    throw new WXException('ERR_MONEY_NOT_ENOUGH');
                }

                $params = [];
                $params['userId'] = $transferRecord['user_id'];
                $params['amount'] = $transferRecord['amount'];
                $params['orderId'] = $transferRecord['out_order_id'];
                $params['currency'] = 'CNY';
                $params['superUserId'] = $transferRecord['user_id'];
                $direction = $transferRecord['direction'];
                $supervisionService = new SupervisionService();
                $requestResult = $supervisionService->requestSupervisionInterface($direction, $params);
                if ($requestResult != true) {
                    throw new \Exception('余额划转批准失败');
                }
            }
            //}}}
            $result['status'] = 'OK';
            $result['msg'] = '操作成功';
            $ret['success'][] = $result;
        } catch(\Exception $e) {
            $result['status'] = $e->getCode();
            $result['msg'] = $e->getMessage();
            $ret['fail'][] = $result;
            $ret['failMsg'][] = $id . '-' . $result['msg'];
        }
        if ($isBatch != 1) {
            ajax_return($result);
        }else{
            return $ret;
        }
    }

    /**
     * A角色申请
     */
    public function doTransfer() {
        $result = array('status' => -1, 'msg' => '操作失败');
        try {
            if (empty($_REQUEST['amount']) || empty($_REQUEST['id']) || empty($_REQUEST['direction'])) {
                throw new WXException('ERR_PARAM');
            }
            $outOrderId = Idworker::instance()->getId();
            if (empty($outOrderId)) {
                throw new WXException('ERR_IDWORKER');
            }

            $amount = floatval($_REQUEST['amount']);
            $userId = intval($_REQUEST['id']);
            $memo = addslashes(trim($_REQUEST['remark']));
            $direction = intval($_REQUEST['direction']);

            // 网信理财划转到超级账户,发起时判断用户可用是否足够
            if($direction == SupervisionTransferModel::DIRECTION_TO_SUPERVISION) {
                $usercarryService = new UserCarryService();
                $canTransferAmount = $usercarryService->canTransferAmount($userId, $amount);
                if (!$canTransferAmount) {
                    throw new WXException('ERR_MONEY_NOT_ENOUGH');
                }
            }

            $amount = bcmul($amount, 100);
            $backendTransferModel = SupervisionBackendTransferModel::instance();
            $createOrderResult = $backendTransferModel->createOrder($userId, $amount, $outOrderId, $direction, $memo);
            if ($createOrderResult!= true) {
                PaymentApi::log('Supervision transfer failed');
                throw new \Exception('余额划转申请失败');
            }
            $result['status'] = 0;
        } catch(\Exception $e) {
            $result['status'] = -1;
            $result['msg'] = $e->getMessage();
        }
        ajax_return($result);
    }

    /**
     * 清空[易宝补单重试列表]
     */
    public function clearRetryList()
    {
        $result = array('status' => -1, 'msg' => '清理失败');
        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST')
        {
            $yeepayPaymentService = new \core\service\YeepayPaymentService();
            $ret = $yeepayPaymentService->clearRepairRetryList();
            $result = $ret ? array('status' => 0, 'msg' => '清理完毕') : array('code' => -2, 'msg' => '已清理');
        }
        ajax_return($result);
    }

    /**
     * 存管行资金记录明细
     * @param integer $userId  用户id
     */
    public function  userLog() {
        $userId = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
        if (empty($userId)) {
            echo '<script>alert("无效的用户Id"); window.close();</script>';
            exit;
        }
        $accountService = new SupervisionAccountService();
        $isSvUser = $accountService->isSupervisionUser($userId);
        if ($isSvUser === false) {
            echo '<script>alert("用户尚未开通网贷P2P账户"); window.close();</script>';
            exit;
        }

        $supervision = new \core\service\SupervisionAccountService();
        $result = $supervision->memberLog($userId);
        echo $result['data']['form'];
        echo '<script>document.forms[0].submit();</script>';
    }

    /**
     * 查看用户在存管系统的概览页面
     */
    public function userInfo() {
        $userId = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
        if (empty($userId)) {
            echo '<script>alert("无效的用户Id"); window.close();</script>';
            exit;
        }
        $accountService = new SupervisionAccountService();
        $isSvUser = $accountService->isSupervisionUser($userId);
        if ($isSvUser === false) {
            echo '<script>alert("用户尚未开通网贷P2P账户"); window.close();</script>';
            exit;
        }

        $supervision = new \core\service\SupervisionAccountService();
        $result = $supervision->memberInfo($userId);
        echo $result['data']['form'];
        echo '<script>document.forms[0].submit();</script>';
    }

    /**
     * 导出用户提现列表
     */
    public function export_csv()
    {

        set_time_limit(0);
        @ini_set('memory_limit', '300M');
        $user = D("User");
        $condition = '1=1';
        if (trim($_REQUEST['user_name']) != '') {
            $userId = DI("User")->where("user_name='".trim($_REQUEST['user_name'])."'")->getField('id');
            $condition .= " AND user_id = '$userId'";
        }

        $applyUserName = addslashes(trim($_REQUEST['apply_user']));
        if($applyUserName){
            $condition .= " AND apply_user_name = '$applyUserName'";
        }
        $status = intval($_REQUEST['status']);
        if ($status === -1) {
            $_REQUEST['status'] = -1;
            $condition .= ' AND audit_status IN (0,1,2,3,4) ';
        }
        else if($status === -2) {
            $condition .= ' AND audit_status IN (2,4)';
        }
        else {
            $condition .= ' AND audit_status = '.$status;
        }

        //添加搜索条件，编号区间
        if (!empty($_REQUEST['apply_time_start'])) {
            $apply_time_start = strtotime($_REQUEST['apply_time_start']);
            $condition .= " AND  create_time >= " . $apply_time_start;
        }

        if (!empty($_REQUEST['apply_time_end'])) {
            $apply_time_end = strtotime($_REQUEST['apply_time_end']);
            $condition .= " AND  create_time <= " . $apply_time_end;
        }

        if (!empty($_REQUEST['out_order_id'])) {
            $condition .= ' AND out_order_id = ' .intval($_REQUEST['out_order_id']);
        }

        $sql = "SELECT * FROM " .DB_PREFIX. "supervision_backend_transfer WHERE $condition ORDER BY id DESC";

        //记录导出日志
        setLog(
            array(
                'sensitive' => 'exportbackendtransfer',
                'analyze' => $sql
                )
        );


        $res = \libs\db\Db::getInstance('firstp2p', 'slave')->query($sql);
        $datatime = date("YmdHis");
        header('Content-Type: application/vnd.ms-excel;charset=utf8');
        header("Content-Disposition: attachment; filename={$datatime}.csv");

        $title = array(
                '编号',
                '会员名称', '外部订单号', '划转金额',
                '划转方向', '审核状态', '审批记录', '申请人','申请时间', '备注'
        );

        foreach ($title as $k => $v) {
            $title[$k] = iconv("utf-8", "gbk//IGNORE", $v);
        }

        $count = 1;
        $limit = 10000;
        $fp = fopen('php://output', 'w+');
        fputcsv($fp, $title);

        while($v = $GLOBALS['db']->fetchRow($res)) {
            $auditLog = '';
            if (in_array($v['audit_status'], [1,2])) {
                $auditLog = date('Y-m-d H:i:s', $v['first_audit_time']) .' '.$this->_show_audit_log_status($v['audit_status']).' '.$v['first_audit_admin_name'];
            }
            else if (in_array($v['audit_status'], [3,4])) {
                $auditLog = date('Y-m-d H:i:s', $v['first_audit_time']) .' A角色批准'.' '.$v['first_audit_admin_name'];
                $auditLog .= date('Y-m-d H:i:s', $v['final_audit_time']) .' '.$this->_show_audit_log_status($v['audit_status']).' '.$v['final_audit_admin_name'];
            }
            else {
                $auditLog = '';
            }
            $arr = array();
            $arr[] = $v['id'];
            $arr[] = $user->where("id=".$v['user_id'])->getField("user_name");
            $arr[] = $v['out_order_id']." \t ";
            $arr[] = format_price(bcdiv($v['amount'], 100, 2));
            $arr[] = $this->_show_direction_name($v['direction']);
            $arr[] = $this->_show_audit_status($v['audit_status']);
            $arr[] = $auditLog;
            $arr[] = $v['apply_user_name'];
            $arr[] = date('Y-m-d H:i:s', $v['create_time']);
            $arr[] = $v['memo'];

            foreach ($arr as $k => $v){
                $arr[$k] = iconv("utf-8", "gbk//IGNORE", strip_tags($v));
            }
            $count++;
            if ($count % $limit == 0) { //刷新一下输出buffer，防止由于数据过多造成问题
                ob_flush();
                flush();
                $count = 0;
            }
            fputcsv($fp, $arr);
        }
        exit;
    }


    private function _show_direction_name($direction) {
        if ($direction == 1) {
            return '网信理财账户划转至网贷账户';
        } else if ($direction == 2) {
            return '网贷账户划转至网信理财账户';
        }
    }


    private function _show_audit_status($status) {
        if ($status == 0) {
            return 'A角色待审核';
        } else if ($status == 1) {
            return 'B角色待审核';
        } else if ($status == 2 || $status == 4) {
            return '已拒绝';
        } else if ($status == 3) {
            return '审核通过';
        }
    }

    private function _show_audit_log_status($status) {
        if ($status == 0) {
            return 'A角色待审核';
        } else if ($status == 1) {
            return 'A角色批准';
        } else if ($status == 2) {
            return 'A角色拒绝';
        } else if ($status == 3) {
            return 'B角色批准';
        } else if ($status == 4) {
            return 'B角色拒绝';
        }
    }

    public function import() {
        $this->display();
    }

    public function do_import()
    {
        //文件检查
        if ($_FILES['upfile']['error'] == 4) {
            $this->error("请选择文件！");
            exit;
        }
        if ($_FILES['upfile']['type'] != 'text/csv' && $_FILES['upfile']['type'] != 'application/vnd.ms-excel') {
            $this->error("请上传csv格式的文件！");
            exit;
        }
        set_time_limit(0);
        $max_line_num = 10000;
        ini_set('memory_limit', '2G');
        $file_line_num = count(file($_FILES['upfile']['tmp_name']));
        if ($file_line_num > $max_line_num + 1) {
            $this->error("处理的数据不能超过{$max_line_num}行");
        }

        //读取csv数据
        $row_no = 1;
        $user_model = new \core\dao\UserModel();
        $row_head_array = array('会员名称', '划转类型', '划转金额', '备注');
        $list = array();
        if (($handle = fopen($_FILES['upfile']['tmp_name'], "r")) !== FALSE) {
            while (($row = fgetcsv($handle)) !== FALSE) {
                if ($row_no == 1) { //第一行标题，检查标题行
                    if (count($row) != count($row_head_array)) {
                        $this->error("第一行标题不正确！");
                        exit;
                    }
                    for ($i = 0; $i < count($row_head_array); $i++) {
                        $row[$i] = trim(htmlspecialchars(iconv('GB2312', 'UTF-8', $row[$i])));
                        if ($row[$i] != $row_head_array[$i]) {
                            $this->error("第" . ($i + 1) . "列标题不正确！应为'{$row_head_array[$i]}'");
                            exit;
                        }
                    }
                } else { //数据
                    $item = array();
                    $item['type'] = 1; //会员转账
                    $item['transferUsername'] = trim(htmlspecialchars(iconv('GB2312', 'UTF-8', $row[0])));
                    $item['transferDirection'] = trim(htmlspecialchars(iconv('GB2312', 'UTF-8', $row[1])));
                    $item['amount'] = trim(htmlspecialchars(iconv('GB2312', 'UTF-8', $row[2])));
                    $item['memo'] = trim(htmlspecialchars(iconv('GB2312', 'UTF-8', $row[3])));
                    //检查数据
                    if (empty($item['transferUsername']) || empty($item['transferDirection']) || ($item['transferDirection'] != 1 && $item['transferDirection'] != 2)) {
                        $this->error("第{$row_no}行'{$row_head_array[0]}'或'{$row_head_array[1]}'不正确！");
                        exit;
                    }
                    if (!is_numeric($item['amount']) || $item['amount'] <= 0 || $item['amount'] > 99999999.99) {
                        $this->error("第{$row_no}行'{$row_head_array[2]}'不正确！请填写正确数值，介于0 - 99999999.99之间");
                        exit;
                    }
                    if (strlen($item['memo']) > 450) {
                        $this->error("第{$row_no}行'{$row_head_array[3]}'不正确！不能超过450字节");
                        exit;
                    }
                    if ($item['transferDirection'] == 2) {
                        $transferUser = $user_model->getInfoByName($item['transferUsername']);
                        if (empty($transferUser)) {
                            $this->error("第{$row_no}行'{$row_head_array[0]}'或'{$row_head_array[1]}'不正确！");
                            exit;
                        }
                        $userService = new SupervisionAccountService();
                        if (!$userService->checkUserPrivileges($transferUser['id'], [SupervisionBaseService::GRANT_WITHDRAW_TO_SUPER])) {
                            $this->error("第{$row_no}行 用户没有开通免密划转权限");
                            exit;
                        }
                    }
                    $list[] = $item;
                }
                $row_no++;
            }
            fclose($handle);
            @unlink($_FILES['upfile']['tmp_name']);
        }

        if (empty($list)) {
            $this->error("导入数据为空！");
            exit;
        }
        //导入
        $adm_session = es_session::get(md5(conf("AUTH_KEY")));
        $adm_name = $adm_session['adm_name'];
        $adm_id = intval($adm_session['adm_id']);

        $row_no = 2;
        try {
            $GLOBALS['db']->startTrans();
            foreach ($list as $item) {
                //检查用户名
                $transferUser = $user_model->getInfoByName($item['transferUsername']);
                //入记录
                $backendTransferModel = SupervisionBackendTransferModel::instance();
                $outOrderId = Idworker::instance()->getId();
                $amount = bcmul($item['amount'], 100);
                $direction = SupervisionTransferModel::DIRECTION_TO_SUPERVISION;
                if ($item['transferDirection'] == 2) {
                    $direction = SupervisionTransferModel::DIRECTION_TO_WX;
                }
                $userId = $transferUser['id'];
                $memo = addslashes(trim($item['memo']));
                $createOrderResult = $backendTransferModel->createOrder($userId, $amount, $outOrderId, $direction, $memo);
                if ($createOrderResult != true) {
                    throw new \Exception('余额划转申请失败,第'.$row_no.'行数据有误');
                }
                $row_no++;
            }
            $GLOBALS['db']->commit();
            $this->success("导入成功，导入数据" . count($list) . "条！");
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            $this->error("导入失败！");
        }
    }

}
