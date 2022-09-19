<?php
/**
 * AccountLimitService.php
 *
 * @date 2018-04-11
 * @author 王群强 <wangqunqiang@ucfgroup.com>
 */

namespace core\service;

use libs\utils\PaymentApi;

use core\service\AccountService;
use core\service\UserCarryService;

use core\dao\WithdrawLimitModel;
use core\dao\SupervisionWithdrawLimitModel;

use NCFGroup\Protos\Ptp\Enum\UserAccountEnum;
use NCFGroup\Common\Library\ApiService;

/**
 * Class AccountLimitService
 * @package core\service
 */
class AccountLimitService extends BaseService {

    /**
     *  限制提现规则
     * @param integer $userId 用户 id
     * @param integer $withdrawAmount 需要操作用户可用金额
     * @param integer $platform 平台 业务类型
     * @param integer $accountType 账户类型
     * @param boolean $useBonus 是否使用红包余额
     * @return boolean 是否可以操作申请的余额
     */
    public function canWithdrawAmount($userId, $withdrawAmount, $platform, $accountType, $useBonus = true)
    {
        try {
            // 存管账户使用存管系统的余额
            $supervisionBalance = false;
            if ($platform == UserAccountEnum::PLATFORM_SUPERVISION)
            {
                $supervisionBalance = true;
            }
            $availableAmount = $this->getAvailableAmount($userId, $platform, $accountType, $supervisionBalance, $useBonus);
            // 判断用户是否存在限制提现
            $limitRemainAmount = $this->getRemainAmtByPlatformAndAccountType($userId, $platform, $accountType);
            if (app_conf('ENV_FLAG') == 'test') {
                PaymentApi::log("[canWithdrawAmount] userId：{$userId} platform：{$platform}  accountType：{$accountType} withdrawAmount：{$withdrawAmount} availableAmount：{$availableAmount} limitRemainAmount：{$limitRemainAmount}");
            }
            // 如果为不限制，则只判断用户可用余额是否足够
            if($limitRemainAmount == '-1') {
                return true;
            }
            // 判断用户是否可以操作资金
            return $withdrawAmount <= min($availableAmount, $limitRemainAmount) ? true : false;
        } catch (\Exception $e) {
            return false;
        }
    }


    /**
     * 查询用户是否在某平台下某种账户的限制提现
     * @param integer $userId 用户 id
     * @param integer $platform 平台业务类型
     * @param integer $accountType 用户账户类型
     * @return integer 如果有返回限制规则下可用的额度
     */
    public function getRemainAmtByPlatformAndAccountType($userId, $platform, $accountType)
    {
        $limitRule = WithdrawLimitModel::instance()->findByViaSlave(" user_id = '{$userId}' AND `state` = '".UserCarryService::WITHDRAW_LIMIT_PASSED."' AND platform ='{$platform}' AND account_type = '{$accountType}'");

        // 如果没有规则
        if (empty($limitRule)) {
            // 如果没有规则，则网贷借款户不允许提现
            if ($platform == UserAccountEnum::PLATFORM_SUPERVISION && $accountType == UserAccountEnum::ACCOUNT_FINANCE) {
                return 0;
            }
            return -1;
        }

        // 如果存在规则
        if ($platform == UserAccountEnum::PLATFORM_WANGXIN || ($platform == UserAccountEnum::PLATFORM_SUPERVISION && $accountType != UserAccountEnum::ACCOUNT_FINANCE)) {
            // 网信账户 或者 网贷 非借款资户，如果有限制提现，则可用额度为0
            $accountInfo = (new AccountService())->getAccountByPlatformAndAccountType($userId, $platform, $accountType);
            // 网信账户 或者 网贷非借款户， 可用余额为 用户可用资金减去限制提现的金额之后如果可用还大于0的部分
            return  $accountInfo['amount'] - $limitRule['amount']*100 > 0 ? ($accountInfo['amount'] - $limitRule['amount']*100) : 0;
        }

        // 如果是网贷借款户
        if ($platform == UserAccountEnum::PLATFORM_SUPERVISION && $accountType == UserAccountEnum::ACCOUNT_FINANCE) {
            // 网贷借款户如果有白名单限制 则可用额度为剩余可用额度
            return intval($limitRule['remain_money']);
        }

        return -1;
    }

    /**
     *  获取用户账户可用余额信息 单位分
     * @param integer $userId 用户 id
     * @param integer $platform 平台业务类型
     * @param integer $accountType 账户类型
     * @param boolean $supervisionBalance 是否读取存管系统余额
     * @param boolean $useBonus 是否使用红包
     * @return integer amount 单位分
     */
    public function getAvailableAmount($userId, $platform, $accountType, $supervisionBalance = false, $useBonus = true)
    {
        $accountService = new AccountService();
        $accountInfo = $accountService->getAccountByPlatformAndAccountType($userId, $platform, $accountType);
        if ($supervisionBalance && $accountInfo['is_supervision'] == true) {
            $supervisionAccountInfo = $accountService->getSupervisionAccountInfo($accountInfo['accountId'], $platform, true);
            // 替换掉本地 accountInfo 的余额
            if (isset($supervisionAccountInfo['amount'])) {
                $accountInfo['amount'] = $supervisionAccountInfo['amount'];
            }
        }

        //使用红包
        if ($useBonus) {
            $bonusInfo = (new \core\service\BonusService())->getUsableBonus($userId, true);
            $bonusMoney = isset($bonusInfo['money']) ? bcmul($bonusInfo['money'], 100) : 0;
            $accountInfo['amount'] += $bonusMoney;
        }

        return $accountInfo['amount'];
    }

    const LIMIT_AMOUNT_WHITELIST = 1;
    const LIMIT_BLACKLIST = 2;
    const NO_LIMIT = 0;
    public function getAllLimits($userId, $isSupervision = false)
    {
        $limits = [];
        if ($isSupervision) {
            $limits = ApiService::rpc('ncfph', 'account/getAllLimits', ['accountId' => $userId]);
        } else {
            $limitRules = WithdrawLimitModel::instance()->findAllViaSlave(" user_id = '{$userId}' AND `state` = '".UserCarryService::WITHDRAW_LIMIT_PASSED."'");
            foreach ($limitRules as $rule)
            {
                $row = $rule->getRow();
                if ($isSupervision && $row['platform'] == UserAccountEnum::PLATFORM_WANGXIN)
                {
                    continue;
                }
                $limits[] = [
                    'id' => $row['id'],
                    'platform' => $row['platform'],
                    'account_type' => $row['account_type'],
                    'remain_money' => $row['remain_money'],
                    'money' => $row['amount']
                ];
            }
        }
        return $limits;
    }

    public function minusRemainMoney($id, $withdrawAmount, $isSupervision = false)
    {
        if (empty($id))
        {
            return true;
        }
        if ($isSupervision)
        {
            return ApiService::rpc('ncfph', 'account/minusRemainMoney', [
                'id' => $id,
                'withdrawAmount' => $withdrawAmount,
            ]);
        } else {
            $withdraw = WithdrawLimitModel::instance()->find($id);
            return $withdraw->minusRemainMoney($withdrawAmount);
        }
    }

    public function addRemainMoney($id, $withdrawAmount, $isSupervision = false)
    {
        if (empty($id))
        {
            return true;
        }
        if ($isSupervision)
        {
            return ApiService::rpc('ncfph', 'account/addRemainMoney', [
                'id' => $id,
                'withdrawAmount' => $withdrawAmount,
            ]);
        } else {
            $withdrawLimit = WithdrawLimitModel::instance()->find($id);
            return $withdrawLimit->addRemainMoney($withdrawAmount);
       }
    }

    /**
     *  判断是否可以创建已提请状态的 客户记录
     */
    public static function  updateWithdrawLimitRecord($limitId, $isSupervision = false)
    {
        if ($isSupervision)
        {
            return ApiService::rpc('ncfph', 'account/updateLimitRecord', [
                'limitId' => $limitId,
            ]);
        } else {
            $withdrawLimit = WithdrawLimitModel::instance()->find($limitId);
            if ($withdrawLimit['remain_money'] == 0)
            {
                $db = \libs\db\Db::getInstance('firstp2p', 'master');
                try {
                    $db->startTrans();
                    $_toUpdate = [
                        'status' => UserCarryService::WITHDRAW_LIMIT_STATUS_FINISH,
                        'update_time' => get_gmtime(),
                    ];
                    $db->autoExecute('firstp2p_withdraw_limit_record', $_toUpdate, 'UPDATE', 'wl_id = '.$limitId);
                    $db->query("DELETE FROM firstp2p_withdraw_limit WHERE id = '{$limitId}'");
                    $db->commit();
                } catch (\Exception $e) {
                    $db->rollback();
                }
            }
       }
    }
}
