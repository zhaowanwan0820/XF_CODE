<?php
namespace core\dao\supervision;
use NCFGroup\Common\Library\Idworker;
use libs\common\WXException;
use libs\utils\Site;
use libs\utils\PaymentApi;
use core\enum\AccountEnum;
use core\service\user\UserService;
use core\service\account\AccountService;
use core\dao\BaseModel;
use core\dao\deal\DealModel;
#use core\exception\UserThirdBalanceException;

/**
 * 存管账户划转记录
 * 涉及的业务:
 * SupervisionFinanceService/superRecharge 网信理财账户划转到存管账户
 * SupervisionFinanceService/superRechargeNotify 网信理财账户划转到存管账户 回调
 * SupervisionFinanceService/superWithdrawSecret 存管账户验密划转到网信理财账户
 * SupervisionFinanceService/accountSuperWithdraw 存管账户划转到网信理财账户
 * SupervisionFinanceService/accountSuperWithdrawNotify 存管账户划转到网信理财账户 回调
 **/
class SupervisionTransferModel extends BaseModel {

    // 定义资金记录使用的系统账户名称
    const P2P_NAME = '网信理财账户余额';
    const SUPERVISION_NAME = '网贷P2P账户余额';

    // 划转状态
    const TRANSFER_STATUS_NORMAL = 0; // 未处理
    const TRANSFER_STATUS_SUCCESS = 1; // 成功
    const TRANSFER_STATUS_FAILURE = 2; // 失败

    // 划转方向
    const DIRECTION_TO_SUPERVISION = 1; // 网信理财账户划转到存管账户
    const DIRECTION_TO_WX = 2; // 存管账户划转到网信理财账户

    /**
     * 创建划转记录单
     * @param integer $accountId 账户ID
     * @param integer $accountType 账户类型
     * @param integer $amount 订单金额
     * @param string  $outOrderId 外部订单号
     * @param integer $direction 划转方向
     * @return boolean
     */
    public function createOrder($accountId, $amount, $outOrderId, $direction = self::DIRECTION_TO_SUPERVISION, $needChangeMoney = true) {
        try {
            // 获取表名
            $tableName = $this->tableName();
            $sql = "SELECT COUNT(*) AS orderInfo FROM `{$tableName}` WHERE out_order_id = '{$outOrderId}'";
            $data = $this->db->getRow($sql);
            if (!empty($data['orderInfo'])) {
               return true;
            }

            $this->db->startTrans();
            if (empty($accountId) || empty($amount)) {
                throw new WXException('ERR_PARAM');
            }

            $insertData = [];
            $insertData['user_id'] = intval($accountId); // 账户ID
            $insertData['direction'] = intval($direction);
            $insertData['amount'] = intval($amount);
            $insertData['transfer_status'] = self::TRANSFER_STATUS_NORMAL;
            $insertData['site_id'] = Site::getId();
            $insertData['out_order_id'] = $outOrderId;
            $insertData['create_time'] = time();
            try {
                $insertResult = $this->db->autoExecute($tableName, $insertData, 'INSERT');
            } catch (\Exception $e) {
                if ($e->getCode() == '1062') {
                    return true;
                }
            }

            $userInfo = UserService::getUserById($userId);
            if (!empty($userInfo) && $needChangeMoney) {
                // human readable amount
                $actrualMoney = bcdiv($amount, 100, 2);
                $userLogMemo = '';
                $moneyChangeType = 0;
                $negative = 0;
                $moneyChangeType = AccountEnum::MONEY_TYPE_LOCK;
                // 网信理财账户划转到存管账户
                if ($direction == self::DIRECTION_TO_SUPERVISION) {
                    // 唯一订单号
                    $userLogMemo = self::P2P_NAME.'划转到'.self::SUPERVISION_NAME;
                    $changeMoneyResult = AccountService::changeMoney($accountId, $actrualMoney, '余额划转申请', $userLogMemo, $moneyChangeType, $negative);
                    if (!$changeMoneyResult) {
                        throw new WXException('ERR_TRANSFER_ORDER_FAILED');
                    }
                }
            }
            $this->db->commit();
            return true;
         } catch (\Exception $e) {
            $this->db->rollback();
            PaymentApi::log('Supervision Transfer createOrder FAILED, code:'.$e->getCode().' message:'.$e->getMessage());
            return false;
         }
    }

    public function updateOrderById($id, $updateData) {
        if (empty($id) || empty($updateData)) {
            throw new WXException('ERR_PARAM');
        }
        if (empty($updateData['update_time'])) {
            $updateData['update_time'] = time();
        }
        $id = addslashes(trim($id));
        $this->db->autoExecute($this->tableName(), $updateData, 'UPDATE', " id = '{$id}' AND transfer_status = ".self::TRANSFER_STATUS_NORMAL);
        $affRows = $this->db->affected_rows();
        if ($affRows == 0) {
            return false;
        }
        return true;
    }

    public function updateOrderByOutId($outOrderId, $updateData) {
        if (empty($outOrderId) || empty($updateData)) {
            throw new WXException('ERR_PARAM');
        }
        if (empty($updateData['update_time'])) {
            $updateData['update_time'] = time();
        }
        $outOrderId = intval(trim($outOrderId));
        $svBackendTransferModel = new SupervisionBackendTransferModel();
        $svBackendTransferModel->db->autoExecute($svBackendTransferModel->tableName(), $updateData, 'UPDATE', " out_order_id = '{$outOrderId}'");
        $this->db->autoExecute($this->tableName(), $updateData, 'UPDATE', " out_order_id = '{$outOrderId}' AND transfer_status = ".self::TRANSFER_STATUS_NORMAL);
        $affRows = $this->db->affected_rows();
        if ($affRows == 0) {
            return false;
        }
        return true;
    }

    public function getTransferRecordByOutId($outOrderId) {
        if (empty($outOrderId)) {
            return false;
        }
        // 获取表名
        $tableName = $this->tableName();
        $outOrderId = intval(trim($outOrderId));
        $res = $this->db->getRow("SELECT * FROM `{$tableName}` WHERE out_order_id = '{$outOrderId}'");
        return $res;
    }

    /**
     * 余额划转
     * @param integer $outOrderId 外部订单号
     * @param integer $status 支付状态
     * @param integer $amount 支付金额
     * @param string $userLogType 用户资金记录类型
     * @return boolean $dealUserMoney 是否处理用户资金
     * @return boolean
     */
    public function orderProcess($outOrderId, $status, $amount, $userLogType = '余额划转', $direction = SupervisionTransferModel::DIRECTION_TO_SUPERVISION) {
        //以后没有余额划转
        return true;
        try {
            $orderInfo = $this->getTransferRecordByOutId($outOrderId);
            if ($orderInfo['transfer_status'] == $status) {
                return true;
            }
            $this->db->startTrans();
            if (empty($outOrderId) || empty($amount)) {
                throw new WXException('ERR_PARAM');
            }
            if (empty($orderInfo)) {
                throw new WXException('ERR_TRANSFER_ORDER_NOT_EXSIT');
            }

            $updateData = [];
            $updateData['update_time'] = time();
            $updateData['transfer_status'] = $status;

            // 账户ID
            $accountId = $orderInfo['user_id'];
            // 把账户ID转换为用户ID
            $userId = AccountService::getUserId($accountId);
            $userInfo = UserService::getUserById($userId);
            if (empty($userInfo)) {
                throw new WXException('ERR_USER_NOEXIST');
            }

            // human readable amount
            $actrualMoney = bcdiv($amount, 100, 2);
            // 划转逻辑处理
            $changeMoneyResult = false;
            // 网信理财账户划转到存管账户
            if ($orderInfo['direction'] == self::DIRECTION_TO_SUPERVISION) {
                $userLogMemo = self::P2P_NAME.'划转至'.self::SUPERVISION_NAME;
                // 划转成功
                if ($status == self::TRANSFER_STATUS_SUCCESS) {
                    // TODO 给存管账户加余额 资产中心
                    $userLogType = sprintf('网贷%s成功', $userLogType);
                    $changeSupervisionMoneyResult = AccountService::changeMoney($accountId, $actrualMoney, $userLogType, $userLogMemo, AccountEnum::MONEY_TYPE_INCR);
//                    UserThirdBalanceModel::instance()->updateUserSupervisionMoney($orderInfo['user_id'], $actrualMoney, UserModel::MONEY_TYPE_INCR);
                } else if ($status == self::TRANSFER_STATUS_FAILURE) {
                    // 划转失败， 网信理财记录失败记录，扣减冻结，返还可用
                    $userLogType = sprintf('%s失败', $userLogType);
                    $changeMoneyResult = AccountService::changeMoney($accountId, $actrualMoney, $userLogType, $userLogMemo, AccountEnum::MONEY_TYPE_LOCK);
                }
            // 存管账户划转到网信理财账户
            } elseif ($orderInfo['direction'] == self::DIRECTION_TO_WX) {
                $userLogMemo = self::SUPERVISION_NAME.'划转至'.self::P2P_NAME;
                // 划转成功
                if ($status == self::TRANSFER_STATUS_SUCCESS) {
                    // 给存管账户减余额 资产中心
                    $userLogType = sprintf('网贷%s成功', $userLogType);
                    $changeSupervisionMoneyResult = AccountService::changeMoney($accountId, $actrualMoney, $userLogType, $userLogMemo, AccountEnum::MONEY_TYPE_INCR);
//                    UserThirdBalanceModel::instance()->updateUserSupervisionMoney($orderInfo['user_id'], -$actrualMoney, UserModel::MONEY_TYPE_INCR);
                } elseif ($status == self::TRANSFER_STATUS_FAILURE) {
                    //$userDao->changeMoneyDealType = DealModel::DEAL_TYPE_SUPERVISION;
                 //   $userLogType .='失败';
                 //   $changeMoneyResult = $userDao->changeMoney(-$actrualMoney, $userLogType, $userLogMemo, 0, 0, UserModel::MONEY_TYPE_INCR);
                     $changeMoneyResult = true;
                }
            }

            if (!$changeMoneyResult) {
                throw new WXException('ERR_TRANSFER_ORDER_FAILED');
            }

            // 更新订单状态
            $updateResult = $this->updateOrderByOutId($outOrderId, $updateData);
            if (!$updateResult) {
                throw new WXException('ERR_TRANSFER_ORDER_FAILED');
            }
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            PaymentApi::log('Supervision Transfer FAILED, code:'.$e->getCode().' message:'.$e->getMessage());
            if ($e instanceof UserThirdBalanceException) {
                throw $e;
            }
            return false;
        }
        return false;
    }
}
