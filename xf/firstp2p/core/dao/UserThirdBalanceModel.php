<?php
/**
 * ThirdBalanceModel.php
 * @date 2017-03-08
 * @author luzhengshuai <luzhengshuai@ucfgroup.com>
 **/

namespace core\dao;

use core\dao\UserModel;
use core\dao\UserLogModel;
use libs\utils\Logger;
use core\exception\UserThirdBalanceException;
use core\service\ncfph\AccountService as PhAccountService;

class UserThirdBalanceModel extends BaseModel {

    const BALANCE_SUPERVISION = 'supervision';

    public static $balanceTypeDesc = [
        self::BALANCE_SUPERVISION => '存管',
    ];

    // 第三方余额汇总
    public static $balanceEnum = [
        // 存管余额
        self::BALANCE_SUPERVISION => [
            'supervisionBalance' => [
                'field' => 'money',
                'desc' => '余额'
            ],
            'supervisionLockMoney' =>[
                'field' => 'lockMoney',
                'desc' => '冻结'
            ]
        ],
    ];

    /**
     * initBalance
     * 初始化用户第三方余额
     *
     * @param mixed $userId
     * @access public
     * @return void
     */
    public function initBalance($userId, $accountType) {
        return true;
        $this->user_id = $userId;
        $this->create_time = time();
        $this->account_type = $accountType;
        if (!$this->save()) {
            throw new \Exception('用户第三方余额初始化失败');
        }

        return true;
    }

    /**
     * updateUserSupervisionMoney
     * 存管余额更新,所有的金额都不能扣负
     *
     * @param integer $userId 用户ID
     * @param float $money 变动金额
     * @param integer $moneyType 资金类型
     * @access public
     * @return void
     */
    public function updateUserSupervisionMoney($userId, $money, $moneyType, $negative = true) {

        if (bccomp($money, 0, 2) == 0) {
            Logger::info("存管余额更新|金额变动为0不处理 userId:$userId, money:$money, moneyType:$moneyType");
            return true;
        }

        $sql = 'UPDATE ' . $this->tableName() . ' SET';

        $where = ' WHERE user_id = "' .$userId. '"';
        switch($moneyType) {
        case UserModel::TYPE_MONEY:
            $sql .= ' supervision_balance = supervision_balance +' . $money;
            break;
        case UserModel::TYPE_LOCK_MONEY:
            $sql .= ' supervision_balance = supervision_balance -' . $money . ', supervision_lock_money = supervision_lock_money + ' . $money;
            break;
        case UserModel::TYPE_DEDUCT_LOCK_MONEY:
            $sql .= ' supervision_lock_money = supervision_lock_money - ' . $money;
            break;
        default:
            //TODO LOG
            Logger::info("存管余额更新|无需处理的资金类型 userId:$userId, money:$money, moneyType:$moneyType");
            return true;
            break;
        }

        $sql .= ' ,update_time = ' . time() . $where;

        $updateRes = $this->db->query($sql);
        if (!$updateRes || $this->db->affected_rows() == 0) {
            throw new UserThirdBalanceException("存管余额更新失败 userId:$userId, money:$money, moneyType:$moneyType");
        }
        // 用户余额操作判断
        $isMinusMoney = $this->db->getOne("SELECT COUNT(*) FROM firstp2p_user_third_balance WHERE user_id = '{$userId}' AND supervision_balance < 0");
        if ($isMinusMoney) {
            \libs\payment\supervision\SupervisionChecker::registerCheck($userId);
        }

        Logger::info("存管余额更新|更新成功 userId:$userId, money:$money, moneyType:$moneyType");
        return true;
    }

    /**
     * getUserThirdBalance
     * 获取用户存管金额
     *
     * @param integer $userId
     * @param boolean $slave
     * @access public
     * @return array
     */
    public function getUserThirdBalance($userId, $slave = false) {

        $phAccountSrv = new PhAccountService();
        $userInfo = UserModel::instance()->find($userId, 'user_purpose');
        $accountType = $userInfo['user_purpose'];

        $accountInfo = $phAccountSrv->getInfoByUserIdAndType($userId, $accountType);

        $balance = [];
        foreach (self::$balanceEnum as $balanceType => $fields) {
            $totalKey = $balanceType . 'Money';
            $balance[$balanceType][$totalKey] = 0;
            foreach ($fields as $key => $attr) {
                $balance[$balanceType][$key] = $accountInfo[$attr['field']];
                $balance[$balanceType][$totalKey] = bcadd($balance[$balanceType][$totalKey], $accountInfo[$attr['field']], 2);
            }
        }
        return $balance;
    }

    // UserModel中实时获取用户余额
    public function getUserSupervisionMoney($userId) {
        $balance = $this->getUserThirdBalance($userId);
        return $balance[self::BALANCE_SUPERVISION];
    }

    public function setUserSupervisionBalance($userId, $balance, $lockMoney) {
        $condition = ' user_id = ' . intval($userId);
        $data = [
            'supervision_balance' => $balance,
            'supervision_lock_money' => $lockMoney
        ];
        return $this->updateBy($data, $condition);
    }

    public function getAccountInfo($userId, $platform, $accountType)
    {
        $phAccountSrv = new PhAccountService();
        return $phAccountSrv->getInfoByUserIdAndType($userId, $accountType);
    }

    public function getAccountList($userId)
    {
        $phAccountSrv = new PhAccountService();
        return $phAccountSrv->getListByUserId($userId);
    }

}
