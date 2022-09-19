<?php

/**
 * 提现限制类
 */
use NCFGroup\Protos\Ptp\Enum\UserAccountEnum;
class WithdrawLimitAction extends CommonAction {


    public function index() {
        $map = $_map =[];
        $map['_string'] = $this->_getWangxinCondition();
        $this->_index($map);
        $this->display();
    }

    public function _getWangxinCondition()
    {
        return '(platform = '.UserAccountEnum::PLATFORM_SUPERVISION.' AND account_type != '.UserAccountEnum::ACCOUNT_FINANCE.') or platform = '.UserAccountEnum::PLATFORM_WANGXIN;
    }

    public function _getSupervisionCondition()
    {
        return 'platform = '.UserAccountEnum::PLATFORM_SUPERVISION.' AND account_type = '.UserAccountEnum::ACCOUNT_FINANCE;
    }

    public function index_supervision()
    {
       $map = [];
       $map['_string'] = $this->_getSupervisionCondition();
       $this->_index($map);
       $this->assign('isSupervision', 1);
       $this->display('index');
    }

    public function _index($map)
    {
        $model = DI ("WithdrawLimit");
        $adm_session = \es_session::get(md5(conf("AUTH_KEY")));
        $map['state'] = array('eq', 0);
        if(isset($_REQUEST['id'])) {
            $map['user_id'] = array('eq', intval($_REQUEST['id']));
        }
        $this->_list($model, $map);
        $this->assign('accountMap', UserAccountEnum::$accountDesc);
        $this->assign('cur_adm_id', $adm_session['adm_id']);
    }

    public function _cancel($map)
    {
        $model = DI ("WithdrawLimit");
        $map['state'] = array('eq', 2);
        $map['cancel_state'] = array('in', array(0,1));
        $adm_session = \es_session::get(md5(conf("AUTH_KEY")));
        if(isset($_REQUEST['id'])) {
            $map['user_id'] = array('eq', intval($_REQUEST['id']));
        }
        $this->_list($model, $map);
        $this->assign('cur_adm_id', $adm_session['adm_id']);
        $this->assign('accountMap', UserAccountEnum::$accountDesc);
        $this->assign('limit_types', \core\service\UserCarryService::$withdrawLimitTypeCn);

    }

    public function cancel() {
        $map = [];
        $map['_string'] = $this->_getWangxinCondition();
        $this->_cancel($map);
        $this->display();
    }

    public function cancel_supervision() {
        $map = [];
        $map['_string'] = $this->_getSupervisionCondition();
        $this->_cancel($map);
        $this->assign('isSupervision', 1);
        $this->display('cancel');
    }

    public function doCancelAudit() {
        $status = isset($_GET['status']) ? intval($_GET['status']) : -1;
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if ($status  === -1 or $id === 0) {
            $this->error('无效的操作');
        }
        $adm_session = \es_session::get(md5(conf("AUTH_KEY")));
        $permissions = array();
        $msg = '';
        if ($status == \core\service\UserCarryService::WITHDRAW_LIMIT_PASSED || $status == \core\service\UserCarryService::WITHDRAW_LIMIT_REFUSED) {
            $permissions = explode(',', app_conf('WITHDRAW_LIMIT_REVIEW'));
            $msg = '您无权审核限制提现/投资。';
        }
        else if ($status == 1) {
            $permissions = explode(',', app_conf('WITHDRAW_LIMIT_APPLY'));
            $msg = '您无权发起限制提现/投资取消申请。';
        }
        if (!in_array($adm_session['adm_role_id'], $permissions)) {
            $this->error($msg);
            return;
        }

        $carryService = new \core\service\UserCarryService;
        if ($carryService->doCancelAudit($id, $status, $adm_session['adm_name'], $adm_session['adm_id'])) {
            $this->success('审核成功');
        }
        else {
            $this->error('审核失败');
        }

    }

    public function editLimit() {
        $ret = array(
            'code' => 0,
            'msg' => '',
        );
        try {
            $adm_session = es_session::get(md5(conf("AUTH_KEY")));
            $permissions = explode(',', app_conf('WITHDRAW_LIMIT_APPLY'));
            if (!in_array($adm_session['adm_role_id'], $permissions)) {
                throw new \Exception('您无权发起调整限额。');
            }

            $id = isset($_POST['id']) ? intval($_POST['id']) : null;
            if (empty($id)) {
                throw new \Exception('用户ID不能为空！');
            }
            $type = isset($_POST['type']) ? intval($_POST['type']) : null;
            if (!in_array($type, array_keys(\core\service\UserCarryService::$withdrawLimitTypeCn))) {
                throw new \Exception('请选择申请类型！');
            }
            $amount = isset($_POST['limit_amount']) ? floatval($_POST['limit_amount']) : null;
            if (empty($amount)) {
                throw new \Exception('限制金额不能为空！');
            }
            $usercarryService = new \core\service\UserCarryService();
            if (false === $usercarryService->editLimit($id, $amount, $type)) {
                throw new \Exception('提交限制提现/投资申请处理失败！');
            }
        }
        catch (\Exception $e) {
            $ret['code'] = 1;
            $ret['msg'] = $e->getMessage();
        }
        echo json_encode($ret);
    }


    public function doAudit() {
        $status = isset($_GET['status']) ? intval($_GET['status']) : -1;
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if ($status  === -1 or $id === 0) {
            $this->error('无效的操作');
        }

        $adm_session = \es_session::get(md5(conf("AUTH_KEY")));
        $permissions = explode(',', app_conf('WITHDRAW_LIMIT_REVIEW'));
        if (!in_array($adm_session['adm_role_id'], $permissions)) {
            $this->error('您无权审核限制提现/投资。');
        }
        $carryService = new \core\service\UserCarryService;
        $limitRecord = $carryService->findLimitById($id);
        if ($carryService->doAudit($id, $status, $adm_session['adm_name'], $adm_session['adm_id'], $limitRecord['modify_amount'])) {
            $this->success('审核成功');
        }
        else {
            $this->error('审核失败');
        }
    }


    public function record()
    {
        $map = [];
        $map['is_whitelist'] = 0;
        $this->_record($map);
        $this->display();
    }

    public function record_supervision()
    {
        $map = [];
        $map['is_whitelist'] = 1;
        $map['status'] = ['gt', 3];
        $this->_record($map);
        $this->assign('isSupervision', 1);
        $this->display('record');
    }


    /**
     *限制提现/投资客户记录
     */
    public function _record($map)
    {
        $model = DI ("WithdrawLimitRecord");
        //被限制人id
        if(isset($_REQUEST['user_id']) && !empty($_REQUEST['user_id'])) {
            $map['user_id'] = array('eq', intval($_REQUEST['id']));
        }
        //被限制人姓名
        if(isset($_REQUEST['user_name']) && !empty($_REQUEST['user_name'])) {
            $map['user_name'] = array('eq', trim($_REQUEST['user_name']));
        }
        //还款状态
        if(isset($_REQUEST['status']) && !empty($_REQUEST['status'])) {
            $map['status'] = array('eq', intval($_REQUEST['status']));
        }
        //限制提现类型
        if(isset($_REQUEST['type']) && $_REQUEST['type'] !== '') {
            $map['type'] = array('eq', intval($_REQUEST['type']));
        }
        $this->_list($model, $map);
        $this->assign('accountMap', UserAccountEnum::$accountDesc);
        $this->assign('limit_types', \core\service\UserCarryService::$withdrawLimitTypeCn);//限制体现/投资类型map
        $this->assign('limit_status', \core\service\UserCarryService::$withdrawLimitStatusCn);//还款状态map
    }
}
