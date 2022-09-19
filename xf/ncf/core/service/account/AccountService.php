<?php
/**
 * 账户服务类
 *
 * @author  weiwei12
 * @date 2018-6-22 18:08:29
 */
namespace core\service\account;

use libs\db\Db;
use libs\utils\Logger;
use libs\utils\PaymentApi;
use libs\utils\Alarm;
use libs\utils\Finance;
use libs\common\ErrCode;
use core\service\BaseService;
use core\service\user\UserService;
use core\service\user\UserCarryService;
use core\service\user\UserLoanRepayStatisticsService;
use core\service\bonus\BonusService;
use core\service\supervision\SupervisionAccountService;
use core\service\supervision\SupervisionService;
use core\service\msgbus\MsgbusService;
use core\dao\account\AccountModel;
use core\dao\account\AccountLogModel;
use core\enum\AccountEnum;
use core\enum\DealEnum;
use core\enum\UserAccountEnum;
use core\enum\SupervisionEnum;
use core\enum\MsgbusEnum;
use core\dao\deal\DealModel;
use core\dao\repay\DealLoanRepayModel;
use core\dao\supervision\SupervisionChargeModel;

class AccountService extends BaseService {

    const KEY_ACCOUNT_STATUS = 'account_status_%s';

    /**
     * 初始化账户
     * @param int $userId 账号ID
     * @param int $accountType 账户类型
     * @return int 账户ID
     */
    public static function initAccount($userId, $accountType, $platform = UserAccountEnum::PLATFORM_SUPERVISION) {
        if (empty($userId) || empty($accountType)) {
            return false;
        }
        /**
         * 多账户后再支持
         * $accountList = self::getAccountListByUserId($userId, $platform);
         * foreach ($accountList as $accountInfo) {
         *     if ($accountInfo['account_type'] == $accountType) {
         *         return $accountInfo['id'];
         *     }
         * }
         * 首次初始化账户，账户ID和账号ID写成一样
         * $autoIncr = count($accountList) > 0 ? true : false;
         * Logger::info(sprintf('initAccount. userId:%s, accountType:%s, autoIncr: %s', $userId, $accountType, $autoIncr));
         * return AccountModel::instance()->addAccount($userId, $accountType, $platform, $autoIncr);
         */

        $accountInfo = self::getAccountInfo($userId, $accountType);
        if (!empty($accountInfo)) {
            return $accountInfo['id'];
        }
        $autoIncr = false; //账户ID和账号ID写成一样，保证用户只有一个账户
        Logger::info(sprintf('initAccount. userId:%s, accountType:%s, autoIncr: %s', $userId, $accountType, $autoIncr));
        return AccountModel::instance()->addAccount($userId, $accountType, $platform, $autoIncr);
    }

    /**
     * 开通账户
     * @param int $accountId 账户ID
     * @return boolean
     */
    public static function openAccount($accountId) {
        $accountInfo = self::getAccountInfoById($accountId);
        if (!empty($accountInfo) && $accountInfo['status'] == AccountEnum::STATUS_OPENED) {
            return true;
        }
        //清理缓存
        self::clearAccountStatusCache($accountId);
        Logger::info(sprintf('openAccount. accountId: %s', $accountId));
        return AccountModel::instance()->openAccount($accountId);
    }

    /**
     * 设置账户未激活
     */
    public static function setUnactivated($accountId) {
        $accountInfo = self::getAccountInfoById($accountId);
        if (!empty($accountInfo) && $accountInfo['status'] == AccountEnum::STATUS_UNACTIVATED) {
            return true;
        }
        //清理缓存
        self::clearAccountStatusCache($accountId);
        Logger::info(sprintf('setUnactivated. accountId: %s', $accountId));
        return AccountModel::instance()->setUnactivated($accountId);
    }

    /**
     * 获取账户状态
     * @param mix $account 账户ID 或 账户信息
     * @param int $syncStatus 是否同步存管状态 默认同步状态
     * @return boolean
     */
    public static function getAccountStatus($account, $syncStatus = true) {
        $accountInfo = is_array($account) ? $account : self::getAccountInfoById($account);
        if (empty($accountInfo)) {
            return false;
        }
        $status = (int) $accountInfo['status'];
        $accountId = $accountInfo['id'];
        $userId = $accountInfo['user_id'];
        //如果是状态是默认值，调用存管接口查询
        if ($syncStatus && $status === AccountEnum::STATUS_DEFAULT) {
            //读取redis缓存，存管接口很脆弱:(
            $cache = self::getAccountStatusCache($accountId);
            if ($cache !== null) {
                Logger::info(sprintf('accountId:%s, AccountStatusCache:%s', $accountId, $cache));
                return (int) $cache;
            }

            //调用存管接口
            $supervisionAccountService = new SupervisionAccountService();
            $member = $supervisionAccountService->memberSearch($accountId);
            if (empty($member) || ($member['status'] != SupervisionEnum::RESPONSE_SUCCESS && $member['respCode'] != ErrCode::getCode('ERR_NOT_OPEN_ACCOUNT'))) {//排除掉未开户
                Logger::error(implode(' | ', array(__CLASS__, __FUNCTION__, sprintf('accountId:%s, memberInfo:%s', $accountId, json_encode($member)))));
            }
            //已开通
            if (!empty($member['data']) && !empty($member['data']['userId']) && $member['data']['status'] == 'T') {
                self::openAccount($accountId);
                $status = AccountEnum::STATUS_OPENED;
                // 同步网信存管状态
                UserService::updateSupervisionUserId($userId);
            }
            //未激活
            if (!empty($member['data']) && !empty($member['data']['userId']) && $member['data']['status'] == 'N') {
                self::setUnactivated($accountId);
                $status = AccountEnum::STATUS_UNACTIVATED;
                // 同步网信存管状态
                UserService::updateSupervisionUserId($userId);
            }
            //设置60秒缓存
            self::setAccountStatusCache($accountId, $status);
        }
        Logger::info(sprintf('getAccountStatus. accountId:%s, AccountStatus:%s', $accountId, $status));
        return $status;
    }

    /**
     * 是否开通
     * @param mix $account 账户ID 或 账户信息
     * @return boolean
     */
    public static function isOpened($account) {
        $accountInfo = is_array($account) ? $account : self::getAccountInfoById($account);
        if (empty($accountInfo)) {
            return false;
        }
        return (int) $accountInfo['status'] === AccountEnum::STATUS_OPENED ? true : false;
    }

    /**
     * 是否未激活
     * @param mix $account 账户ID 或 账户信息
     * @return boolean
     */
    public static function isUnactivated($account) {
        $accountInfo = is_array($account) ? $account : self::getAccountInfoById($account);
        if (empty($accountInfo)) {
            return false;
        }
        return (int) $accountInfo['status'] === AccountEnum::STATUS_UNACTIVATED ? true : false;
    }

    /**
     * 注销账户
     * @param int $accountId 账户ID
     * @return boolean
     */
    public static function removeAccount($accountId) {
        // 删除账户表记录
        $accountInfo = AccountModel::instance()->find($accountId);
        if (empty($accountInfo)) {
            return true;
        }
        Logger::info(sprintf('removeAccount, accountId: %s', $accountId));
        return $accountInfo->remove();
    }

    /**
     * 获取账户类型
     * @param mix $account 账户ID 或 账户信息
     * @return int
     */
    public static function getAccountType($account) {
        $accountInfo = is_array($account) ? $account : self::getAccountInfoById($account);
        if (empty($accountInfo)) {
            return false;
        }
        return isset($accountInfo['account_type']) ? $accountInfo['account_type'] : null;
    }

    /**
     * 判断用户是否允许投资
     */
    public static function allowAccountLoan($accountType) {
        //借贷混合户,投资户允许投资
        return in_array(intval($accountType), array(UserAccountEnum::ACCOUNT_INVESTMENT, UserAccountEnum::ACCOUNT_MIX));
    }

    /**
     * 获取用户账户ID
     * 已开户、未激活
     * @param integer $userId
     * @param int $accountType 账户类型
     * @param int $platform  平台 默认网贷
     * @return int
     */
    public static function getUserAccountId($userId, $accountType, $platform = UserAccountEnum::PLATFORM_SUPERVISION) {
        $result = self::_getUserAccountId($userId, $accountType, $platform);
        if ($result === false)
        {
            // 增加告警
            $messageTitle = '获取用户网贷账户ID失败';
            $pushResult = Alarm::push('GET_USER_ACCOUNT_ID', $messageTitle, ['userId' => $userId, 'accountType' => $accountType, 'platform' => $platform]);
            if (!$pushResult)
            {
                Logger::error(__CLASS__.'::'.__FUNCTION__.$messageTitle.' 告警写入失败');
            }
        }
        return $result;
    }

    private static function _getUserAccountId($userId, $accountType, $platform = UserAccountEnum::PLATFORM_SUPERVISION)
    {
        //白名单用户不检查账户类型
        $whitelist = explode(',', app_conf('GET_ACCOUNT_WHITELIST'));
        if (in_array($userId, $whitelist)) {
            $accountInfo = AccountModel::instance()->getWhitelistAccountInfo($userId);
        } else {
            $accountInfo = self::getAccountInfo($userId, $accountType, $platform, false);
        }

        if (empty($accountInfo)) {
            return false;
        }
        return in_array(
            self::getAccountStatus($accountInfo),
            [AccountEnum::STATUS_OPENED, AccountEnum::STATUS_UNACTIVATED]
        ) ? $accountInfo['id'] : false;
    }

    /**
     * 获取账号ID
     * @param int $accountId 账户ID
     * @return int
     */
    public static function getUserId($accountId) {
        $accountInfo = self::getAccountInfoById($accountId);
        if (empty($accountInfo['user_id'])) {
            return false;
        }
        return (int) $accountInfo['user_id'];
    }

    /**
     * 通过账号ID，账户类型获取账户信息
     * @param int $userId 账号Id
     * @param int $accountType 账户类型
     * @return mix 输出金额单位分
     */
    public static function getAccountInfo($userId, $accountType, $platform = UserAccountEnum::PLATFORM_SUPERVISION) {
        $accInfo = AccountModel::instance()->getAccountInfo($userId, $accountType, $platform, false);
        $accountInfo = !empty($accInfo) ? $accInfo->getRow() : [];
        return $accountInfo;
    }

    /**
     * 通过账户ID获取账户信息
     * @param int $accountId 账户Id
     * @return mix 注：输出金额单位分
     */
    public static function getAccountInfoById($accountId) {
        if (empty($accountId)) {
            return false;
        }
        $accInfo = AccountModel::instance()->find($accountId);
        $accountInfo = !empty($accInfo) ? $accInfo->getRow() : [];
        //设置静态缓存
        return $accountInfo;
    }

    /**
     * 通过用户Id获取账户列表
     */
    public static function getAccountListByUserId($userId, $platform = UserAccountEnum::PLATFORM_SUPERVISION) {
        $accountList = AccountModel::instance()->getAccountList($userId, $platform);
        return $accountList;
    }

    /**
     * 根据用户ID获取账户ID列表
     * @param int $userId
     * @param int $platform
     */
    public static function getAccountIdsByUserId($userId, $platform = UserAccountEnum::PLATFORM_SUPERVISION) {
        // 根据用户ID获取账户ID列表
        $accountIds = [];
        if (!empty($userId)) {
            $accountList = self::getAccountListByUserId($userId, $platform);
            foreach ($accountList as $account) {
                $accountIds[] = $account['id'];
            }
        }
        return $accountIds;
    }

    /**
     * 获取账户余额
     * @param integer $userId
     * @param int $accountType 账户类型
     * @param int $platform  平台 默认网贷
     * @param boolean $slave
     * @return array 输出金额单位元
     */
    public static function getAccountMoney($userId, $accountType, $platform = UserAccountEnum::PLATFORM_SUPERVISION) {
        $accountInfo = AccountModel::instance()->getAccountInfo($userId, $accountType, $platform, false);
        $result = [
            'money'     => isset($accountInfo['money']) ? bcdiv($accountInfo['money'], 100, 2) : 0,
            'lockMoney' => isset($accountInfo['lock_money']) ? bcdiv($accountInfo['lock_money'], 100, 2) : 0,
        ];
        $result['totalMoney'] = bcadd($result['money'], $result['lockMoney'], 2);
        return $result;
    }

    /**
     * 获取账户余额
     * @param integer $accountId
     * @return array 输出金额单位元
     */
    public static function getAccountMoneyById($accountId) {
        if (empty($accountId)) {
            return false;
        }
        $accountInfo = AccountModel::instance()->find($accountId, 'money,lock_money');
        $result = [
            'money'     => isset($accountInfo['money']) ? bcdiv($accountInfo['money'], 100, 2) : 0,
            'lockMoney' => isset($accountInfo['lock_money']) ? bcdiv($accountInfo['lock_money'], 100, 2) : 0,
        ];
        $result['totalMoney'] = bcadd($result['money'], $result['lockMoney'], 2);
        return $result;
    }

    /**
     * 获取账户可用余额，包括红包
     * @param int $accountId 账户ID
     * @param int $bidMoney 投资金额
     * @param int $orderId
     * @param array $user
     */
    public static function getAccountMoneyInfo($accountId, $bidMoney = 0, $orderId = false, $user = array()) {
        if (empty($accountId)) {
            return false;
        }

        $accountInfo = self::getAccountInfoById($accountId);
        $userId = !empty($accountInfo['user_id']) ? $accountInfo['user_id'] : 0;
        if (empty($userId)) {
            return false;
        }

        $accountMoney = bcdiv($accountInfo['money'], 100, 2);
        $accountLockMoney = bcdiv($accountInfo['lock_money'], 100, 2);

        if (empty($user)){
            $user = UserService::getUserById($userId);
        }
        $isEnterprise = $user['is_enterprise_user'];

        //获取用户红包
        $bonusInfo = BonusService::getUsableBonus($userId, true, $bidMoney, $orderId, $isEnterprise);
        $bonusMoney = isset($bonusInfo['money']) ? $bonusInfo['money'] : 0; // 红包余额

        //账户限制金额
        $userCarryService = new UserCarryService();
        $limitMoney = $userCarryService->getLimitAmountByUserId($accountId);

        //银行金额
        $bankMoney = $bankLockMoney = 0; // 银行余额
        $superAccountService = new SupervisionAccountService();
        $isSuperUser = $superAccountService->isSupervisionUser($accountInfo);
        if ($isSuperUser && SupervisionService::isServiceDown() === false) {
            $res = $superAccountService->balanceSearch($accountId);
            if ($res['status'] == SupervisionEnum::RESPONSE_FAILURE) {
                $bankMoney = 0;
                Logger::error(implode(" | ", array(__CLASS__,__FUNCTION__,"获取存管系统余额失败 errMsg:".$res['respMsg'])));
            } else {
                $bankMoney = bcdiv($res['data']['availableBalance'], 100, 2);
                $bankLockMoney = bcdiv($res['data']['freezeBalance'], 100, 2);
            }
        }

        $moneyInfo = [
            'accountId' => $accountId,
            'userId' => $userId,
            'accountMoney' => $accountMoney,
            'accountLockMoney' => $accountLockMoney,
            'bonusMoney' => $bonusMoney,
            'bonusInfo' => $bonusInfo,
            'bankMoney' => $bankMoney,
            'bankLockMoney' => $bankLockMoney,
            'limitMoney' => $limitMoney,
        ];
        Logger::info(implode(" | ", array(__CLASS__,__FUNCTION__,"获取账户余额信息 moneyInfo:".json_encode($moneyInfo))));
        return $moneyInfo;
    }

    /**
     * 生成业务token
     */
    private static function generateBizToken($logInfo, $bizToken) {

        if (empty($bizToken)) {
            return '';
        }

        foreach($bizToken as $key => $value) {
            $bizToken[$key] = strval($value);
        }

        return json_encode($bizToken);
    }

    /**
     * 直接进行资金变动
     * @param int $accountId 账户ID
     * @param float $money 金额 元
     * @param string $message 类型
     * @param string $note 备注
     * @param string $note 订单ID
     * @param int $moneyType 见AccountEnum
     * @param int $isAsync 是否异步操作
     * @param int $isChangeMoney true更新账户资金，false只记录资金记录
     * @param int $adminId 管理员id
     * @param array $bizToken 业务标识数据
     * throw new \Exception
     * @return boolean
     **/
    public static function changeMoney($accountId, $money, $message, $note, $moneyType, $isAsync = false, $isChangeMoney = true, $adminId = 0, $bizToken = []) {
        //账户ID不能为空
        if (empty($accountId)) {
            throw new \Exception('ChangeMoney. 账户ID不能为空');
        }
        //金额不能传负
        if (bccomp($money, 0, 2) === -1) {
            throw new \Exception('ChangeMoney. 金额不能传负');
        }
        //检查操作类型
        if (!isset(AccountEnum::$moneyTypeMap[$moneyType])) {
            throw new \Exception('ChangeMoney. 未知moneyType');
        }

        // 生成业务token
        $bizIds = $bizToken;
        $bizToken = self::generateBizToken($message, $bizToken);
        if (empty($bizToken)) {
            Logger::info("changeMoney no bizToken. log_info:$message, note:$note");
        }

        //是否异步执行
        $dealType = DealEnum::DEAL_TYPE_SUPERVISION; //默认网贷
        if ($isAsync === true) {
            $data = array(
                'user_id'       => $accountId,
                'money'         => $money,
                'message'       => $message,
                'note'          => $note,
                'money_type'    => $moneyType,
                'create_time'   => time(),
                'status'        => 0,
                'deal_type'     => $dealType,
                'biz_token'     => $bizToken,
            );
            $db = \libs\db\Db::getInstance('firstp2p');
            if (!$db->insert('firstp2p_money_queue', $data)) {
                throw new \Exception('Insert firstp2p_money_queue failed');
            }
            return true;
        }

        //更新账户表
        if ($isChangeMoney) {
            AccountModel::instance()->updateAccountMoney($accountId, $money, $moneyType);
        }

        //记录账户资金日志
        $accountLog = new AccountLogModel();
        $accountLog->log_info = $message;
        $accountLog->note = $note;
        $accountLog->log_time = get_gmtime();
        $accountLog->log_admin_id = $adminId;
        $accountLog->log_user_id = $accountId;
        $accountLog->user_id = $accountId;
        $accountLog->biz_token = $bizToken; //业务标识
        $accountLog->deal_id = isset($bizIds['dealId'])? intval($bizIds['dealId']):0;
        $accountLog->out_order_id = isset($bizIds['outOrderId'])? trim($bizIds['outOrderId']):'';
        //增加交易类型字段
        $accountLog->deal_type = $dealType;
        switch ($moneyType) {
            case AccountEnum::MONEY_TYPE_INCR:
                $accountLog->money = floatval($money);
                break;
            case AccountEnum::MONEY_TYPE_REDUCE:
                $accountLog->money = -floatval($money);
                break;
            case AccountEnum::MONEY_TYPE_LOCK:
                $accountLog->money = -floatval($money);
                $accountLog->lock_money = floatval($money);
                break;
            case AccountEnum::MONEY_TYPE_UNLOCK:
                $accountLog->money = floatval($money);
                $accountLog->lock_money = -floatval($money);
                break;
            case AccountEnum::MONEY_TYPE_LOCK_INCR:
                $accountLog->lock_money = floatval($money);
                break;
            case AccountEnum::MONEY_TYPE_LOCK_REDUCE:
                $accountLog->lock_money = -floatval($money);
                break;
        }
        $accountMoney = self::getAccountMoneyById($accountId);
        $accountLog->remaining_money = $accountMoney['money'];
        $accountLog->remaining_total_money = $accountMoney['totalMoney'];
        if(!$accountLog->insert()){
            throw new \Exception("ChangeMoney增加资金记录失败. accountId:{$accountId}");
        }

        $accountLog->remaing_lock_money = $accountLog->remaining_total_money - $accountLog->remaining_money;
        $log = array_merge(array(__FUNCTION__, @APP), $accountLog->getRow());
        Logger::info(implode(" | ", $log));

        $trace = debug_backtrace();
        $caller = isset($trace[1]['function']) ? basename($trace[0]['file']).'/'.$trace[1]['function'].':'.$trace[0]['line'] : '';
        PaymentApi::log("ChangeMoney. {$caller}, userLog:".json_encode($accountLog->getRow(), JSON_UNESCAPED_UNICODE));

        //同步到网信
        $syncInfo = $accountLog->getRow();
        $syncInfo['id'] = $accountLog->db->insert_id();
        $userId = self::getUserId($accountId);
        $syncInfo['user_id'] = $syncInfo['log_user_id'] = $userId;//转成真userId
        MsgbusService::produce(MsgbusEnum::TOPIC_ACCOUNT_LOG_SYNC, $syncInfo);
        PaymentApi::log("ChangeMoney. syncInfo:".json_encode($syncInfo, JSON_UNESCAPED_UNICODE));

        return true;
    }

    /**
     * 按账户转账
     * @param int $payerId 付款账户ID
     * @param int $receiverId 收款账户ID
     * @param float $money 金额
     * @param string $payerType 付款类型
     * @param string $payerNote 付款备注
     * @param string $receiverType 收款类型
     * @param string $receiverNote 收款备注
     * throw new \Exception
     * @return boolean
     */
    public static function transferMoney($payerId, $receiverId, $money,
        $payerType, $payerNote, $receiverType, $receiverNote, $payerAsync = false, $receiverAsync = false, $payerBizToken = [], $receiverBizToken = [],
        $payerMoneyType = AccountEnum::MONEY_TYPE_REDUCE, $receiverMoneyType = AccountEnum::MONEY_TYPE_INCR)
    {
        if ($payerId === $receiverId) {
            throw new \Exception('转入转出不能为同一账户');
        }
        $db = Db::getInstance('firstp2p');
        try {
            $db->startTrans();

            //付款账户扣钱
            self::changeMoney($payerId, $money, $payerType, $payerNote, $payerMoneyType, $payerAsync, true, 0, $payerBizToken);

            //收款账户加钱
            self::changeMoney($receiverId, $money, $receiverType, $receiverNote, $receiverMoneyType, $receiverAsync, true, 0, $receiverBizToken);

            $db->commit();

        } catch (\Exception $e) {
            $db->rollback();
            Logger::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' fail, payerId:'.$payerId.', receiverId:'.$receiverId.', money:'.$money.', errMsg:'.$e->getMessage());
            throw new \Exception('转账失败:'.$e->getMessage());
        }
        return true;
    }

    /**
     * 格式化数据
     */
    private static function formatInfo($accountInfo, $syncStatus = true) {
        if (empty($accountInfo)) {
            return [];
        }
        $accountInfo = is_object($accountInfo) ? $accountInfo->getRow() : $accountInfo;
        $isUnactivated = self::isUnactivated($accountInfo);
        $supervisionAccountService = new SupervisionAccountService();
        return [
            'accountId'         => $accountInfo['id'], //账户Id
            'userId'            => $accountInfo['user_id'], //用户Id
            'accountType'       => $accountInfo['account_type'], //账户类型
            'accountTypeDesc'   => UserAccountEnum::$accountDesc[UserAccountEnum::PLATFORM_SUPERVISION][$accountInfo['account_type']], //账户类型描述
            'status'            => $accountInfo['status'], //状态  0默认  1已开通  2未激活
            'statusDesc'        => AccountEnum::$statusMap[$accountInfo['status']],
            'isUnactivated'     => $isUnactivated, //未激活
            'money'             => bcdiv($accountInfo['money'], 100, 2), //可用金额，单位元
            'lockMoney'         => bcdiv($accountInfo['lock_money'], 100, 2), //冻结金额，单位元
            'totalMoney'        => bcdiv($accountInfo['lock_money'] + $accountInfo['money'], 100, 2), //总金额，单位元
            'openTime'          => $accountInfo['open_time'],  //开通时间
            'isSupervisionUser' => $supervisionAccountService->isSupervisionUser($accountInfo, $syncStatus), //是否是存管账户
        ];
    }

    /**
     * 通过账号id集合获取账户列表
     * @param array $userIds 账号id
     * @return array
     */
    public static function getListByUserIds($userIds, $syncStatus = true) {
        $accountList = AccountModel::instance()->getListByUserIds($userIds);
        $result = [];
        foreach ($accountList as $accountInfo) {
            $result[$accountInfo['user_id']][] = self::formatInfo($accountInfo);
        }
        return $result;
    }

    /**
     * 通过账户id集合获取信息
     * @param array $accountIds 账户id
     * @return array
     */
    public static function getInfoByIds($accountIds, $syncStatus = true) {
        $data = AccountModel::instance()->getInfoByIds($accountIds);
        $result = [];
        foreach ($data as $accountInfo) {
            $result[$accountInfo['id']] = self::formatInfo($accountInfo, $syncStatus);
        }
        return $result;
    }

    /**
     * 通账号id集合 和 账户类型集合 获取信息
     * @param array $userId 账号id
     * @param array $accountTypeList 账户类型
     * @return array
     */
    public static function getInfoByUserIdsAndTypeList($userIds, $accountTypeList, $syncStatus = true) {
        if (count($userIds) != count($accountTypeList)) {
            return false;
        }
        $condition = [];
        foreach ($userIds as $index => $userId) {
            $accountType = $accountTypeList[$index];
            $condition[] = sprintf('(user_id = %d AND account_type = %d AND platform = %d)', $userId, $accountType, UserAccountEnum::PLATFORM_SUPERVISION);
        }
        $conditionStr = implode(' OR ', $condition);
        $data = AccountModel::instance()->getInfoByCondition($conditionStr);
        $result = [];
        foreach ($data as $accountInfo) {
            $result[$accountInfo['user_id'] . '_' . $accountInfo['account_type']] = self::formatInfo($accountInfo, $syncStatus);
        }
        return $result;
    }

    /**
     * 获取用户状态缓存
     * @param int $accountId 账户ID
     * @return int
     */
    public static function getAccountStatusCache($accountId) {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $cacheKey = sprintf(self::KEY_ACCOUNT_STATUS, $accountId);
        return $redis->get($cacheKey);
    }

    /**
     * 设置用户状态缓存
     * @param int $accountId 账户ID
     * @return bool
     */
    public static function setAccountStatusCache($accountId, $status) {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $cacheKey = sprintf(self::KEY_ACCOUNT_STATUS, $accountId);
        return $redis->setex($cacheKey, 60, $status);
    }

    /**
     * 清理用户状态缓存
     * @param int $accountId 账户ID
     * @return bool
     */
    public static function clearAccountStatusCache($accountId) {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $cacheKey = sprintf(self::KEY_ACCOUNT_STATUS, $accountId);
        return $redis->del($cacheKey);
    }

    /*
     * 获取用户账户类型信息
     * @param $userPurpose 用户的账户类型
     * @return array
     */
    public static function getUserPurposeInfo($userPurpose) {
        $purposeList = !empty($GLOBALS['dict']['ENTERPRISE_PURPOSE']) ? $GLOBALS['dict']['ENTERPRISE_PURPOSE'] : [];
        return !empty($purposeList[(int)$userPurpose]) ? $purposeList[(int)$userPurpose] : [];
    }

    /**
     * 用户资产信息
     * @param $user_id
     */
    public static function getUserSummary($user_id,$is_add_duotou = true) {
        $remain_principal = 0; // 待收本金(含通知贷)
        $remain_interest = 0; // 待还利息(不含通知贷)
        $total_interest = 0; // 总收益 = 已赚利息+预期罚息+提前还款违约金

        $userAsset = UserLoanRepayStatisticsService::getUserAssets($user_id,true);
        $remain_principal = $userAsset['norepay_principal'];
        $remain_interest = $userAsset['norepay_interest'];
        $total_interest = Finance::addition(
            array($userAsset['load_earnings'], $userAsset['load_tq_impose'], $userAsset['load_yq_impose']),
            2
        );

        //贴息累计金额
        $extra = 0;
        $total_interest = bcadd($total_interest, $userAsset['dt_repay_interest'], 2);// 总收益
        // 智多鑫待投本金
        $dt_remain = bcsub($userAsset['dt_norepay_principal'], $userAsset['dt_load_money'], 2);
        // 普惠充值总金额
        $dayChargeAmount = SupervisionChargeModel::instance()->sumUserOnlineChargeAmountToday($user_id);

        $userAccoutMoney = self::getAccountMoneyById($user_id);

        $ret = array(
            'cash' => $userAccoutMoney ? $userAccoutMoney['money'] : 0,
            'freeze' => $userAccoutMoney ? $userAccoutMoney['lockMoney'] : 0,
            'corpus' => $remain_principal, // 待收本金
            'income' => $remain_interest, // 待收利息
            'earning_all' => bcadd($total_interest, $extra, 2), // 累计收益 = 总收益 + 贴息金额
            'compound_interest' => 0,
            'js_norepay_principal' => $userAsset['js_norepay_principal'], // 金锁的代收本金
            'js_norepay_earnings' => $userAsset['js_norepay_earnings'], // 金锁的代收收益
            'js_total_earnings' => $userAsset['js_total_earnings'], // 金锁的累计收益
            'p2p_principal' => $userAsset['norepay_principal'], // 仅p2p 本金

            'cg_principal' => $userAsset['norepay_principal'], // 存管在投本金
            'cg_income' => $userAsset['norepay_interest'], // 存管待收收益
            'cg_earnings' => $userAsset['norepay_interest'], // 存管累计收益

            'dt_norepay_principal' => $userAsset['dt_norepay_principal'],
            'dt_load_money' => $userAsset['dt_load_money'],
            'dt_remain' => $dt_remain, // 智多鑫待投本金

            'dayChargeAmount' => $dayChargeAmount, //用户普惠日充值总金额，单位元
        );
        return $ret;
    }

    /**
     * 用户资产分别统计
     * @param $user_id
     */
    public static function getUserSummaryExt($user_id) {
        $remain_principal = 0; // 待收本金(含通知贷)
        $remain_interest = 0; // 待还利息(不含通知贷)
        $total_interest = 0; // 总收益 = 已赚利息+预期罚息+提前还款违约金

        $userAsset = UserLoanRepayStatisticsService::getUserAssets($user_id);
        $remain_principal = $userAsset['norepay_principal'];
        $remain_interest = $userAsset['norepay_interest'];
        $total_interest = Finance::addition(
            array($userAsset['load_earnings'], $userAsset['load_tq_impose'], $userAsset['load_yq_impose']),
            2
        );

        //贴息累计金额
        $extra = 0;
        //p2p资产统计,包括大金所的
        $p2p_user_asset = array(
            'corpus' => $remain_principal, // 待收本金
            'income' => $remain_interest, // 待收利息
            'earning_all' => bcadd($total_interest, $extra, 2), // 累计收益 = 总收益 + 贴息金额
            'compound_interest' => 0,
            'p2p_principal' => $userAsset['norepay_principal'], // 仅p2p 本金
        );

        //dajinsuo资产统计
        $js_user_asset = array(
            'norepay_principal' => $userAsset['js_norepay_principal'], // 金锁的代收本金
            'norepay_earnings' => $userAsset['js_norepay_earnings'], // 金锁的代收收益
            'total_earnings' => $userAsset['js_total_earnings'], // 金锁的累计收益
        );

        //$remain_principal = bcadd($remain_principal,$userAsset['dt_norepay_principal'],2);
        $total_interest = bcadd($total_interest, $userAsset['dt_repay_interest'], 2);
        $dt_remain = bcsub($userAsset['dt_norepay_principal'], $userAsset['dt_load_money'], 2);
        $duotou_user_asset = array(
            'dt_norepay_principal' => $userAsset['dt_norepay_principal'],
            'dt_repay_interest' => $userAsset['dt_repay_interest'],
            'dt_remain' => $dt_remain,
        );

        $user_asset = array(
            'corpus' => $remain_principal, // 待收本金
            'income' => $remain_interest, // 待收利息
            'earning_all' => bcadd($total_interest, $extra, 2), // 累计收益 = 总收益 + 贴息金额
        );

        //存管的资产
        $sv_user_asset = array(
            'norepay_principal' => $userAsset['cg_norepay_principal'], // 金锁的代收本金
            'norepay_earnings' => $userAsset['cg_norepay_earnings'], // 金锁的代收收益
            'total_earnings' => $userAsset['cg_total_earnings'], // 金锁的累计收益
        );

        $ret = array(
            'p2p_user_asset' => $p2p_user_asset,
            'duotou_user_asset' => $duotou_user_asset,
            'js_user_asset' => $js_user_asset,
            'user_asset' => $user_asset,
            'sv_user_asset' => $sv_user_asset,
        );

        return $ret;
    }

    /**
     * 获取用户的待还本金息
     * @param $user_id
     * @return array
     */
    public function getUserPendingAmount($user_id) {
        $deal_model = new DealModel();
        $dlr_model = new DealLoanRepayModel();

        if(app_conf('USER_ASSET_NEW') == '1') {
            $userAsset = \core\service\user\UserLoanRepayStatisticsService::getUserAssets($user_id);
            $principal = $deal_model->floorfix($userAsset['norepay_principal']);
            $interest = $deal_model->floorfix($userAsset['norepay_interest']);
        }else{
            $principal = $deal_model->floorfix($dlr_model->getSumByUserId($user_id, array(1, 8), 0));
            $interest = $deal_model->floorfix($dlr_model->getSumByUserId($user_id, array(2, 9), 0));
        }

        $deal_compound =  new DealCompoundService();
        $compound =  $deal_compound->getUserCompoundMoney($user_id, get_gmtime());
        $interest = bcadd($interest, $compound['interest'] ,2);

        //$cl_model = new CouponLogModel();
        //$coupon = $cl_model->getUserPendingRebate($user_id);

        // 代收本金增加智多鑫投资金额
        $principal = bcadd($principal, $userAsset['dt_norepay_principal'], 2);

        $result = array(
            'principal' => $principal,
            'interest' => $interest,
            'coupon' => 0//$coupon,
        );

        return $result;
    }

    /**
     * 同步账户资金
     */
    public function syncAccountMoney($userId, $accountType)
    {
        $accountId = self::getUserAccountId($userId, $accountType);
        if (empty($accountId)) {
            return false;
        }
        $superService = new SupervisionAccountService();
        $data = $superService->balanceSearch($accountId);
        if ($data['status'] != SupervisionEnum::RESPONSE_SUCCESS) {
            return false;
        }

        $balance = $data['data']['availableBalance'];
        $lockMoney = $data['data']['freezeBalance'];
        $res = AccountModel::instance()->setAccountMoney($accountId, $balance, $lockMoney);
        return $res;
    }
}
