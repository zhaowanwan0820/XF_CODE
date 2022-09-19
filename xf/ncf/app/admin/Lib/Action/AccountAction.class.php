<?php

use core\service\account\AccountService;
use core\service\account\AccountAuthService;
use core\service\supervision\SupervisionAccountService;
use core\enum\UserAccountEnum;
use core\enum\AccountAuthEnum;

class AccountAction extends CommonAction
{

    /**
     * 更换账户类型
     */
    public function changeAccountType() {
        if (isset($_SERVER['REQUEST_METHOD']) && strtolower($_SERVER['REQUEST_METHOD']) === 'post') {

            if (!isset($_REQUEST['account_type']) || intval($_REQUEST['account_type']) == -1) {
                $this->error('用户类型不正确');
            }
            $accountType = intval($_REQUEST['account_type']);
            if (!isset($_REQUEST['accountIds']) || empty($_REQUEST['accountIds'])) {
                $this->error('待刷新用户列表不能为空');
            }
            $grantTypeList = !empty($_REQUEST['grant']) ? $_REQUEST['grant'] : []; //授权
            $logError = [];
            $accountService = new SupervisionAccountService();
            // 企业用户列表里面的企业用户
            $accountIds = explode("\n", $_REQUEST['accountIds']);
            foreach ($accountIds as $accountId) {
                $accountId = trim($accountId);
                if (empty($accountId)) {
                    continue;
                }
                $purposeInfo = AccountService::getUserPurposeInfo($accountType);
                if (empty($purposeInfo) || empty($purposeInfo['supervisionBizType'])) {
                    $logError[] = $accountId.'不支持的账户类型';
                    continue;
                }
                $grantList = AccountAuthService::convertToGrant($grantTypeList);
                $response = $accountService->updateAccountType($accountId, $accountType, $purposeInfo['supervisionBizType'], $grantList);
                if ($response !== true) {
                    $logError[] = $accountId.$response;
                    continue;
                }
            }

            $mesasge = '操作成功';
            if (!empty($logError)) {
                $message = implode("<br/>", $logError);
                return $this->error($message);
            }
            return $this->success($message, 0, "?m=Account&a=changeAccountType", null , 10);

        }
        $this->assign('grantList', AccountAuthEnum::$grantTypeName);
        $this->assign('types', UserAccountEnum::$accountDesc[UserAccountEnum::PLATFORM_SUPERVISION]);
        $this->display();
    }
}
