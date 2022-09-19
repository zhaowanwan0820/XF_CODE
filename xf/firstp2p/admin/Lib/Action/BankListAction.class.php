<?php
use NCFGroup\Task\Services\TaskService AS GTaskService;
use libs\utils\Logger;
use core\dao\EnterpriseModel;
use core\dao\UserBankcardModel;
use core\dao\BanklistModel;
use core\event\UserUpdateBankInfoEvent;
use core\service\BanklistService;

class BankListAction extends CommonAction
{

    /**
     * 列表
     */
    public function index()
    {
        $condition = array();

        $name = isset($_REQUEST['name']) ? addslashes(trim($_REQUEST['name'])) : '';
        if ($name !== '') {
            $condition['name'] = array('LIKE', "%{$name}%");
        }

        $branch = isset($_REQUEST['branch']) ? addslashes(trim($_REQUEST['branch'])) : '';
        if ($branch !== '') {
            $condition['branch'] = array('LIKE', "%{$branch}%");
        }

        $bank_id = isset($_REQUEST['bank_id']) ? addslashes(trim($_REQUEST['bank_id'])) : '';
        if ($bank_id !== '') {
            $condition['bank_id'] = $bank_id;
        }

        $oper_name = isset($_REQUEST['oper_name']) ? addslashes(trim($_REQUEST['oper_name'])) : '';
        if ($oper_name !== '') {
            $oper_id = M("Admin")->where("adm_name='{$oper_name}'")->getField("id");
            if ($oper_id > 0) {
                $condition['oper_id'] = $oper_id;
            }
        }

        $this->_list(D('banklist'), $condition);

        $this->display();
    }

    /**
     * 添加
     */
    public function add()
    {
        $this->display();
    }

    /**
     * 修改
     */
    public function edit()
    {
        $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        if ($id === 0) {
            $this->error('参数错误');
        }

        $result = M('banklist')->where("id='{$id}'")->find();

        // 获取银行ID
        $bankAid = 0;
        if (!empty($result['branch'])) {
            $bankModel = new \core\dao\BankModel();
            $bankInfo = $bankModel->getBankByName($result['branch']);
            $bankAid = !empty($bankInfo['id']) ? (int)$bankInfo['id'] : 0;
        }
        $this->assign('data', $result);
        $this->assign('bankAid', $bankAid);
        $this->display();
    }

    /**
     * 保存
     */
    public function insert()
    {
        $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        $branch = trim($_REQUEST['branch']);
        $name = trim($_REQUEST['name']);
        $bank_id = trim($_REQUEST['bank_id']);
        $province = trim($_REQUEST['province']);
        $city = trim($_REQUEST['city']);

        //联行号增加唯一性校验
        $bankInfo = BanklistModel::instance()->getBankInfoByBankId($bank_id);
        if (!empty($bankInfo)) {
            $this->error('此联行号已存在，请核对后重新填写');
        }

        $data = array(
            'branch' => $branch,
            'name' => $name,
            'bank_id' => $bank_id,
            'province' => $province,
            'city' => $city,
        );
        $adm_session = es_session::get(md5(conf("AUTH_KEY")));
        $data['oper_id'] = intval($adm_session['adm_id']);
        $data['oper_time'] = time();

        $GLOBALS['db']->autoExecute('firstp2p_banklist', $data, 'INSERT');

        \libs\utils\Logger::info('BankListEdit. data:'.json_encode($data));

        $affrows = $GLOBALS['db']->affected_rows();

        if ($affrows == 1)
        {
            $this->success('操作成功', 0, '?m=BankList');
        }
        else
        {
            $this->error('操作失败');
        }
    }

    /**
     * 保存
     */
    public function save()
    {
        $id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
        $bankId = (int)$_REQUEST['bank_aid']; // 银行编号
        $branchNo = trim($_REQUEST['bank_id']);
        $branchName = trim($_REQUEST['name']);
        $province = trim($_REQUEST['province']);
        $city = trim($_REQUEST['city']);

        if ((int)$bankId <= 0 || empty($branchNo) || empty($branchName)) {
            Logger::info(implode('|', array(__CLASS__, __FUNCTION__, sprintf('通过银行名称没有查到银行编号, bankId:%d, newBranchNo:%s, newBranchZone:%s', $bankId, $branchNo, $branchName))));
            $this->error('操作失败（银行名称不存在或参数不能为空）');
        }

        // 更新银行支行数据
        $requestData = array(
            'id' => $id,
            'name' => $branchName,
            'province' => $province,
            'city' => $city,
        );
        $bankListService = new BanklistService();
        $ret = $bankListService->saveBankListById($requestData);
        if (false === $ret) {
            $this->error('操作失败请重试');
        }

        // 新增支行信息时，不需要同步三端系统
        if ($id === 0) {
            Logger::info(implode('|', array(__CLASS__, __FUNCTION__, '企业用户列表为空，无需更新, bankId:' . $bankId)));
            $this->success('操作成功', 0, '?m=BankList');
        }

        // 获取所有的企业用户
        $enterpriseModel = new EnterpriseModel();
        $userIdList = $enterpriseModel->getAllEnterpriseUidList('user_id,company_name');
        if (empty($userIdList)) {
            Logger::info(implode('|', array(__CLASS__, __FUNCTION__, '企业用户列表为空，无需更新, bankId:' . $bankId)));
            $this->success('操作成功', 0, '?m=BankList');
        }

        // 整理符合条件的企业用户
        $userIds = [];
        foreach ($userIdList as $userItem) {
            if (empty($userItem['user_id']) || empty($userItem['company_name'])) {
                continue;
            }
            $userIds[] = (int)$userItem['user_id'];
        }

        // 根据银行ID，获取绑卡的用户列表
        $userBankList = UserBankcardModel::instance()->getBankCardListByBankUserId($bankId, $userIds);
        if (empty($userBankList)) {
            Logger::info(implode('|', array(__CLASS__, __FUNCTION__, sprintf('没有绑定该银行的企业用户，无需更新, bankId:%d, newBranchNo:%s, newBranchZone:%s', $bankId, $branchNo, $branchName))));
            $this->success('操作成功', 0, '?m=BankList');
        }

        $updateRet = false;
        foreach ($userBankList as $userBankItem) {
            if (empty($userBankItem['branch_no']) || $userBankItem['branch_no'] !== $branchNo) {
                Logger::info(implode('|', array(__CLASS__, __FUNCTION__, sprintf('该企业用户绑定的不是该联行号码, bankId:%d, newBranchNo:%s, newBranchZone:%s, oldBranchNo:%s', $bankId, $branchNo, $branchName, $userBankItem['branch_no']))));
                continue;
            }
            $updateRet = true;
            Logger::info(implode('|', array(__CLASS__, __FUNCTION__, sprintf('该企业用户的绑卡数据符合更新要求, bankId:%d, newBranchNo:%s, newBranchZone:%s, oldBranchNo:%s', $bankId, $branchNo, $branchName, $userBankItem['branch_no']))));
            $obj = new GTaskService();
            $event = new UserUpdateBankInfoEvent($id, $branchNo, $branchName, $userBankItem);
            $obj->doBackground($event, 1);
        }

        // 没有绑定该联行号码的企业用户绑卡记录
        if (false === $updateRet) {
            $this->success('操作成功', 0, '?m=BankList');
        }
        $this->success('操作成功（数据已经提交异步更新，请耐心等待）', 0, '?m=BankList');
    }

    /**
     * 查询联行号
     */
    public function qIssue()
    {
        $result = array('code' => 0, 'issue' => '');
        do {
            $bankIssueName = trim($_GET['bankIssueName']);
            if (empty($bankIssueName))
            {
                $result['code'] = -1;
                break;
            }
            $bankIssueName = addslashes($bankIssueName);
            $data = \libs\db\Db::getInstance('firstp2p', 'slave')->getOne("SELECT bank_id FROM firstp2p_banklist WHERE name = '{$bankIssueName}'");
            if ($data)
            {
                $result['issue'] = $data;
            }
            else
            {
                $result['code'] = -1;
            }
        } while(false);

        echo json_encode($result);
    }
}
