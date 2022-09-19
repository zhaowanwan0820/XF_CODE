<?php
namespace core\dao\supervision;

use libs\common\WXException;
use NCFGroup\Common\Library\Idworker;
use libs\utils\Site;
use libs\utils\PaymentApi;
use core\dao\BaseModel;
use core\dao\deal\DealModel;
use core\dao\user\UserModel;
use core\dao\supervision\SupervisionTransferModel;
use core\service\supervision\SupervisionAccountService;
use core\service\user\UserService;
use core\service\account\AccountService;
use core\enum\SupervisionEnum;
use core\enum\AccountEnum;

/**
 * 存管账户划转记录
 * 涉及的业务:
 * admin/SupervisionAction/doTransfer 划转， 批量划转
 **/
class SupervisionBackendTransferModel extends BaseModel {

    // 复核状态
    const AUDIT_STATUS_NORMAL = 0; // 等待复核
    const AUDIT_STATUS_PASS = 1; // A角色通过
    const AUDIT_STATUS_REFUSE = 2; // A角色拒绝
    const AUDIT_STATUS_FINAL_PASS = 3; // B角色通过
    const AUDIT_STATUS_FINAL_REFUSE = 4; // B角色拒绝

    /**
     * 创建划转记录单
     * @param integer $accountId 账户ID
     * @param integer $amount 订单金额
     * @param string $outOrderId 外部订单号
     * @param integer $direction 划转方向
     * @return boolean
     */
    public function createOrder($accountId, $amount, $outOrderId, $direction = SupervisionTransferModel::DIRECTION_TO_SUPERVISION, $memo = '') {
        // 获取表名
        $tableName = $this->tableName();
        $sql = "SELECT COUNT(*) AS orderInfo FROM `{$tableName}` WHERE out_order_id = '{$outOrderId}'";
        $data = $this->db->getRow($sql);
        if (!empty($data['orderInfo'])) {
           return true;
        }

        try {
            $this->db->startTrans();
            if (empty($accountId) || empty($amount)) {
                throw new WXException('ERR_PARAM');
            }

            $insertData = [];
            $insertData['user_id'] = intval($accountId); // 账户ID
            $insertData['direction'] = intval($direction);
            $insertData['amount'] = intval($amount);
            $insertData['transfer_status'] = SupervisionTransferModel::TRANSFER_STATUS_NORMAL;
            $insertData['site_id'] = Site::getId();
            $insertData['out_order_id'] = $outOrderId;
            $insertData['create_time'] = time();
            $adminInfo = \es_session::get(md5(conf("AUTH_KEY")));
            $insertData['apply_user_name'] = $adminInfo['adm_name'];
            $insertData['audit_status'] = self::AUDIT_STATUS_NORMAL;
            $insertData['memo'] = $memo;
            $this->db->autoExecute($this->tableName(), $insertData, 'INSERT');
            $insertResult = $this->db->affected_rows() > 0 ? $insertData['out_order_id'] : false;

            $this->db->commit();
            return true;
         } catch (\Exception $e) {
            $this->db->rollback();
            PaymentApi::log('Supervision Transfer createOrder FAILED, code:'.$e->getCode().' message:'.$e->getMessage());
            return false;
         }
    }

    public function updateOrderById($id, $updateData, $condition = '') {
        if (empty($id) || empty($updateData)) {
            throw new WXException('ERR_PARAM');
        }
        if (empty($updateData['update_time'])) {
            $updateData['update_time'] = time();
        }
        if (!empty($condition)) {
            $condition = ' AND '.$condition;
        }
        $id = addslashes(trim($id));
        $record = $this->find($id);
        $this->db->autoExecute($this->tableName(), $updateData, 'UPDATE', " id = '{$id}' {$condition}");
        return $this->db->affected_rows() > 0 ? true : false;
    }

    public function updateOrderByOutId($outOrderId, $updateData, $condition = '') {
        if (empty($outOrderId) || empty($updateData)) {
            throw new WXException('ERR_PARAM');
        }
        if (empty($updateData['update_time'])) {
            $updateData['update_time'] = time();
        }
        if (!empty($condition)) {
            $condition = ' AND '.$condition;
        }
        $outOrderId = intval(trim($outOrderId));
        $this->db->autoExecute($this->tableName(), $updateData, 'UPDATE', " out_order_id = '{$outOrderId}' {$condition}");
        return $this->db->affected_rows() > 0 ? true : false;
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
        try {
            $orderInfo = $this->getTransferRecordByOutId($outOrderId);
            if ($orderInfo['transfer_status'] == $status) {
                return true;
            }
            $this->db->startTrans();
            if (empty($outOrderId) || empty($status) || empty($amount)) {
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
            $userLogMemo = '';
            if ($orderInfo['direction'] == SupervisionTransferModel::DIRECTION_TO_SUPERVISION) {
                $userLogMemo = SupervisionTransferModel::P2P_NAME.'划转至'.SupervisionTransferModel::SUPERVISION_NAME;
            } else if ($orderInfo['direction'] == SupervisionTransferModel::DIRECTION_TO_WX) {
                $userLogMemo = SupervisionTransferModel::SUPERVISION_NAME.'划转至'.SupervisionTransferModel::P2P_NAME;
            }

            // 划转逻辑处理
            $changeMoneyResult = false;
            if ($direction == SupervisionTransferModel::DIRECTION_TO_SUPERVISION) {
                // 划转成功
                if ($status == SupervisionTransferModel::TRANSFER_STATUS_SUCCESS) {
                    $userLogType .='成功';
                    $changeMoneyResult = AccountService::changeMoney($accountId, $actrualMoney, $userLogType, $userLogMemo, AccountEnum::MONEY_TYPE_LOCK_REDUCE);
                } else if ($status == SupervisionTransferModel::TRANSFER_STATUS_FAILURE) {
                    $userLogType .='失败';
                    $actrualMoney = -$actrualMoney;
                    $changeMoneyResult = AccountService::changeMoney($accountId, $actrualMoney, $userLogType, $userLogMemo, AccountEnum::MONEY_TYPE_LOCK);
                }
            } elseif ($direction == SupervisionTransferModel::DIRECTION_TO_WX) {
                // 划转成功
                if ($status == SupervisionTransferModel::TRANSFER_STATUS_SUCCESS) {
                    $userLogType .='成功';
                    $changeMoneyResult = AccountService::changeMoney($accountId, $actrualMoney, $userLogType, $userLogMemo, AccountEnum::MONEY_TYPE_INCR);
                } elseif ($status == SupervisionTransferModel::TRANSFER_STATUS_FAILURE) {
                    $userLogType .='失败';
                    // 只记录交易明细
                    $changeMoneyResult = AccountService::changeMoney($accountId, $actrualMoney, $userLogType, $userLogMemo, AccountEnum::MONEY_TYPE_INCR);
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
            return false;
        }
    }

    public function doAudit($id, $status) {
        try {
            $this->db->startTrans();
            if (!in_array($status, [SupervisionBackendTransferModel::AUDIT_STATUS_PASS,SupervisionBackendTransferModel::AUDIT_STATUS_REFUSE])) {
                throw new WXException('ERR_TRANSFER_STATUS_NOT_ALLOWED');
            }
            $adminInfo = \es_session::get(md5(conf("AUTH_KEY")));
            $toupdate = [
                'audit_status' => $status,
                'first_audit_admin_id' => $adminInfo['adm_id'],
                'first_audit_admin_name' => $adminInfo['adm_name'],
                'first_audit_time' =>  time(),
            ];
            $record = $this->find($id);
            if (empty($record)) {
               throw new WXException('ERR_TRANSFER_ORDER_NOT_EXSIT');
            }

            $updateResult = $this->updateOrderById($id, $toupdate, ' audit_status = 0 ');
            if (!$updateResult) {
                throw new WXException('ERR_TRANSFER_ORDER_UPDATE');
            }
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            PaymentApi::log('Supervision backendTransferModel doAudit FAILED, code:'.$e->getCode().', message:'.$e->getMessage());
            throw $e;
        }
    }

    public function doFinalAudit($id, $status, $checkAccount = false) {
        try {
            $this->db->startTrans();
            $allowStatus = [SupervisionBackendTransferModel::AUDIT_STATUS_FINAL_PASS,SupervisionBackendTransferModel::AUDIT_STATUS_FINAL_REFUSE];
            if (!in_array($status, $allowStatus)) {
                throw new WXException('ERR_TRANSFER_STATUS_NOT_ALLOWED');
            }
            $adminInfo = \es_session::get(md5(conf("AUTH_KEY")));
            $toupdate = [
                'audit_status' => $status,
                'final_audit_admin_id' => $adminInfo['adm_id'],
                'final_audit_admin_name' => $adminInfo['adm_name'],
                'final_audit_time' =>  time(),
            ];
            $record = $this->find($id);
            if (empty($record)) {
                throw new WXException('ERR_TRANSFER_ORDER_NOT_EXSIT');
            }

            //检查 存管余额是否足够
            if ($checkAccount) {
                $checkMoneyBeforeTransfer = $this->checkMoneyBeforeTransfer($record);
                if (!$checkMoneyBeforeTransfer) {
                        throw new WXException('ERR_BALANCE_NOT_ENOUGHT');
                }
            }
            $updateResult = $this->updateOrderById($id, $toupdate, 'audit_status NOT IN ('.implode(',', $allowStatus).')');
            if (!$updateResult) {
                throw new WXException('ERR_TRANSFER_ORDER_UPDATE');
            }
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            PaymentApi::log('Supervision backendTransferModel doAudit FAILED, code:'.$e->getCode().', message:'.$e->getMessage());
            throw $e;
        }
    }

    /**
     * 检查存管余额是否足够
     */
    public function checkMoneyBeforeTransfer($record) {
        try {
            //检查 存管余额是否足够
            $accountService = new SupervisionAccountService();
            $isSvUser = $accountService->isSupervisionUser($record['user_id']);
            if (!$isSvUser) {
                throw new WXException('ERR_NOT_OPEN_ACCOUNT');
            }
            if ($record['direction'] == SupervisionTransferModel::DIRECTION_TO_WX) {
                $supervisionUserInfo = $accountService->balanceSearch($record['user_id']);
                $amount = 0;
                if ($supervisionUserInfo['status'] == SupervisionEnum::RESPONSE_SUCCESS) {
                    $amount = $supervisionUserInfo['data']['availableBalance'];
                }
                if ($amount < $record['amount']) {
                    throw new WXException('ERR_BALANCE_NOT_ENOUGHT');
                }
            } else if ($record['direction'] == SupervisionTransferModel::DIRECTION_TO_SUPERVISION) {
                $actrualMoney = bcdiv($record['amount'], 100, 2);
                $userInfo = UserService::getUserById($record['user_id'], 'money');
                if (!empty($userInfo) && $userInfo['money'] >= $actrualMoney) {
                    throw new WXException('ERR_SUPERACCOUNT_MONEY_NOT_ENOUGH');
                }
            }
            return true;
       } catch (\Exception $e){
            return false;
       }
    }
}
