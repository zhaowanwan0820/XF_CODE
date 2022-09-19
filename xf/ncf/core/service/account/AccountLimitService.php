<?php
/**
 * AccountLimitService.php
 *
 * @date 2018-04-11
 * @author 王群强 <wangqunqiang@ucfgroup.com>
 */

namespace core\service\account;

use libs\db\Db;
use libs\utils\PaymentApi;
use core\enum\UserAccountEnum;
use core\enum\SupervisionEnum;
use core\service\BaseService;
use core\service\user\UserService;
use core\service\user\UserCarryService;
use core\service\bonus\BonusService;
use core\service\account\AccountService;
use core\service\supervision\SupervisionAccountService;
use core\dao\account\WithdrawLimitModel;
use core\dao\account\WithdrawLimitRecordModel;
use core\dao\supervision\SupervisionWithdrawLimitModel;

/**
 * Class AccountLimitService
 * @package core\service
 */
class AccountLimitService extends BaseService {

    /**
     *  限制提现规则
     * @param mix $user 用户id 或者 用户信息
     * @param integer $withdrawAmount 需要操作用户可用金额
     * @param integer $platform 平台 业务类型
     * @param integer $accountType 账户类型
     * @param boolean $useBonus 是否使用红包余额
     * @return boolean 是否可以操作申请的余额
     */
    public function canWithdrawAmount($user, $withdrawAmount, $platform, $accountType, $useBonus = true, $bonusInfo = [])
    {
        $userId = is_array($user) ? $user['id'] : $user;
        try {
            // 存管账户使用存管系统的余额
            $supervisionBalance = true;
            $availableAmount = $this->getAvailableAmount($user, $platform, $accountType, $supervisionBalance, $useBonus, $bonusInfo);
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
            if ($accountType == UserAccountEnum::ACCOUNT_FINANCE) {
                return 0;
            }
            return -1;
        }

        // 如果存在规则
        if ($accountType != UserAccountEnum::ACCOUNT_FINANCE) {
            // 网信账户 或者 网贷 非借款资户，如果有限制提现，则可用额度为0
            $accountInfo = $this->getAccountByPlatformAndAccountType($userId, $platform, $accountType);
            // 网信账户 或者 网贷非借款户， 可用余额为 用户可用资金减去限制提现的金额之后如果可用还大于0的部分
            return $accountInfo['amount'] - $limitRule['amount']*100 > 0 ? ($accountInfo['amount'] - $limitRule['amount']*100) : 0;
        }

        // 如果是网贷借款户
        if ($accountType == UserAccountEnum::ACCOUNT_FINANCE) {
            // 网贷借款户如果有白名单限制 则可用额度为剩余可用额度
            return intval($limitRule['remain_money']);
        }

        return -1;
    }

    /**
     *  获取用户账户可用余额信息 单位分
     * @param mix $user 用户id 或者 用户信息
     * @param integer $platform 平台业务类型
     * @param integer $accountType 账户类型
     * @param boolean $supervisionBalance 是否读取存管系统余额
     * @param boolean $useBonus 是否使用红包
     * @return integer amount 单位分
     */
    public function getAvailableAmount($user, $platform, $accountType, $supervisionBalance = false, $useBonus = true, $bonusInfo = [])
    {
        $userId = is_array($user) ? $user['id'] : $user;
        $accountInfo = $this->getAccountByPlatformAndAccountType($userId, $platform, $accountType);
        $supervisionAccountInfo = $this->getSupervisionAccountInfo($accountInfo['accountId'], $platform, true);
        // 替换掉本地 accountInfo 的余额
        if (isset($supervisionAccountInfo['amount'])) {
            $accountInfo['amount'] = $supervisionAccountInfo['amount'];
        }
        //使用红包
        if ($useBonus) {

            if (is_array($user)) {
                $isEnterprise = $user['is_enterprise_user'];
            } else {
                $userInfo = UserService::getUserById($user);
                $isEnterprise = $userInfo['is_enterprise_user'];
            }

            //查询红包信息
            if (empty($bonusInfo)) {
                $bonusInfo = BonusService::getUsableBonus($userId, true, 0, false, $isEnterprise);
            }
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
        $limitRules = WithdrawLimitModel::instance()->findAllViaSlave(" user_id = '{$userId}' AND `state` = '".UserCarryService::WITHDRAW_LIMIT_PASSED."'");
        $limits = [];
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
        return $limits;
    }

    public function minusRemainMoney($id, $withdrawAmount)
    {
        if (empty($id))
        {
            return true;
        }
        $withdraw = WithdrawLimitModel::instance()->find($id);
        if (!$withdraw)
        {
            return true;
        }
        return $withdraw->minusRemainMoney($withdrawAmount);
    }

    public function addRemainMoney($id, $withdrawAmount)
    {
        if (empty($id))
        {
            return true;
        }
        $withdrawLimit = WithdrawLimitModel::instance()->find($id);
        if (!$withdrawLimit)
        {
            return true;
        }
        return $withdrawLimit->addRemainMoney($withdrawAmount);
    }

    /**
     *  判断是否可以创建已提请状态的 客户记录
     */
    public static function updateWithdrawLimitRecord($limitId)
    {
        $withdrawLimit = WithdrawLimitModel::instance()->find($limitId);
        if ($withdrawLimit['remain_money'] == 0)
        {
            $db = Db::getInstance('firstp2p', 'master');
            try {
                $db->startTrans();
                $_toUpdate = [
                    'status' => UserCarryService::WITHDRAW_LIMIT_STATUS_FINISH,
                    'update_time' => get_gmtime(),
                ];
                $withdrawRecordTableName = WithdrawLimitRecordModel::instance()->tableName();
                $db->autoExecute($withdrawRecordTableName, $_toUpdate, 'UPDATE', 'wl_id = '.$limitId);
                $withdrawTableName = $withdrawLimit->tableName();
                $db->query("DELETE FROM `{$withdrawTableName}` WHERE id = '{$limitId}'");
                $db->commit();
            } catch (\Exception $e) {
                $db->rollback();
            }
        }
    }


    /**
     * 获取用户账户所有信息， 聚合账户信息
     * @param integer $userId 用户 id
     */
    public function getAccountList($userId)
    {
        $accounts = [];
        // 存管账户信息
        $supervisionBalances = AccountService::getAccountListByUserId($userId, UserAccountEnum::PLATFORM_SUPERVISION);
        foreach ($supervisionBalances as $supervisionAccount) {
            $accounts[UserAccountEnum::PLATFORM_SUPERVISION][] = [
                'accountId' => $userId,
                'amount' => $supervisionAccount['money'],
                'freezeAmount' => $supervisionAccount['lock_money'],
                'accountTypeDesc' => $this->getAccountTypeDescription(UserAccountEnum::PLATFORM_SUPERVISION, $supervisionAccount['account_type']),
                'accountType' => $supervisionAccount['account_type'],
                'is_supervision' => true
            ];

        }

        return $accounts;
    }


    /**
     *   返回用户账户标准类型描述 false 为没有匹配的类型
     * @param integer $platform 平台业务类型
     * @param integer $accountType 账户数据库 记录类型
     * @return boolean|integer
     */
    public function getAccountTypeDescription($platform, $accountType)
    {
        switch($platform) {
            case UserAccountEnum::PLATFORM_SUPERVISION:
                return isset(UserAccountEnum::$accountSupervisionMap[$accountType])? UserAccountEnum::$accountDesc[UserAccountEnum::PLATFORM_SUPERVISION][$accountType] : ' 通用账户';
        }
        return '通用账户';
    }


    /**
     *  获取用户某个业务下的某个账户
     * @param integer $userId
     * @param integer $platform
     * @param integer $accountType
     * @return array | boolean
     */
    public function getAccountByPlatformAndAccountType($userId, $platform, $accountType)
    {
        $accountList = $this->getAccountList($userId);
        $platformAccount = isset($accountList[$platform]) ? $accountList[$platform] : array();
        foreach ($platformAccount as $account) {
            if ($account['accountType'] == $accountType) {
                return $account;
            }
        }
        return false;
    }


    /**
     *  返回用户指定业务下的指定账户存管余额， 如果返回 false 则以资产中心余额或者用户余额为准
     * @param integer $accountId 账户 id
     * @param integer $platform 平台业务类型
     * @return boolean | array
     */
    public function getSupervisionAccountInfo($accountId, $platform)
    {
        $accountInfo =[];
        switch ($platform) {
            case UserAccountEnum::PLATFORM_WANGXIN: return false;
            case UserAccountEnum::PLATFORM_SUPERVISION:
                $userBalance = (new SupervisionAccountService())->balanceSearch($accountId);
                if (isset($userBalance['status']) && $userBalance['status'] == SupervisionEnum::RESPONSE_SUCCESS) {
                    $accountInfo['accountId'] = $accountId;
                    $accountInfo['amount'] = $userBalance['data']['availableBalance'];
                    $accountInfo['freezeAmount'] = $userBalance['data']['freezeBalance'];
                    return $accountInfo;
                }
        }
        return false;
    }

}
