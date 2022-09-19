<?php
/**
 * [代理人模块]
 * @author <fanjingwen@ucfgroup.com>
 */

use core\dao\UserModel;
use core\service\ContractNewService;

class EntrustAction extends CommonAction
{
    /**
     * @author fanjingwen@ucfgroup.com #JIRA 3255
     * @func 借款人合同代理签署页面
     */
    public function contract_entrust()
    {
        // 搜索条件初始化
        $successTimeStart = 0;
        $successTimeEnd = 0;
        $dealID = 0;
        $bUserID = '0';

        // 用于翻页时保持搜索条件
        $pageUrl = "asHead=1";

        // 满标时间搜索
        if (!empty($_REQUEST['successTimeStart'])) {
            $pageUrl .= '&successTimeStart=' . $_REQUEST['successTimeStart'];
            $successTimeStart = to_timespan($_REQUEST['successTimeStart']);
        }

        if (!empty($_REQUEST['successTimeEnd'])) {
            $pageUrl .= '&successTimeEnd=' . $_REQUEST['successTimeEnd'];
            $successTimeEnd = to_timespan($_REQUEST['successTimeEnd']);
        }

        // 标的编号搜索
        if (!empty($_REQUEST['dealID'])) {
            $pageUrl .= '&dealID=' . $_REQUEST['dealID'];
            $dealID = intval($_REQUEST['dealID']);
        }

        // 借款人id搜索
        if (!empty($_REQUEST['bUserID'])) {
            $pageUrl .= '&bUserID=' . $searchUserID;
            $searchUserID = trim($_REQUEST['bUserID']);
            $bUserID = $searchUserID;
        }

        // 借款人姓名搜索
        if (!empty($_REQUEST['bRealName'])) {
            $pageUrl .= '&bRealName=' . $_REQUEST['bRealName'];
            $userIDArr = UserModel::instance()->getUserIdsByRealName($_REQUEST['bRealName']);
            $userIDsStr = implode(',', $userIDArr);
            // 如果姓名搜索没结果，或者与id联合搜索不匹配
            if (('' === $userIDsStr) || (!empty($_REQUEST['bUserID']) && ($bUserID !== $userIDsStr))) {
                $bUserID = strval(-1);
            } else {
                $bUserID = $userIDsStr;
            }
        }

        // 获取分页信息
        $nowPage = !empty($_REQUEST[C('VAR_PAGE')]) ? $_REQUEST[C('VAR_PAGE')] : 1;
        $rowOfPage = C('PAGE_LISTROWS');

        // 获取未签署的委托标的信息列表
        $contService = new ContractNewService();
        $listInfo = $contService->getEntrustDealInfoList($nowPage, $rowOfPage, $successTimeStart, $successTimeEnd, $dealID, $bUserID);

        // 关于分页
        $pageObj = new Page($listInfo['count']);
        // 分页跳转的时候保证查询条件
        $pageObj->parameter .= $pageUrl;

        $pageHtml = $pageObj->show();

        $this->assign("list", $listInfo['list']);
        $this->assign("page", $pageHtml);
        $this->assign("nowPage", $nowPage);

        $this->display();
    }

    /**
     * @author fanjingwen@ucfgroup.com #JIRA 3255
     * @func 代理借款人签署合同 ajax请求
     */
    public function signContractEntrust()
    {
        // 获取签署标识
        $signGroup = empty($_REQUEST['signGroup']) ? array() : $_REQUEST['signGroup'];
        $admSession = es_session::get(md5(conf("AUTH_KEY")));
        $admID = intval($admSession['adm_id']);

        $ret = true;
        $contService = new ContractNewService();
        foreach ($signGroup as $sign) {
            if (empty($sign['deal_id']) || empty($sign['user_id'])) {
                continue;
            }

            if (false == $contService->signAll($sign['deal_id'], 1, $sign['user_id'], $admID)) {
                $ret = false;
            }
        }

        // 返回消息结果
        if (true == $ret) {
            $status = 1;
            $info = "签署任务添加成功！";
        } else {
            $status = 0;
            $info = "NOTICE:签署任务添加异常！";
        }

        $retArr = array(
            'status' => $status,
            'info' => $info,
        );
        echo json_encode($retArr);
        return;
    }
}