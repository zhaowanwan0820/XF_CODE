<?php
/**
 * 存管系统-查询是否在存管行开户
 * 
 */

namespace web\controllers\deal;

use web\controllers\BaseAction;
use libs\common\WXException;
use libs\utils\Logger;
use core\service\UserService;

class IsOpenAccount extends BaseAction {
    /**
     * 是否页面
     * @var boolean
     */
    const IS_H5 = false;

    public function init() {}

    public function invoke() {
        try{
            if (!$this->ajax_checklogin()) {
                throw new WXException('ERR_USER_NOLOGIN');
            }
            // 用户UID
            $userId = intval($GLOBALS['user_info']['id']);
            // 查询用户是否在存管系统开户
            $isSupervisionData = $this->rpc->local('SupervisionAccountService\isSupervision', [$userId]);
            $isOpenAccount = isset($isSupervisionData['isSvUser']) ? (int)$isSupervisionData['isSvUser'] : 0;
            //是否实名和绑卡
            $userService = new UserService($GLOBALS['user_info']['id']);
            $checkBindCardRet = $userService->isBindBankCard();
            $this->json_data = ['status'=>$isOpenAccount, 'wxStatus'=> (int)$checkBindCardRet['ret']];

            Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, APP, 'SUPERVISION', sprintf('查询是否开通存管账户成功,userId:%d,isOpenAccount:%d', $userId, $isOpenAccount))));
            return true;
        }catch(\Exception $e) {
            Logger::error(implode(' | ', array(__CLASS__, __FUNCTION__, APP, 'SUPERVISION', sprintf('查询是否开通存管账户失败,userId:%d,ExceptionCode:%s,ExceptionMsg:%s', $userId, $e->getCode(), $e->getMessage()))));
            $this->setErr($e->getCode(), $e->getMessage());
        }
    }
}
