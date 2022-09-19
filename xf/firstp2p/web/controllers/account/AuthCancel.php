<?php
/**
 * 存管系统-取消授权
 * 
 */

namespace web\controllers\account;

use web\controllers\BaseAction;
use libs\common\WXException;
use libs\utils\Logger;
use core\service\SupervisionAccountService;

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
            // 查询用户是否开启存管系统帐号
            $isSupervisionData = $this->rpc->local('SupervisionAccountService\isSupervision', [$userId]);
            $isOpenAccount = isset($isSupervisionData['isSvUser']) ? (int)$isSupervisionData['isSvUser'] : 0;
            if (empty($isOpenAccount)) {
                throw new WXException('ERR_NOT_OPEN_ACCOUNT');
            }
            if ($grant === 'quickbid') {
                // 随鑫约-用户是否有未结束的预约记录
                $userValidReservelist = $this->rpc->local('UserReservationService\getUserValidReserveList', [$userId]);
                if (!empty($userValidReservelist['userReserveList'])) {
                    throw new WXException('ERR_RESERVE_NOCANCEL');
                }
                // 随鑫约-授权组
                $grantList = [SupervisionAccountService::GRANT_INVEST, SupervisionAccountService::GRANT_WITHDRAW_TO_SUPER];
            }else if ($grant === 'yxt') {
                // 银信通-用户是否有未还款的记录
                $userCreditCnt = $this->rpc->local('CreditLoanService\getNotFinishCreditCount', [$user_info['id']]);
                if ((int)$userCreditCnt > 0) {
                    throw new WXException('ERR_YXT_NOCANCEL');
                }
                // 银信通-授权组
                $grantList = [SupervisionAccountService::GRANT_WITHDRAW_TO_YXT];
            }
            // 取消授权
            $supervisionAccountObj = new SupervisionAccountService();
            $cancelRet = $supervisionAccountObj->memberAuthorizationCancel($userId, $grantList);
            if (empty($cancelRet) || $cancelRet['status'] != SupervisionAccountService::RESPONSE_SUCCESS) {
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