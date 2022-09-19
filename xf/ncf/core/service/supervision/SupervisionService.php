<?php
namespace core\service\supervision;

use libs\utils\Logger;
use libs\utils\ABControl;
#use core\dao\ConfModel;
use core\enum\UserEnum;
use core\enum\SupervisionEnum;
use core\service\user\UserService;
use core\service\supervision\SupervisionBaseService;
use core\service\supervision\SupervisionAccountService;
use core\service\supervision\SupervisionFinanceService;
use core\service\account\AccountService;
use core\dao\supervision\SupervisionTransferModel;

/**
 * P2P存管
 */
class SupervisionService extends SupervisionBaseService
{
    //缓存开关
    private static $isSupervisionOpen = null;

    /**
     * 存管功能开关
     * 加上ABtest
     * 弃用，存管不可能关闭
     * @return boolean
     */
    public static function isSupervisionOpen()
    {
        if (self::$isSupervisionOpen === null) {
            self::$isSupervisionOpen = false;
            if((int)app_conf('SUPERVISION_SWITCH') === 1 || ABControl::getInstance()->hit('supervisionOpen')) {
                self::$isSupervisionOpen = true;
                Logger::info(sprintf("isSupervisionOpen. userId: %s", isset($GLOBALS['user_info']['id']) ? $GLOBALS['user_info']['id'] : 0));
            }
        }
        return self::$isSupervisionOpen;
    }

    /**
     * 存管-控制PC取消授权的开关
     * 0:不显示1:显示
     * @return boolean
     */
    public static function isCancelAuthOpen()
    {
        if((int)app_conf('SUPERVISION_PCCANCELAUTH_SWITCH') === 1) {
            return true;
        }
        return false;
    }

    /**
     * 存管服务降级
     */
    public static function isServiceDown()
    {
        if((int)app_conf('SUPERVISION_SERVICE_DOWN_SWITCH') === 1) {
            return true;
        }
        return false;
    }

    /**
     * 存管服务降级 实时
     */
    public static function isServiceDownRt()
    {
        $switch = ConfModel::instance()->get('SUPERVISION_SERVICE_DOWN_SWITCH');
        if (isset($switch['value']) && (int) $switch['value'] === 1) {
            return true;
        }
        return false;
    }

    /**
     * 存管服务降级提示信息
     */
    public static function maintainMessage()
    {
        return app_conf('SUPERVISION_SERVICE_MAINTAINCE_MESSAGE')?:'海口联合农商银行系统维护中，请稍后再试';
    }

    /**
     * 存管相关数据
     */
    public static function svInfo($accountId)
    {
        $data = [
            'status'        => 0,
            'isSvUser'      => 0,
            'userPurpose'   => 0,
            'isSvUser'      => 0,
            'svBalance'     => 0,
            'svFreeze'      => 0,
            'svMoney'       => 0,
            'isActivated'   => 0,
        ];
        $svStatus = (int) self::isSupervisionOpen();
        $data['status'] = $svStatus;
        if ($svStatus) {
            $accountInfo = AccountService::getAccountInfoById($accountId);
            $sas = new SupervisionAccountService();
            $svRes = $sas->isSupervisionUser($accountInfo);
            $data['isSvUser'] = intval($svRes);
            $data['userPurpose'] = AccountService::getAccountType($accountInfo);
            // 网信普惠用户投资一律验证交易密码
            if ($data['isSvUser']) {
                $data['svBalance'] = bcdiv($accountInfo['money'], 100, 2);
                $data['svFreeze'] = bcdiv($accountInfo['lock_money'], 100, 2);
                $data['svMoney'] = bcdiv($accountInfo['lock_money'] + $accountInfo['money'], 100, 2);
                //判断用户是否是存量未激活用户
                $data['isActivated'] = AccountService::isUnactivated($accountInfo) ? 0 : 1;
            }
        }
        Logger::info('svInfo:'.json_encode($data));
        return $data;
    }

    /*是否存管升级用户 弃用*/
    public static function isUpgradeAccount($userId)
    {
        if ($userId) {
            $result = UserService::checkUserTag(UserEnum::SV_UPGRADE_USER, $userId);
            return $result;
        }
        return false;
    }

    /**
     * 同步划转第二步请求存管行完成划转并处理结果
     */
    public function requestSupervisionInterface($direction, $params) {
        $financeSrv = new SupervisionFinanceService();
        try {
            $transferResult = [];
            if ($direction == SupervisionTransferModel::DIRECTION_TO_WX) {
                $transferResult = $financeSrv->accountSuperWithdraw($params);
            } else if ($direction == SupervisionTransferModel::DIRECTION_TO_SUPERVISION) {
                unset($params['superUserId']);
                $transferResult = $financeSrv->superRecharge($params);
            } else {
                PaymentApi::log('supervision transfer fail, unsupported direction '.$direction);
            }

            if (isset($transferResult['respCode']) && $transferResult['respCode'] !== SupervisionEnum::RESPONSE_CODE_SUCCESS) {
                throw new \Exception($transferResult['respMsg']);
            }
            return true;
       } catch (\Exception $e) {
            PaymentApi::log('余额划转审批失败,'.$e->getMessage());
            return false;
       }
    }
}

