<?php
/**
 * 多投宝投资记录
 * User: jinhaidong
 * Date: 2015/10/13 12:02
 */

use core\service\user\UserService;
use libs\utils\Logger;
use core\service\duotou\DtEntranceService;

class DtDealLoanAction extends DtCommonAction
{

    /**
     * 投资人列表
     */
    public function index()
    {
        $pageNum = intval($_REQUEST['p']);
        $pageNum = $pageNum > 0 ? $pageNum : 1;
        $pageSize =  C('PAGE_LISTROWS');
        $projectId = intval($_REQUEST['project_id']);
        $status = intval($_REQUEST['status']);
        $userId = intval($_REQUEST['userId']);
        $deal_load_date_begin = trim($_REQUEST['deal_load_date_begin']);
        $deal_load_date_end = trim($_REQUEST['deal_load_date_end']);
        //$redem_apply_date_begin = trim($_REQUEST['redem_apply_date_begin']);
        //$redem_apply_date_end = trim($_REQUEST['redem_apply_date_end']);
        //$redem_finish_date_begin = trim($_REQUEST['redem_finish_date_begin']);
        //$redem_finish_date_end = trim($_REQUEST['redem_finish_date_end']);
        $lock_period = intval($_REQUEST['lock_period']);
        if (empty($projectId)) {
            $this->error("参数错误");
        }

        $request = array(
            "pageNum"=>$pageNum,
            "pageSize"=>$pageSize,
            "projectId"=>$projectId,
            "status"=>$status,
            "userId"=>$userId,
            'deal_load_date_begin' => strtotime($deal_load_date_begin),
            'deal_load_date_end' => strtotime($deal_load_date_end),
           // "redem_apply_date_begin"=>strtotime($redem_apply_date_begin),
           // "redem_apply_date_end"=>strtotime($redem_apply_date_end),
           // "redem_finish_date_begin"=>strtotime($redem_finish_date_begin),
           // "redem_finish_date_end"=>strtotime($redem_finish_date_end),
            'lock_period' => $lock_period,
        );
        $response = $this->callByObject(array(
            'service' => 'NCFGroup\Duotou\Services\DealLoan',
            'method' => 'getUserLoansByDeal',
            'args' => $request,
        ));
        if (!$response) {
            $this->error("rpc请求失败");
        }
        if ($response['errCode'] != 0) {
            $this->error("Rpc错误 errCode:".$response['errCode'] . " errMsg:" .$response['errMsg']);
        }
        $datas = $response['data']['data'];

        $userIds = array();
        foreach ($datas as $data) {
            $userIds[] = $data['userId'];
        }

        $userNames = UserService::getUserRealName($userIds);
        foreach ($datas as & $data) {
            $data['realName'] = '';
            if($data['userId']) {
                $data['realName'] = count($userIds) == 1 ? $userNames : $userNames[$data['userId']];
            }
            $data['createTime'] = date('Y-m-d H:i:s', $data['createTime']);
            $data['redemCreateTime'] = $data['redemCreateTime'] ? date('Y-m-d H:i:s', $data['redemCreateTime']) : '-';
            $data['redemFinishTime'] = $data['redemFinishTime'] ? date('Y-m-d H:i:s', $data['redemFinishTime']) : '-';
        }

        $p = new Page($response['data']['totalNum'], $pageSize);
        $page = $p->show();
        $this->assign("page", $page);
        $this->assign("nowPage", $p->nowPage);
        $this->assign("projectId", $projectId);
        $this->assign("data", $datas);
        $this->assign('lockPeriodList', DtEntranceService::getLockDayList());
        $template = $this->is_cn ? 'index_cn' : 'index';
        $this->display($template);
    }

    /**
     * 用户投资记录
     */
    public function userLoan()
    {
    }
}
