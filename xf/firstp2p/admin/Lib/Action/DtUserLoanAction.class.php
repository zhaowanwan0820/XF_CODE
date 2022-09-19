<?php
/**
 * 用户投资记录
 * User: jinhaidong
 * Date: 2015/10/15 14:02
 */

use libs\utils\Logger;
use NCFGroup\Protos\Duotou\RequestCommon;
use NCFGroup\Protos\Duotou\Enum\DealLoanEnum;
use core\service\DealService;
use core\service\DealProjectService;
use core\service\UserService;

class DtUserLoanAction extends CommonAction {

    public function index() {
        $pageNum = intval($_REQUEST['p']);
        $pageNum = $pageNum > 0 ? $pageNum : 1;
        $pageSize = C('PAGE_LISTROWS');

        $request = new RequestCommon();
        $request->setVars(array(
            "userId" => $_REQUEST['user_id'],
            "pageNum"=>$pageNum,
            "pageSize"=>$pageSize));

        $response = $this->getRpc('duotouRpc')->callByObject(array(
            'service' => 'NCFGroup\Duotou\Services\DealLoan',
            'method' => 'getDealLoans',
            'args' => $request,
        ));

        if(!$response) {
            $this->error("rpc请求失败");
        }
        if($response['errCode'] != 0) {
            $this->error("Rpc错误 errCode:".$response['errCode'] . " errMsg:" .$response['errMsg']);
        }

        $p = new Page ($response['data']['totalNum'], $pageSize);
        $page = $p->show ();
        $this->assign ( "page", $page );
        $this->assign ( "nowPage", $p->nowPage );

        $this->assign("data",$response['data']['data']);
        $this->display ('index');
    }

    /**
     * 用户资金记录详情
     *
     */
    public function userLoanDetail() {
        $pageNum = intval($_REQUEST['p']);

        $userId = intval($_REQUEST['user_id']);
        $dealId = intval($_REQUEST['dealId']);

        $pageNum = $pageNum > 0 ? $pageNum : 1;
        $pageSize = C('PAGE_LISTROWS');

        $request = new RequestCommon();
        $request->setVars(array(
            "loanId" => $_REQUEST['loanId'],
            "pageNum"=>$pageNum,
            "pageSize"=>$pageSize));

        $response = $this->getRpc('duotouRpc')->callByObject(array(
            'service' => 'NCFGroup\Duotou\Services\LoanMapping',
            'method' => 'getLoanP2pInfos',
            'args' => $request,
        ));


        $datas = $response['data']['data'];

        $dealService = new DealService();
        $dealProjectService = new DealProjectService();
        foreach ($datas as & $data) {
            $dealInfo = $dealService->getDeal($data['p2pDealId']);
            $data['repayTime'] = '';
            $data['borrowUser'] = '';
            $data['repayStartTime'] = '';

            if($dealInfo) {
                $data['repayStartTime'] = $dealInfo['repay_start_time'] + 28800;
                if ($dealInfo['loantype'] == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_DAY']) {
                    $data['repayTime'] =$dealInfo['repay_time'].'日';
                } else {
                    $data['repayTime'] =$dealInfo['repay_time'].'个月';
                }
                $projectInfo = $dealProjectService->getProInfo($dealInfo['project_id'], $dealInfo['id']);
                if(!empty($projectInfo)) {
                    $userInfo = (new UserService())->getUserArray($projectInfo['user_id'],'real_name');
                    if(!empty($userInfo)) {
                        $data['borrowUser'] = $userInfo['real_name'];
                    }
                }
            }
            $data['totalMoney'] = $data['totalMoney'] / 100; //将分格式化为元
        }

        if(!$response) {
            $this->error("rpc请求失败");
        }
        if($response['errCode'] != 0) {
            $this->error("Rpc错误 errCode:".$response['errCode'] . " errMsg:" .$response['errMsg']);
        }

        $p = new Page ($response['data']['totalNum'], $pageSize);
        $page = $p->show ();
        $this->assign ( "page", $page );
        $this->assign ( "nowPage", $p->nowPage );
        $this->assign ( "dealId", $dealId );

        $this->assign("data",$datas);
        $template = $this->is_cn ? 'userLoanDetail_cn' : 'userLoanDetail';
        $this->display ($template);
    }
}
