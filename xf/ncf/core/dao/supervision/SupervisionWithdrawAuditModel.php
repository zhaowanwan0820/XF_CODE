<?php
namespace core\dao\supervision;

use NCFGroup\Common\Library\GTM\GlobalTransactionManager;
use NCFGroup\Common\Library\GTM\Toolkit\EventMaker;
use libs\common\WXException;
use libs\utils\PaymentApi;
use libs\utils\Site;
use core\dao\BaseModel;
use core\enum\DealExtEnum;
use core\enum\SupervisionEnum;
use core\tmevent\supervision\SupervisionApiExecuteEvent;
use core\service\account\AccountService;
use core\service\supervision\SupervisionAccountService;

/**
 * 存管账户提现审核记录（放款类型：先收费后放款）
 **/
class SupervisionWithdrawAuditModel extends BaseModel {
    /**
     * 创建存管提现审核记录
     * @param string $function 方法名
     * @param array $params 参数列表
     * @return boolean
     */
    public function createOrder($function, $params) {
        // 成功的数据格式
        $result = array('status' => SupervisionEnum::RESPONSE_SUCCESS, 'respCode' => SupervisionEnum::RESPONSE_CODE_SUCCESS, 'respMsg' => '');
        try {
            $this->db->startTrans();
            if (empty($function) || empty($params['orderId']) || empty($params['userId']) || empty($params['amount'])) {
                throw new WXException('ERR_PARAM');
            }
            $insertData = [];
            $insertData['function'] = trim($function);
            $insertData['params'] = json_encode($params);
            $insertData['order_id'] = intval($params['orderId']);
            $insertData['user_id'] = intval($params['userId']);
            $insertData['amount'] = intval($params['amount']);
            $insertData['type'] = isset($params['type']) ? intval($params['type']) : DealExtEnum::LOAN_TYPE_LATER_LOAN;
            $insertData['status'] = SupervisionEnum::STATUS_NOT_AUDIT; // 待审核
            $insertData['create_time'] = time();
            $insertData['site_id'] = Site::getId();
            if (!empty($params['bidId'])) {
                $insertData['bid'] = intval($params['bidId']);
            }
            try {
                $tableName = $this->tableName();
                $this->db->insert($tableName, $insertData);
            } catch(\Exception $e) {
                if ($e->getCode() != '1062') {
                    throw new WXException('ERR_WITHDRAW_AUDIT_CREATE_FAILED');
                }
            }
            $this->db->commit();
        } catch(\Exception $e) {
            $result = array(
                'status' => SupervisionEnum::RESPONSE_FAILURE,
                'respCode' => SupervisionEnum::RESPONSE_CODE_FAILURE,
                'respMsg' => $e->getMessage(),
            );
            $this->db->rollback();
            PaymentApi::log('SupervisionWithdrawAudit_createOrder FAILED, code:'.$e->getCode().', message:'.$e->getMessage());
        }
        return $result;
    }

    public function updateOrderById($id, $updateData, $condition = '') {
        if (empty($id) || empty($updateData)) {
            throw new WXException('ERR_PARAM');
        }
        if (empty($updateData['update_time'])) {
            $updateData['update_time'] = time();
        }
        if (!empty($condition)) {
            $condition = ' AND ' . $condition;
        }
        $id = addslashes(trim($id));
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
        if ($condition != '') {
            $condition = ' AND ' . $condition;
        }
        $where = sprintf(" `order_id` = '%d'%s", $outOrderId, $condition);
        $this->db->autoExecute($this->tableName(), $updateData, 'UPDATE', $where);
        return $this->db->affected_rows() > 0 ? true : false;
    }

    public function getWithdrawRecordByOutId($outOrderId) {
        if (empty($outOrderId)) {
            return false;
        }
        // 获取表名
        $tableName = $this->tableName();
        $outOrderId = intval(trim($outOrderId));
        return $this->db->getRow("SELECT * FROM `{$tableName}` WHERE `order_id` = '{$outOrderId}'");
    }

    /**
     * 通过用户ID、标的ID查询提现审核记录
     * @param int $userId
     * @param int $bid
     * @return array
     */
    public function getWithdrawRecordByBid($userId, $bid) {
        if (empty($userId) || empty($bid)) {
            return false;
        }
        // 获取表名
        $tableName = $this->tableName();
        return $this->db->getRow(sprintf("SELECT `id`,`order_id`,`user_id`,`amount`,`status`,`type`,`bid` FROM `{$tableName}` WHERE `user_id`='%d' AND `bid`='%d'", (int)$userId, (int)$bid));
    }

    /**
     * A角色通过
     * @param int $id
     * @param int $status
     * @throws WXException
     * @throws Exception
     * @return boolean
     */
    public function doFirstAudit($id, $status, $checkAccount = false) {
        $result = ['respCode'=>0, 'respMsg' => ''];
        try {
            $this->db->startTrans();
            if (!in_array($status, [SupervisionEnum::STATUS_A_PASS])) {
                throw new WXException('ERR_TRANSFER_STATUS_NOT_ALLOWED');
            }

            // 获取提现审核记录
            $record = $this->find($id);
            if (empty($record)) {
                throw new WXException('ERR_INVEST_NO_EXIST');
            }
            // A角色已经审核通过
            if ($record['status'] == SupervisionEnum::STATUS_A_PASS) {
                return $result;
            }

            // 检查 存管余额是否足够
            if ($checkAccount) {
                $checkMoney = $this->checkMoneyBeforeWithdraw($record);
                if (!$checkMoney) {
                    throw new WXException('ERR_SV_MONEY_NOT_ENOUGH');
                }
            }

            $adminInfo = \es_session::get(md5(conf("AUTH_KEY")));
            $toupdate = [
                'status' => $status,
                'first_audit_admin_id' => $adminInfo['adm_id'],
                'first_audit_admin_name' => $adminInfo['adm_name'],
                'first_audit_time' =>  time(),
            ];
            $condition = sprintf('status IN (%s)', implode(',', [SupervisionEnum::STATUS_NOT_AUDIT, SupervisionEnum::STATUS_B_REFUND]));
            $updateResult = $this->updateOrderById($id, $toupdate, $condition);
            if (!$updateResult) {
                throw new WXException('ERR_WITHDRAW_AUDIT_FAILED');
            }
            $this->db->commit();
            return $result;
        } catch (\Exception $e) {
            $this->db->rollback();
            PaymentApi::log('SupervisionWithdrawAudit_doFirstAudit FAILED, id:' . $id . ', status:' . $status . ', code:'.$e->getCode().', message:'.$e->getMessage());
            $result['respCode'] = -1;
            $result['respMsg'] = $e->getMessage();
            return $result;
        }
    }

    /**
     * B角色审核
     * @param int $id
     * @param int $status
     * @param string $checkAccount
     * @throws WXException
     * @throws Exception
     * @return boolean
     */
    public function doFinalAudit($id, $status, $checkAccount = false) {
        $result = ['respCode'=>0, 'respMsg' => ''];
        try {
            $allowStatus = [SupervisionEnum::STATUS_B_PASS, SupervisionEnum::STATUS_B_REFUND];
            if (!in_array($status, $allowStatus)) {
                throw new WXException('ERR_TRANSFER_STATUS_NOT_ALLOWED');
            }

            // 获取提现审核记录
            $record = $this->find($id);
            if (empty($record)) {
                throw new WXException('ERR_INVEST_NO_EXIST');
            }
            // B角色已经审核完毕
            if (in_array($record['status'], $allowStatus)) {
                return $result;
            }

            // B角色审批拒绝
            if ($status == SupervisionEnum::STATUS_B_REFUND) {
                $adminInfo = \es_session::get(md5(conf("AUTH_KEY")));
                $toupdate = [
                    'status' => $status,
                    'final_audit_admin_id' => $adminInfo['adm_id'],
                    'final_audit_admin_name' => $adminInfo['adm_name'],
                    'final_audit_time' =>  time(),
                ];
                $updateResult = $this->updateOrderById($id, $toupdate, sprintf('status = %d', SupervisionEnum::STATUS_A_PASS));
                if (!$updateResult) {
                    throw new WXException('ERR_WITHDRAW_AUDIT_FAILED');
                }
                PaymentApi::log('SupervisionWithdrawAudit_doFinalAudit_SUCCESS, id:' . $id . ', status:' . $status);
                return $result;
            }

            // 检查 存管余额是否足够
            if ($checkAccount) {
                $checkMoney = $this->checkMoneyBeforeWithdraw($record);
                if (!$checkMoney) {
                    throw new WXException('ERR_SV_MONEY_NOT_ENOUGH');
                }
            }

            // 方法名校验
            $functionList = explode('::', $record['function']);
            if (count($functionList) != 2) {
                throw new WXException('ERR_PARAM_LOSE');
            }
            // 参数校验
            $apiParams = json_decode($record['params'], true);
            if (empty($apiParams)) {
                throw new WXException('ERR_PARAM_LOSE');
            }

            $gtm = new GlobalTransactionManager();
            $gtm->setName('SvAdminWithdrawAudit');

            // 请求存管Api接口
            $gtm->addEvent(new SupervisionApiExecuteEvent((new $functionList[0]()), $functionList[1], $apiParams));

            // 更新审核记录
            $adminInfo = \es_session::get(md5(conf("AUTH_KEY")));
            $toupdate = [
                'status' => $status,
                'final_audit_admin_id' => $adminInfo['adm_id'],
                'final_audit_admin_name' => $adminInfo['adm_name'],
                'final_audit_time' =>  time(),
            ];
            $auditUpdateEvent = new EventMaker([
                'commit' => [(new self()), 'updateOrderById', [$id, $toupdate, sprintf('status = %d', SupervisionEnum::STATUS_A_PASS)]],
            ]);
            $gtm->addEvent($auditUpdateEvent);

            $auditRet = $gtm->execute();
            if (true !== $auditRet) {
                throw new \Exception($gtm->getError());
            }

            PaymentApi::log('SupervisionWithdrawAudit_doFinalAudit_SUCCESS, id:' . $id . ', status:' . $status . ', auditRet:' . json_encode($auditRet));
        } catch (\Exception $e) {
            PaymentApi::log('SupervisionWithdrawAudit_doFinalAudit_FAILED, id:' . $id . ', status:' . $status . ', code:'.$e->getCode().', message:'.$e->getMessage());
            $result['respCode'] = -1;
            $result['respMsg'] = $e->getMessage();
        }
        return $result;
    }

    /**
     * 检查存管余额是否足够
     */
    public function checkMoneyBeforeWithdraw($record) {
        try {
            //检查 存管余额是否足够
            $accountService = new SupervisionAccountService();
            $isSvUser = $accountService->isSupervisionUser($record['user_id']);
            if (!$isSvUser) {
                throw new WXException('ERR_NOT_OPEN_ACCOUNT');
            }

            // 获取账户余额，单位元
            $userAccountInfo = AccountService::getAccountMoneyById($record['user_id']);
            $userAccountAmountCent = bcmul($userAccountInfo['money'], 100, 0);
            if ($userAccountAmountCent < $record['amount']) {
                throw new WXException('ERR_SV_MONEY_NOT_ENOUGH');
            }

            PaymentApi::log(sprintf('SupervisionWithdrawAudit_checkMoneyBeforeWithdraw_SUCCESS, accountId:%d, withdrawAmount:%d, svAmount:%d', $record['user_id'], $record['amount'], $userAccountInfo['money']));
            return true;
        } catch (\Exception $e) {
            PaymentApi::log(sprintf('SupervisionWithdrawAudit_checkMoneyBeforeWithdraw_FAILED, accountId:%d, withdrawAmount:%d, svAmount:%d, code:%s, message:%s', $record['user_id'], $record['amount'], $userAccountInfo['money'], $e->getCode(), $e->getMessage()));
            return false;
        }
    }

    /**
     * 标的还款提前结清时的方法
     * @param int $userId
     * @param int $bid
     * @throws Exception
     * @return boolean
     */
    public function repayWithdrawAudit($userId, $bid) {
        $result = ['respCode'=>0, 'respMsg' => ''];
        try {
            $id = $orderId = 0;
            if (empty($userId) || empty($bid)) {
                throw new WXException('ERR_PARAM');
            }

            // 获取提现审核记录
            $record = $this->getWithdrawRecordByBid($userId, $bid);
            if (empty($record)) {
                throw new WXException('ERR_INVEST_NO_EXIST');
            }

            // 如果已经是系统自动处理的状态则不处理
            if ($record['status'] == SupervisionEnum::STATUS_SYS_AUTO) {
                return $result;
            }

            $statusMap = [SupervisionEnum::STATUS_NOT_AUDIT, SupervisionEnum::STATUS_A_PASS, SupervisionEnum::STATUS_B_REFUND];
            // 只处理A角色待审核、B角色待审核、B角色拒绝的记录
            if (!in_array($record['status'], $statusMap)) {
                return $result;
            }

            // 更新提现审核状态为"系统自动处理"
            $id = $record['id'];
            $orderId = $record['order_id'];
            $toupdate = [
                'status' => SupervisionEnum::STATUS_SYS_AUTO,
            ];
            $condition = sprintf('status IN (%s)', implode(',', $statusMap));
            $updateResult = $this->updateOrderById($id, $toupdate, $condition);
            if (!$updateResult) {
                throw new WXException('ERR_WITHDRAW_AUDIT_FAILED');
            }
            PaymentApi::log(sprintf('SupervisionWithdrawAudit_repayWithdrawAudit SUCCESS, userId:%d, bid:%d, id:%d, orderId:%s', $userId, $bid, $id, $orderId));
            return $result;
        } catch (\Exception $e) {
            PaymentApi::log(sprintf('SupervisionWithdrawAudit_repayWithdrawAudit FAILED, userId:%d, bid:%d, id:%d, orderId:%s, code:%s, message:%s', $userId, $bid, $id, $orderId, $e->getCode(), $e->getMessage()));
            $result['respCode'] = -1;
            $result['respMsg'] = $e->getMessage();
            return $result;
        }
    }
}