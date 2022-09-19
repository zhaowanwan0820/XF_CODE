<?php
use core\dao\LoanAccountAdjustMoneyModel;
use core\service\ncfph\AccountService as PhAccountService;
use core\service\ncfph\AccountMoneyAdjustService;
use NCFGroup\Common\Library\Idworker;
use libs\db\Db;

/**
 * 网贷调账管理列表
 * @author yangkonghao
 */
class LoanAccountAdjustMoneyAction extends CommonAction
{
    public function index()
    {
        $map = $this->_search();
        if (isset($_REQUEST['status']) && $_REQUEST['status'] == '0')
        {
            $map['status'] = array('in', array(1,2,3,4,5));
        }
        if (!empty($_REQUEST['order_id']))
        {
            $map['order_id'] = addslashes(trim($_REQUEST['order_id']));
        }

        $this->assign("loan_account_adjust_money_status", LoanAccountAdjustMoneyModel::$loan_account_adjust_money_status);
        if (method_exists($this, '_filter')) {
            $this->_filter($map);
        }
        $name = $this->getActionName();
        $model = D($name);
        $this->_list($model, $map);
        $this->display();

    }

    public function get_info(){
        $user_id = trim($_REQUEST['user_id']);
        //echo $user_id;
        if (empty($user_id)) {
            echo json_encode(['status'=>0, 'msg'=>'参数错误']);
            exit;
        }
        $user_info = MI("User")->where("id = $user_id")->find();
        //var_dump($user_info['real_name']); exit();
        $account_info = (new PhAccountService())->getInfoByUserIdAndType($user_info['id'], $user_info['user_purpose']);
        $data = ['status'=>1, 'user_name'=>$user_info['real_name'], 'money'=>$account_info['money']];
        echo json_encode($data);
        exit;
    }

    /**
     * 增加网贷调账申请页面
     */
    public function add()
    {
        save_log('进入网贷调账申请', 1);
        $this->assign("loan_account_adjust_money_type", LoanAccountAdjustMoneyModel::$loan_account_adjust_money_type);
        $this->display();
    }

    /**
     * 处理增加调账申请
     */
    public function doadd()
    {
        $user_id = trim($_REQUEST['user_id']);
        if (empty($user_id)) {
            $this->error("用户ID{$user_id}有误");
        }
        $user_info = MI("User")->where("id = $user_id")->find();
        $account_info = (new PhAccountService())->getInfoByUserIdAndType($user_info['id'], $user_info['user_purpose']);
        $money = floatval($_REQUEST['money']);
        if ($money == 0 || !is_numeric($money)) {
            $this->error("金额{$money}有误");
        }
        $type = trim($_REQUEST['type']);
        if (empty($type)) {
            $this->error('调账类型{$type}有误');
        }

        $datetime = date('Y-m-d H:i:s');
        $adminSession = \es_session::get(md5(conf("AUTH_KEY")));
        $logInfo = $datetime. ' 新增:'.$adminSession['adm_name']."\n";
        $add_data = array(
            'vip_name' => $user_info['user_name'],
            'vip_num' => numTo32($user_info['id']),
            'user_name' => $user_info['real_name'],
            'account_type' => $account_info['accountType'],
            'user_id' => $user_id,
            'money' => $money,
            'type' => $type,
            'create_time' => time(),
            'note' => $_REQUEST['note'],
            'order_id' => Idworker::instance()->getId(),
            'log' => $logInfo,
        );
        $url = u("LoanAccountAdjustMoney/index");
        $GLOBALS['db']->startTrans();
        try {
            $GLOBALS['db']->autoExecute('firstp2p_loan_account_adjust_money', $add_data, 'INSERT');
            $affectRows = $GLOBALS['db']->affected_rows();
            if ($affectRows <= 0) {
                throw new \Exception('添加记录失败');
            }
            $log = '新增调账申请,调账用户ID:'.$user_id.',调账金额:'.format_price($money).',类型：'.$type;
            if (!save_log($log, 1)) {
                throw new \Exception('保存日志失败');
            }
            $GLOBALS['db']->commit();
        }
        catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            $this->error($e->getMessage(), 0, $url);
        }
        $this->success(L("INSERT_SUCCESS"), 0, $url);
    }

    /**
     * 批量导入网贷调账申请
     */
    public function import()
    {
        $this->display();
    }

    /**
     * 处理批量导入网贷调账申请
     */
    public function doimport()
    {
        $filename = isset($_FILES['upfile']['name']) ? trim($_FILES['upfile']['name']) : '';
        if (empty($filename)) {
            $this->error('请选择要上传的文件');
        }

        if (strtolower(substr($filename, -4)) !== '.csv') {
            $this->error('只支持上传csv格式的文件');
        }

        $content = file_get_contents($_FILES['upfile']['tmp_name']);
        $content = trim($content);
        if (iconv('gbk', 'utf-8', $content) !== false) {
            $content = iconv('gbk', 'utf-8', $content);
        }

        //解析文件
        $contentArray = explode("\n", $content);
        if (count($contentArray) < 2) {
            $this->error('上传的文件没有内容');
        }

        array_shift($contentArray);
        $applyRecords = [];
        $service = new AccountMoneyAdjustService();
        $checkResult = $service->checkInfo($contentArray, $applyRecords);
        if ($service->hasErrors())
        {
            $this->error($service->printError());
        }

        $adminSession = \es_session::get(md5(conf("AUTH_KEY")));
        $addResult = $service->batchAdd($applyRecords, $adminSession);
        if (!$addResult)
        {
            $this->error('批量导入网贷调账申请失败,请重新提交');
        }
        $this->success('批量导入网贷调账申请成功');

    }

    public function audit()
    {
        $service = new AccountMoneyAdjustService();
        $ids = explode(',', $_GET['id']);
        if (empty($ids))
        {
            $this->error('id 不能为空');
        }
        $adminSession = \es_session::get(md5(conf("AUTH_KEY")));
        $service->updateStatus($ids, LoanAccountAdjustMoneyModel::STATUS_NEED_FINAL_AUDIT,$adminSession);
        $this->success('A角色审核成功');
    }

    public function disagree()
    {
        $service = new AccountMoneyAdjustService();
        $ids = explode(',', $_GET['id']);
        if (empty($ids))
        {
            $this->error('id 不能为空');
        }
        $adminSession = \es_session::get(md5(conf("AUTH_KEY")));
        $service->updateStatus($ids, LoanAccountAdjustMoneyModel::STATUS_REFUSE_A, $adminSession);
        $this->success('A角色拒绝成功');
    }


    public function refuse()
    {
        $service = new AccountMoneyAdjustService();
        $ids = explode(',', $_GET['id']);
        if (empty($ids))
        {
            $this->error('id 不能为空');
        }
        $adminSession = \es_session::get(md5(conf("AUTH_KEY")));
        $service->updateStatus($ids, LoanAccountAdjustMoneyModel::STATUS_REFUSE_B, $adminSession);
        $this->success('B角色拒绝成功');
    }



    public function finalAudit()
    {
        $service = new AccountMoneyAdjustService();
        $ids = explode(',', $_GET['id']);
        if (empty($ids))
        {
            $this->error('id 不能为空');
        }
        $adminSession = \es_session::get(md5(conf("AUTH_KEY")));
        try {
            $service->batchPass($ids, $adminSession);
        } catch (\Exception $e) {
            $this->error('B角色审核成功,处理资金操作失败,原因:'.$e->getMessage()); 
        }
        $this->success('B角色审核成功');
    }


}
