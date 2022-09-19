<?php
/**
 * ThirdBalanceModel.php
 * @date 2017-03-08
 * @author luzhengshuai <luzhengshuai@ucfgroup.com>
 **/
namespace core\dao\account;

use core\dao\BaseModel;
use core\enum\AccountEnum;
use core\enum\UserAccountEnum;
use libs\db\Db;
use libs\utils\Alarm;
use libs\utils\Logger;

class AccountModel extends BaseModel {

    /**
     * 获取账户信息
     * @param int $userId 用户ID
     * @param int $accountType 账户类型
     * @param int $platform 平台
     */
    public function getAccountInfo($userId, $accountType, $platform, $slave = true)
    {
        $condition = "user_id = '$userId' AND platform = '$platform' AND account_type = '$accountType'";
        return $this->findBy($condition, '*', [], $slave);
    }

    /**
     * 获取账户列表
     * @param int $userId 用户ID
     * @param int $platform 平台
     */
    public function getAccountList($userId, $platform)
    {
        $condition = "user_id = '$userId' AND platform = '$platform'";
        return $this->findAllViaSlave($condition);
    }

    /**
     * 新增账户，默认未开通
     * @return int 账户ID
     */
    public function addAccount($userId, $accountType, $platform, $autoIncr = true) {
        $data = array(
            'user_id'       => (int) $userId,
            'account_type'  => (int) $accountType,
            'platform'      => (int) $platform,
            'create_time'   => time(),
            'update_time'   => time(),
        );
        if (!$autoIncr) {
            $data['id'] = (int) $userId;
        }
        $result = $this->db->autoExecute($this->tableName(), $data, "INSERT");

        if ($result) {
            return $this->db->insert_id();
        }else{
            return false;
        }
    }

    /**
     * 开通账户
     */
    public function openAccount($accountId) {
        $condition = sprintf("`id` = '%d' AND `status` in (%s)", $accountId, implode(',', [AccountEnum::STATUS_DEFAULT, AccountEnum::STATUS_UNACTIVATED]));
        $params = array(
            'status'        => AccountEnum::STATUS_OPENED,
            'open_time'     => time(),
            'update_time'   => time(),
        );
        return $this->updateBy($params, $condition);
    }

    /**
     * 设置未激活
     */
    public function setUnactivated($accountId) {
        $condition = sprintf("`id` = '%d' AND `status` = %d", $accountId, AccountEnum::STATUS_DEFAULT);
        $params = array(
            'status'        => AccountEnum::STATUS_UNACTIVATED,
            'update_time'   => time(),
        );
        return $this->updateBy($params, $condition);
    }

    /**
     * 账户余额更新,所有的金额都不能扣负
     * @param integer $accountId 账户ID
     * @param float $money 变动金额 单位元
     * @param integer $moneyType 资金类型
     * @access public
     * @return void
     */
    public function updateAccountMoney($accountId, $money, $moneyType) {

        if (bccomp($money, 0, 2) == 0) {
            Logger::info("账户余额更新|金额变动为0不处理 accountId:$accountId, money:$money, moneyType:$moneyType");
            return true;
        }
        $amount = bcmul($money, 100); //转成分

        $sql = 'UPDATE ' . $this->tableName() . ' SET';

        $where = ' WHERE id = "' .$accountId. '"';
        switch($moneyType) {
        case AccountEnum::MONEY_TYPE_INCR:
            $sql .= ' money = money + ' . $amount;
            break;
        case AccountEnum::MONEY_TYPE_REDUCE:
            $sql .= ' money = money - ' . $amount;
            $where .= ' AND money >= ' . $amount;
            break;
        case AccountEnum::MONEY_TYPE_LOCK:
            $sql .= ' money = money - ' . $amount . ', lock_money = lock_money + ' . $amount;
            $where .= ' AND money >= ' . $amount;
            break;
        case AccountEnum::MONEY_TYPE_UNLOCK:
            $sql .= ' money = money + ' . $amount . ', lock_money = lock_money - ' . $amount;
            $where .= ' AND lock_money >= ' . $amount;
            break;
        case AccountEnum::MONEY_TYPE_LOCK_INCR:
            $sql .= ' lock_money = lock_money + ' . $amount;
            break;
        case AccountEnum::MONEY_TYPE_LOCK_REDUCE:
            $sql .= ' lock_money = lock_money - ' . $amount;
            $where .= ' AND lock_money >= ' . $amount;
            break;
        default:
            //TODO LOG
            Logger::info("账户余额更新|无需处理的资金类型 accountId:$accountId, money:$money, moneyType:$moneyType");
            return true;
            break;
        }

        $sql .= ', update_time = ' . time() . $where;

        $updateRes = $this->db->query($sql);
        if (!$updateRes || $this->db->affected_rows() == 0) {
            Alarm::push('account', __METHOD__, sprintf("账户余额更新失败 accountId:$accountId, money:$money, moneyType:$moneyType"));
            throw new \Exception("账户余额更新失败 accountId:$accountId, money:$money, moneyType:$moneyType");
        }

        Logger::info("账户余额更新|更新成功 accountId:$accountId, money:$money, moneyType:$moneyType");
        return true;
    }

    /**
     * 通过id集合获取账户信息
     */
    public function getInfoByIds($ids) {
        $condition = sprintf('id in (%s)', implode(',', $ids));
        return $this->findAllViaSlave($condition);
    }

    /**
     * 通过userId集合获取账户列表
     */
    public function getListByUserIds($userIds) {
        $condition = sprintf('user_id in (%s) and platform = %d', implode(',', $userIds), UserAccountEnum::PLATFORM_SUPERVISION);
        return $this->findAllViaSlave($condition);
    }

    /**
     * 通过条件获取信息
     */
    public function getInfoByCondition($condition) {
        return $this->findAllViaSlave($condition);
    }

    /**
     * 设置账户资金
     */
    public function setAccountMoney($accountId, $money, $lockMoney) {
        $condition = sprintf("`id` = '%d'", $accountId);
        $params = array(
            'money'         => $money,
            'lock_money'    => $lockMoney,
            'update_time'   => time(),
        );
        return $this->updateBy($params, $condition);
    }

    /**
     * 通过范围获取用户金额
     * @params float $minMoney  单位元
     * @params float $maxMoney  单位元
     */
    public function getBalanceByRange($minId, $maxId, $minMoney = 0, $maxMoney = 0, $accountType = UserAccountEnum::ACCOUNT_INVESTMENT) {
        $condition = sprintf(' user_id >= %d and user_id <= %d and account_type = %d and platform = %d ', $minId, $maxId, $accountType, UserAccountEnum::PLATFORM_SUPERVISION);
        if (bccomp($minMoney, 0, 2) === 1) {
            $condition .= sprintf(' and money >= %d ', bcmul($minMoney, 100));
        }
        if (bccomp($maxMoney, 0, 2) === 1) {
            $condition .= sprintf(' and money <= %d ', bcmul($maxMoney, 100));
        }
        return $this->findAll($condition, true, 'user_id, round(money/100, 2) as money');
    }
    /**
     * 获取最大的账户Id
     */
    public function getMaxAccountId() {
        $ret = $this->findByViaSlave('1', 'max(`id`) AS `maxid`');
        return intval($ret['maxid']);
    }

    /**
     * 获取指定区间+状态的账户列表
     */
    public function getAccountListByRange($accountId, $stepCount, $status, $fields = 'id,user_id,money,lock_money') {
        return $this->findAllViaSlave(sprintf('`id` >= %d AND `id` < %d AND `status` = %d', $accountId, ($accountId+$stepCount), $status), true, $fields);
    }

    /**
     * 获取指定账户ID的列表
     */
    public function getAccountListByIds($accountIds, $fields = 'id,user_id,money,lock_money') {
        if (empty($accountIds)) {
            return [];
        }
        $condition = '`id` IN (' .join(',', $accountIds). ')';
        return $this->findAllViaSlave($condition, true, $fields);
    }

    /**
     * 获取指定账户总余额、总冻结
     * @params int $userId 用户ID
     * @params int $platform 账户平台标识
     */
    public function getTotalMoneyStatis($userId = 0, $platform = UserAccountEnum::PLATFORM_SUPERVISION) {
        $condition = sprintf('`platform` = \'%d\'', $platform);
        if (!empty($userId)) {
            $condition .= sprintf(' AND `user_id` = \'%d\'', $userId);
        }
        return $this->findByViaSlave($condition, 'SUM(`money`) AS totalMoney, SUM(`lock_money`) AS totalLockMoney, SUM(`money`+`lock_money`) AS totalAccount');
    }

    /**
     * 获取余额、冻结为负的列表
     * @params int $platform 账户平台标识
     */
    public function getMinusMoneyList($platform = UserAccountEnum::PLATFORM_SUPERVISION) {
        $condition = sprintf('`platform` = \'%d\' AND (`money` < 0 OR `lock_money` < 0)', $platform);
        return $this->findAllViaSlave($condition, true, '`id`,`user_id`,`money`,`lock_money`');
    }

    /**
     * 获取白名单账户信息
     * @param int $userId 用户ID
     * @param int $accountType 账户类型
     * @param int $platform 平台
     */
    public function getWhitelistAccountInfo($userId, $platform = UserAccountEnum::PLATFORM_SUPERVISION)
    {
        if (empty($userId)) {
            return [];
        }
        $condition = "user_id = '$userId' AND platform = '$platform'";
        $result = $this->findBy($condition, '*', []);
        return !empty($result) ? $result->getRow() : [];

    }

}
