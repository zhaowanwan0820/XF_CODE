<?php
/**
 * 多投项目相关
 * User: jinhaidong
 * Date: 2015/10/9 18:02
 */


use core\service\user\UserService;
use libs\utils\Logger;
use core\dao\UserModel;
use core\service\duotou\DtBidService;
use core\service\duotou\DtEntranceService;
use core\enum\duotou\ProjectEnum;
use core\service\duotou\duotouService;
use core\service\contract\CategoryService;
use core\dao\deal\DealModel;

class DtProjectAction extends DtCommonAction
{
    public function index()
    {
        $pageNum = intval($_REQUEST['p']);
        $pageNum = $pageNum > 0 ? $pageNum : 1;
        $pageSize = C('PAGE_LISTROWS');


        $id = trim($_REQUEST['id']);
        $name = trim($_REQUEST['name']);

        $request = array("pageNum"=>$pageNum,"pageSize"=>$pageSize,"id"=>$id,"name"=>$name);
        $response = $this->callByObject(array(
            'service' => 'NCFGroup\Duotou\Services\Project',
            'method' => 'listProject',
            'args' => $request));
    
        if (!$response) {
            $this->error("rpc请求失败");
        }
        if ($response['errCode'] != 0) {
            $this->error("Rpc错误 errCode:".$response['errCode'] . " errMsg:" .$response['errMsg']);
        }

        $p = new Page($response['data']['totalNum'], $pageSize);
        $page = $p->show();
        $this->assign("page", $page);
        $this->assign("nowPage", $p->nowPage);

        $this->assign("data", $response['data']['data']);
        $template = $this->is_cn ? 'index_cn' : 'index';
        $this->display($template);
    }

    public function add()
    {
        //担保机构
        $deal_agency = M("DealAgency")->where('is_effect = 1 and type=1')->order('sort DESC')->findAll();
        $this->assign("deal_agency", $deal_agency);

        //咨询机构
        $deal_advisory = M("DealAgency")->where('is_effect = 1 and type=2')->order('sort DESC')->findAll();
        $this->assign("deal_advisory", $deal_advisory);

        //管理机构
        $deal_manage = M("DealAgency")->where('is_effect = 1 and type=5')->order('sort DESC')->findAll();
        $this->assign("deal_manage", $deal_manage);
        $contractResponse = CategoryService::getCategorys(0,1);
        $this->assign('contractCategory', $contractResponse);
        $template = $this->is_cn ? 'add_cn' : 'add';
        $this->display($template);
    }

    public function edit()
    {
        $projectId = $_REQUEST['project_id'];
        $request = array("project_id"=>$projectId);

        //担保机构
        $deal_agency = M("DealAgency")->where('is_effect = 1 and type=1')->order('sort DESC')->findAll();
        $this->assign("deal_agency", $deal_agency);

        //咨询机构
        $deal_advisory = M("DealAgency")->where('is_effect = 1 and type=2')->order('sort DESC')->findAll();
        $this->assign("deal_advisory", $deal_advisory);

        //管理机构
        $deal_manage = M("DealAgency")->where('is_effect = 1 and type=5')->order('sort DESC')->findAll();
        $this->assign("deal_manage", $deal_manage);

        $response = $this->callByObject(array(
            'service' => 'NCFGroup\Duotou\Services\Project',
            'method' => 'getProjectInfoById',
            'args' => $request,
        ));

        $contractResponse = CategoryService::getCategorys(0,1);
        $this->assign('contractCategory', $contractResponse);
        $this->assign('data', $response['data']);
        $tempalte = $this->is_cn ? 'edit_cn':'edit';
        $this->display($tempalte);
    }

    public function showChangeLog()
    {
        $data = CategoryService::getCategoryRecordsByDealId(intval($_REQUEST['id']),1,intval($_REQUEST['p']));
        if ($data) {
            $this->assign('page', $_REQUEST['p']);
            $this->assign('totalPage', $data['totalPage']);
            $this->assign('totalNum', $data['totalNum']);
            $this->assign('list', $data['data']);
            $this->display();
        } else {
            $this->error("RPC response is null");
        }
    }

    public function delete()
    {
        $projectIds = explode(",", $_REQUEST['id']);
        $return  =  array(
            "status" => 0,
            "msg" => "",
        );
        if (empty($projectIds)) {
            return ajax_return($return);
        }

        $request = array("project_ids"=>$projectIds);
        $response = $this->callByObject(array(
            'service' => 'NCFGroup\Duotou\Services\Project',
            'method' => 'delProject',
            'args' => $request,
        ));
        if (!$response['data']) {
            $return['status'] = 1;
            $return['msg'] = $response['errMsg'];
        }
        return ajax_return($return);
    }

    /**
     * 增加项目
     */
    public function insert()
    {
        $expiryInterest = trim($_POST['expiry_interest']);
        $expiryInterestDays = explode(',', $expiryInterest);
        $validInterestDays = ProjectEnum::$interestDay;

        foreach ($expiryInterestDays as $day) {
            if (!in_array($day, $validInterestDays)) {
                $this->error("结息日不正确，请修正后重试！");
            }
        }
        $_POST['expiry_interest'] = $expiryInterest;

        $request = $_POST;
        $response = $this->callByObject(array(
            'service' => 'NCFGroup\Duotou\Services\Project',
            'method' => 'saveProject',
            'args' => $request,
        ));

        if (!$response || $response['data'] == false) {
            $this->error("操作失败".$response['errMsg']);
        }
        $this->success("操作成功");
    }

    /**
     * 检查项目名称是否存在
     * @return bool|void
     */
    public function getCntByName()
    {
        $name = $_REQUEST ['name'];
        $request = array('name'=>$name);
        $response = $this->callByObject(array(
            'service' => 'NCFGroup\Duotou\Services\Project',
            'method' => 'checkProjectExists',
            'args' => $request,
        ));
        $retrun = $response['data'] ? 1 : 0;
        return ajax_return($retrun);
    }

    /**
     * 项目资产-该项目下所有标对应的p2p标的信息
     */
    public function assetRelate()
    {
        $projectId = $_REQUEST['project_id'];
        if (!$projectId) {
            $this->error("缺少参数project_id");
        }

        $pageNum = intval($_REQUEST['p']);
        $pageNum = $pageNum > 0 ? $pageNum : 1;
        $pageSize = C('PAGE_LISTROWS');

        $request = array(
            "project_id"=>$projectId,
            "pageNum"=>$pageNum,
            "pageSize"=>$pageSize,
        );

        $response = $this->callByObject(array(
            'service' => 'NCFGroup\Duotou\Services\DealMapping',
            'method' => 'getP2pDealIdsByProjectId',
            'args' => $request
        ));

        if ($response['errCode'] != 0) {
            $this->error("rpc调用失败");
        }


        $dealIds = $response['data']['data'];
        $p = new Page($response['data']['totalNum'], $pageSize);
        $page = $p->show();
        $this->assign("page", $page);
        $this->assign("nowPage", $p->nowPage);

        $data = array();
        if (!empty($dealIds)) {
            // 获取标信息
            $data = DealModel::instance()->getDealsInfoByIds($dealIds);
            foreach ($data as &$value) {
                $user = UserService::getUserById($value['user_id'],'user_name,real_name,mobile');
                $value['user_name'] = $user['user_name'];
                $value['real_name'] = $user['real_name'];
                $value['mobile'] = $user['mobile'];
            }
        }
        $this->assign("data", $data);
        $template = $this->is_cn ? 'assetRelate_cn' : 'assetRelate';
        $this->display($template);
    }

    /**
     * 项目收支详情
     */
    public function assetDetail()
    {
        $projectId = $_REQUEST['project_id'];
        if (!$projectId) {
            $this->error("缺少参数project_id");
        }

        $request = array("projectId"=>$projectId);
        $response = $this->callByObject(array(
            'service' => 'NCFGroup\Duotou\Services\Project',
            'method' => 'getProjectAsset',
            'args' => $request
        ));

        if (!$response) {
            $this->error("rpc请求失败");
        }
        if ($response['errCode'] != 0) {
            $this->error("Rpc错误 errCode:".$response['errCode'] . " errMsg:" .$response['errMsg']);
        }
        $this->assign("data", $response['data']);
        $template = $this->is_cn ? 'assetDetail_cn' : 'assetDetail';
        $this->display($template);
    }

    /**
     * 显示投资认购界面
     */
    public function showInvest()
    {
        $id = intval(trim($_REQUEST['id']));

        $request = array("project_id"=>$id);

        $response = $this->callByObject(array(
            'service' => 'NCFGroup\Duotou\Services\Project',
            'method' => 'getProjectInfoById',
            'args' => $request,
        ));

        $projectInfo = $response['data'];

        if (!$projectInfo) {
            $this->error("操作失败 errCode:".$projectInfo['errCode']." errMsg:".$projectInfo['errMsg']);
        }

        $canUseMoney = $projectInfo['moneyLimitDay'] - $projectInfo['hasLoanMoney'] ;

        $dtUser = UserModel::instance()->find(app_conf('AGENCY_ID_DT_PRINCIPAL'))->getRow();

        $dtUsers = array(array('user_name' => $dtUser['user_name'],'id'=>$dtUser['id']));
        $this->assign("dtUsers", $dtUsers);
        $this->assign("canUseMoney", $canUseMoney);
        $this->display();
    }

    /**
     * 获取用户余额
     */
    public function getUserMoney()
    {
        $userName = trim($_REQUEST['user_name']);
        $user_info = UserModel::instance()->getUserinfoByUsername($userName);
        if (empty($user_info['id'])) {
            $this->ajaxReturn(0, '用户不存在', 0);
        }
        $user_id = $user_info['id'];
        $money = $user_info['money'];

        if ($user_id == 0 || $money == 0) {
            $this->ajaxReturn(0, '提交的数据存在异常', 0);
        }
        $this->ajaxReturn($money);
    }

    /**
     * 投资认购
     */
    public function invest()
    {
        $userName = trim($_REQUEST['user_name']);
        $user_info = UserModel::instance()->getUserinfoByUsername($userName);
        $userId = $user_info['id'];

        $user_dt_interest = UserModel::instance()->find(app_conf('AGENCY_ID_DT_INTEREST'));
        if ($userId == $user_dt_interest['id']) {
            $this->error("智多新利息账户不允许投资！");
        }
        $dealId = intval(trim($_REQUEST['id']));
        $investMoney = floatval(trim($_REQUEST['investMoney']));

        if ($userId == 0 || $investMoney <= 0) {
            $this->error("提交的数据存在异常");
        }

        $dtbidService = new DtBidService();
        $res = $dtbidService->bidAdmin($userId, $dealId, $investMoney);

        if ($res['errCode']) {
            $this->error($res['errMsg'], "");
        } else {
            $this->success("操作成功！");
        }
    }

    /**
     * 智多新入口列表
     */
    public function entrance()
    {

        //定义条件
        if (!empty($_REQUEST['name'])) {
            $map['name'] = array('like', '%' . addslashes($_REQUEST['name']) . '%');
        }

        $start = $end = 0;
        if (!empty($_REQUEST['time_start'])) {
            $start = to_timespan($_REQUEST['time_start']);
            $map['create_time'] = array('egt', $start);
        }

        if (!empty($_REQUEST['time_end'])) {
            $end = to_timespan($_REQUEST['time_end']);
            $map['create_time'] = array('between', sprintf('%s,%s', $start, $end));
        }

        $min_invest = $_REQUEST['min_invest'];
        if (is_numeric($min_invest) && $min_invest > 0) {
            $map['min_invest_money'] = array('eq', $min_invest);
        }

        $new_user_min_invest = $_REQUEST['new_user_min_invest'];
        if (is_numeric($new_user_min_invest) && $new_user_min_invest > 0) {
            $map['new_user_min_invest_money'] = array('eq', $new_user_min_invest);
        }

        $lock_day = intval($_REQUEST['lock_day']);
        if (is_numeric($lock_day) && $lock_day > 0) {
            $map['lock_day'] = array('eq', $lock_day);
        }

        $status = intval($_REQUEST['status']);
        if (is_numeric($status) && $status > 0) {
            $map['status'] = array('eq', $status);
        }

        //取列表数据
        $DuotouEntrance = M("DuotouEntrance");
        $this->_list($DuotouEntrance, $map);
        $template = $this->is_cn ? 'entrance_cn' : 'entrance';
        $this->display($template);
    }

    /**
     * 新建智多新入口
     */
    public function addEntrance()
    {
        $request = array();
        
        $response = $this->callByObject(array(
            'service' => 'NCFGroup\Duotou\Services\Project',
            'method' => 'getProjectEffect',
            'args' => $request,
        ));

        if (!$response) {
            Logger::error(implode(" | ", array(__CLASS__,__FUNCTION__,"fail duotou rpc 调用失败")));
            $this->error("调用智多新服务失败");
        }

        $this->assign('rateYear', $response['data']['rateYear']);
        $this->assign('minLoanMoney', $response['data']['singleMinLoanMoney']);
        $tempalte = $this->is_cn ? 'addEntrance_cn' : 'addEntrance';
        $this->display($tempalte);
    }

    /**
     * 修改智多新入口
     */
    public function editEntrance()
    {
        $id = $_REQUEST['id'];

        $dtEntranceService = new DtEntranceService();
        $entrance = $dtEntranceService->getEntranceInfo($id);
        
        $response = $this->callByObject(array('\NCFGroup\Duotou\Services\Project', "getProjectEffect", array()));
        if (!$response) {
            Logger::error(implode(" | ", array(__CLASS__,__FUNCTION__,"fail duotou rpc 调用失败")));
            $this->error("调用智多新服务失败");
        }
        $this->assign('rateYear', $response['data']['rateYear']);
        $this->assign('minLoanMoney', $response['data']['singleMinLoanMoney']);
        $this->assign('entrance', $entrance);
        $template = $this->is_cn ? 'editEntrance_cn' : 'editEntrance';
        $this->display($template);
    }

    public function saveEntrance()
    {
        B('FilterString');

        $id = $_POST['id'];
        $data = M("DuotouEntrance")->create();

        $data['name'] = $_POST['name'];
        $data['min_invest_money'] = $_POST['min_invest'];
        $data['new_user_min_invest_money'] = $_POST['new_user_min_invest'];
        $data['lock_day'] = $_POST['lock_day'];
        $data['min_rate'] = $_POST['min_rate'];
        $data['max_rate'] = $_POST['max_rate'];
        $data['status'] = $_POST['status'];
        $data['site_ids_type'] = $_POST['site_id_type'];
        $data['site_ids'] = $_POST['site_id'];
        $data['create_time'] = $data['update_time'] = get_gmtime();

        if (!empty($id)) {
            $rs = M("DuotouEntrance")->where('id='.$id)->save($data);
            if (false !== $rs) {
                $this->success("操作成功", 0, u("DtProject/entrance"));
            } else {
                $this->error("操作失败");
            }
        }
        $lastId = M("DuotouEntrance")->add($data);
        if (false !== $lastId) {
            $this->success(L("INSERT_SUCCESS"), 0, u("DtProject/entrance"));
        } else {
            $this->error("操作失败");
        }
    }
}
