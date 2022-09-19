<?php
/**
 * 多投标的相关
 * User: jinhaidong
 * Date: 2015/10/12 18:02
 */

use core\service\user\UserService;
use core\service\duotou\DtBidService;

class DtDealAction extends CommonAction {

    public function add() {
        $projectId = $_REQUEST['project_id'];

        $request = array("project_id"=>$projectId);

        $response = $this->callByObject(array(
                'service' => 'NCFGroup\Duotou\Services\Project',
                'method' => 'getProjectInfoById',
                'args' => $request,
        ));

        $this->assign('data',$response['data']);
        $this->assign('projectId',$projectId);
        $this->display('add');
    }

    public function edit() {
        $dealId = $_REQUEST['deal_id'];
        $request = array("deal_id"=>$dealId);

        $rpcResponse = $this->callByObject(array(
            'service' => 'NCFGroup\Duotou\Services\Deal',
            'method' => 'getDealById',
            'args' => $request,
        ));
        $this->assign('data',$rpcResponse['data']);
        $this->display('edit');
    }

    public function index() {
        $pageNum = intval($_REQUEST['p']);
        $pageNum = $pageNum > 0 ? $pageNum : 1;
        $pageSize = C('PAGE_LISTROWS');
        $id = trim($_REQUEST['id']);
        $name = trim($_REQUEST['name']);

        $request = array("pageNum"=>$pageNum,"pageSize"=>$pageSize,"id"=>$id,"name"=>$name);

        $response = $this->callByObject(array(
            'service' => 'NCFGroup\Duotou\Services\Deal',
            'method' => 'listDeal',
            'args' => $request,
        ));

        if(!$response) {
            $this->error("rpc请求失败");
        }
        if($response['errCode'] != 0) {
            //$this->assign("jumpUrl",u(MODULE_NAME."/index"));
            $this->error("Rpc错误 errCode:".$response['errCode'] . " errMsg:" .$response['errMsg']);
        }

        $p = new Page ($response['data']['totalNum'], $pageSize);
        $page = $p->show ();
        $this->assign ( "page", $page );
        $this->assign ( "nowPage", $p->nowPage );

        $this->assign("data",$response['data']['data']);
        $this->display ('index');
    }

    public function insert() {
        $request = $_POST;
        $response = $this->callByObject(array(
            'service' => 'NCFGroup\Duotou\Services\Deal',
            'method' => 'saveDeal',
            'args' => $request,
        ));
        if(!$response || $response['data'] == false) {
            $this->error("操作失败:". $response['errMsg']);
        }
        $this->success("操作成功");
    }

    /**
     * 显示投资认购界面
     */
    public function showInvest() {
        $id = intval(trim($_REQUEST['id']));

        $request = array("deal_id"=>$id);

        $response = $this->callByObject(array(
            'service' => 'NCFGroup\Duotou\Services\Deal',
            'method' => 'getDealInfoById',
            'args' => $request,
        ));

        $dealInfo = $response['data'];

        if(!$dealInfo) {
            $this->error("操作失败 errCode:".$dealInfo['errCode']." errMsg:".$dealInfo['errMsg']);
        }

        $canUseMoney = $dealInfo['moneyLimitDay'] - $dealInfo['hasLoanMoney'] ;

        $dtUser = UserService::getUserById(app_conf('AGENCY_ID_DT_PRINCIPAL'));

        $dtUsers = array(array('user_name' => $dtUser['user_name'],'id'=>$dtUser['id']));
        $this->assign("dtUsers",$dtUsers);
        $this->assign("canUseMoney",$canUseMoney);
        $this->display ();
    }

    /**
     * 获取用户余额
     */
    public function getUserMoney() {
        $userName = trim($_REQUEST['user_name']);
        $user_info = UserService::getUserByName($userName);
        if (empty($user_info['id'])){
            $this->ajaxReturn(0,'用户不存在',0);
        }
        $user_id = $user_info['id'];
        $money = $user_info['money'];

        if($user_id == 0 || $money == 0){
            $this->ajaxReturn(0,'提交的数据存在异常',0);
        }
        $this->ajaxReturn($money);
    }

    /**
     * 投资认购
     */
    public function invest() {
        $userName = trim($_REQUEST['user_name']);
        $user_info = UserService::getUserByName($userName);
        $userId = $user_info['id'];

        $user_dt_interest = UserService::getUserById(app_conf('AGENCY_ID_DT_INTEREST'));
        if($userId == $user_dt_interest['id']) {
            $this->error("智多新利息账户不允许投资！");
        }
        $dealId = intval(trim($_REQUEST['id']));
        $investMoney = floatval(trim($_REQUEST['investMoney']));

        if($userId == 0 || $investMoney <= 0){
            $this->error("提交的数据存在异常");
        }

        $dtbidService = new DtBidService();
        $res = $dtbidService->bidAdmin($userId, $dealId, $investMoney);

        if($res['errCode']){
            $this->error($res['errMsg'], "");
        } else {
            $this->success("操作成功！");
        }
    }
}
