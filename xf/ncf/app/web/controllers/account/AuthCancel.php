<?php
/**
 * 存管系统-取消授权
 * 
 */

namespace web\controllers\account;

use web\controllers\BaseAction;
use libs\common\WXException;
use libs\utils\Logger;
use core\enum\AccountAuthEnum;
use core\enum\SupervisionEnum;
use core\service\account\AccountService;
use core\service\supervision\SupervisionAccountService;
use core\service\reserve\UserReservationService;
use core\service\creditloan\CreditLoanService;

class AuthCancel extends BaseAction {
    /**
     * 是否页面
     * @var boolean
     */
    const IS_H5 = false;

    /**
     * 授权配置
     * @var array
     */
    private static $grantConfig = [
        'quickbid' => 1,
        'yxt' => 1,
    ];

    public function init() {}

    public function invoke() {
        try{
            if (!$this->ajax_checklogin()) {
                throw new WXException('ERR_USER_NOLOGIN');
            }
            // 取消授权的类型(快捷投资:quickbid银信通:yxt)
            $grant = !empty($_POST['grant']) ? addslashes($_POST['grant']) : '';
            if (empty($grant) || !isset(self::$grantConfig[$grant])) {
                throw new WXException('ERR_PARAM_LOSE');
            }
            // 用户UID
            $userId = intval($GLOBALS['user_info']['id']);
            // 获取用户账户ID
            $accountId = AccountService::getUserAccountId($userId, $GLOBALS['user_info']['user_purpose']);
            // 查询用户是否开启存管系统帐号
            $supervisionAccountObj = new SupervisionAccountService();
            $isSupervisionData = $supervisionAccountObj->isSupervision($accountId);
            $isOpenAccount = isset($isSupervisionData['isSvUser']) ? (int)$isSupervisionData['isSvUser'] : 0;
            if (empty($isOpenAccount)) {
                throw new WXException('ERR_NOT_OPEN_ACCOUNT');
            }
            if ($grant === 'quickbid') {
                // 随心约-用户是否有未结束的预约记录
                $userReservationObj = new UserReservationService();
                $userValidReservelist = $userReservationObj->getUserValidReserveList($accountId);
                if (!empty($userValidReservelist['userReserveList'])) {
                    throw new WXException('ERR_RESERVE_NOCANCEL');
                }
                // 随心约-授权组
                $grantList = [AccountAuthEnum::GRANT_INVEST, AccountAuthEnum::GRANT_WITHDRAW_TO_SUPER];
            }else if ($grant === 'yxt') {
                // 银信通-用户是否有未还款的记录
                $creditLoanObj = new CreditLoanService();
                $userCreditCnt = $creditLoanObj->getNotFinishCreditCount($userId);
                if ((int)$userCreditCnt > 0) {
                    throw new WXException('ERR_YXT_NOCANCEL');
                }
                // 银信通-授权组
                $grantList = [AccountAuthEnum::GRANT_WITHDRAW_TO_YXT];
            }
            // 取消授权
            $cancelRet = $supervisionAccountObj->memberAuthorizationCancel($accountId, $grantList);
            if (empty($cancelRet) || $cancelRet['status'] != SupervisionEnum::RESPONSE_SUCCESS) {
                throw new WXException('ERR_AUTHORIZATION_CANCEL');
            }
            $this->json_data = ['status'=>1];
            Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, APP, 'SUPERVISION', sprintf('取消授权成功,userId:%d,grant:%s', $userId, $grant))));
            return true;
        }catch(\Exception $e) {
            Logger::error(implode(' | ', array(__CLASS__, __FUNCTION__, APP, 'SUPERVISION', sprintf('取消授权失败,userId:%d,grant:%s,ExceptionCode:%s,ExceptionMsg:%s', $userId, $grant, $e->getCode(), $e->getMessage()))));
            $this->setErr($e->getCode(), $e->getMessage());
        }
    }
}