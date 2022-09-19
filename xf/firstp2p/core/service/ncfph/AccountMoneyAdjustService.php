<?php
namespace core\service\ncfph;

use libs\utils\Logger;
use libs\db\Db;
use core\dao\UserModel;
use core\dao\LoanAccountAdjustMoneyModel;
use core\service\ncfph\AccountService as PhAccountService;
use NCFGroup\Common\Library\Idworker;

class AccountMoneyAdjustService
{
    private $errors = [];

    // 类型
    const TYPE_WITHDRAW_RETURN = 1; // 提现退汇
    const TYPE_SYSTEM_REPAIR = 2; // 系统余额修复

    const MONEY_TYPE_INCRE = 1; // changeMoney 加可用余额类型
    const MONEY_TYPE_DECRE = 2; // changeMoney 减可用余额类型

    /**
     * 检查错误信息
     * @param array $rowData
     * @param array $applyRecords 返回可以添加的数据
     * @return boolean
     */
    public function checkInfo($contentArray, &$applyRecords)
    {
        $rowColumns = ['username', 'realName', 'money', 'adjustType', 'note'];
        $allowTypes = [self::TYPE_WITHDRAW_RETURN, self::TYPE_SYSTEM_REPAIR];
        //检查数据
        $applyRecords = array();
        $error = array();
        foreach ($contentArray as $line => $row)
        {
            $rowData = explode(',' ,$row);
            $prefix = '第'.($line + 1).'行:';
            // 检查字段数
            if (count($rowData) !== 5)
            {
                $this->setError($prefix.'字段数不匹配');
                continue;
            }
            $rowData = array_combine($rowColumns, $rowData);
            // 检查调账类型
            if (!in_array($rowData['adjustType'], [self::TYPE_WITHDRAW_RETURN, self::TYPE_SYSTEM_REPAIR]))
            {
                $this->setError($prefix.'['.intval($rowData['adjustType']) .']不符合系统支持的调账类型');
                continue;
            }
            // 检查金额格式
            if (floatval($rowData['money']) != bcadd($rowData['money'], '0.00', 2))
            {
                $this->setError($prefix.'['.$rowData['money'] .']金额格式错误');
                continue;
            }

            // 检查用户名和真实姓名是否对应
            $userInfo = UserModel::instance()->findByViaSlave(" user_name = '{$rowData['username']}' ");
            if ($userInfo['real_name'] !== $rowData['realName'])
            {
                $this->setError($prefix.'['.$rowData['realName'] . ']与真实姓名不匹配');
                continue;
            }
            // account信息判断
            $phAccountService = new PhAccountService();
            $accountInfo = $phAccountService->getInfoByUserIdAndType($userInfo['id'], $userInfo['user_purpose']);
            $rowData['accountType'] = $accountInfo['accountType'];
            $rowData['orderId'] = Idworker::instance()->getId();
            $rowData['userId'] = $userInfo['id'];
            $applyRecords[] = $rowData;
        }
    }

    /**
     * 读取错误信息
     */
    public function hasErrors()
    {
        return $this->getErrors() !== [];
    }

    /**
     * 设置一个错误信息
     * @param string $errInfo
     */
    public function setError($errInfo)
    {
        $this->errors[] = $errInfo;
    }

    /**
     * 获取所有的错误信息
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * 打印错误信息
     */
    public function printError()
    {
        return implode('<br/>', $this->getErrors());
    }


    /**
     * 批量添加申请数据
     * @param array $applyRecordList 申请数据列表
     * @return boolean
     */
    public function batchAdd($applyRecordList, $adminSession)
    {
        $db = Db::getInstance('firstp2p', 'master');
        try
        {
            $datetime = date('Y-m-d H:i:s');
            $logInfo = $datetime. ' 新增:'.$adminSession['adm_name']."\n";
            $db->startTrans();
            foreach ($applyRecordList as $record)
            {
                $rowData = [
                    'vip_name' => $record['username'],
                    'vip_num' => numTo32($record['userId']),
                    'user_name' => $record['realName'],
                    'account_type' => $record['accountType'],
                    'user_id' => $record['userId'],
                    'money' => $record['money'],
                    'type' => $record['adjustType'],
                    'create_time' => time(),
                    'note' => $record['note'],
                    'order_id' => $record['orderId'],
                    'log' => $logInfo,
                ];
                $db->autoExecute('firstp2p_loan_account_adjust_money', $rowData, 'INSERT');
            }
            $db->commit();
            return true;
        } catch (\Exception $e) {
            Logger::error('调整用户网贷账户余额数据保存失败,原因:'.$e->getMessage());
            $db->rollback();
            return false;
        }
    }

    /**
     * 将指定订单更新状态
     * @param array $ids 订单id 数组
     * @param integer $status 订单状态
     * @return 操作结果
     */
    public function updateStatus($ids, $status, $adminSession = [])
    {
        $db = Db::getInstance('firstp2p', 'master');
        try {
            $logInfo = '';
            $datetime = date('Y-m-d H:i:s');
            $logInfo = $datetime. ' A角色通过:'.$adminSession['adm_name']."\n";
            $statusCondition = '';
            $isP2pReady = true;
            $condition = ' id IN ('.implode(',',$ids).')';
            switch($status)
            {
                // A角色审核通过
                case LoanAccountAdjustMoneyModel::STATUS_NEED_FINAL_AUDIT:
                $logInfo = $datetime. ' A角色通过:'.$adminSession['adm_name']."\n";
                $statusCondition = ' AND status =  '.LoanAccountAdjustMoneyModel::STATUS_NEED_AUDIT;
                $db->query("UPDATE firstp2p_loan_account_adjust_money SET status = $status, log = CONCAT(log, '{$logInfo}') WHERE $condition {$statusCondition}");
                break;
                // B角色审核通过
                case LoanAccountAdjustMoneyModel::STATUS_PASS:
                $statusCondition = ' AND status =  '.LoanAccountAdjustMoneyModel::STATUS_NEED_FINAL_AUDIT;
                $logInfo = $datetime. ' B角色通过:'.$adminSession['adm_name']."\n";
                $this->batchPass($ids);
                break;
                // A拒绝, B拒绝
                case LoanAccountAdjustMoneyModel::STATUS_REFUSE_A:
                $statusCondition = ' AND (status = '.LoanAccountAdjustMoneyModel::STATUS_NEED_AUDIT.') ';
                $logInfo = $datetime. ' A角色拒绝:'.$adminSession['adm_name']."\n";
                $db->query("UPDATE firstp2p_loan_account_adjust_money SET status = 4, log = CONCAT(log, '{$logInfo}') WHERE $condition {$statusCondition}");
                break;
                case 5:
                $statusCondition = ' AND (status = '.LoanAccountAdjustMoneyModel::STATUS_NEED_FINAL_AUDIT.') ';
                $logInfo = $datetime. ' B角色拒绝:'.$adminSession['adm_name']."\n";
                $db->query("UPDATE firstp2p_loan_account_adjust_money SET status = 4, log = CONCAT(log, '{$logInfo}') WHERE $condition {$statusCondition}");
                break;

            }
            return true;
        } catch (\Exception $e) {
            Logger::error('更新调账订单'.implode(',', $ids).' 失败,原因:'.$e->getMessage());
            return false;
        }
    }

    public function batchPass($ids)
    {
        $db = Db::getInstance('firstp2p', 'master');
        $batchCnt = count($ids);
        $successCnt = 0;
        $datetime = date('Y-m-d H:i:s');
        $adminSession = \es_session::get(md5(conf("AUTH_KEY")));
        $logInfo = $datetime. ' B角色通过:'.$adminSession['adm_name']."\n";
        foreach ($ids as $id)
        {
            try {
                $db->startTrans();
                // 更新本地数据状态
                $db->query("UPDATE firstp2p_loan_account_adjust_money SET `status` = ".LoanAccountAdjustMoneyModel::STATUS_PASS.", log = CONCAT(log, '{$logInfo}') WHERE id = '{$id}' AND `status` = 2");
                $rows = $db->affected_rows();
                if ($rows <= 0)
                {
                    throw new \Exception('重复执行,记录号:'.$id);
                }
                $result  = $this->doPass($id, $adminSession);
                if (!$result)
                {
                    throw new \Exception('请求普惠系统执行资金操作失败, 记录号:'.$id);
                }
                $db->commit();
                $successCnt ++;
            } catch(\Exception $e) {
                $db->rollback();
                Logger::error('执行资金记录操作失败,原因:'.$e->getMessage());
            }
        }

        if ($batchCnt !== $successCnt)
        {
            throw new \Exception('批量通过部分结果执行成功,失败记录请刷新待审核列表查看');
        }
        return true;
    }

    /**
     * 执行资金记录操作
     */
    public function doPass($id, $adminSession)
    {
        // 取审批记录详情
        $db = Db::getInstance('firstp2p', 'master');

        $record = LoanAccountAdjustMoneyModel::instance()->find($id);
        if (empty($record))
        {
            throw new \Exception ('执行资金操作失败,原申请数据不存在,记录号:'.$id);
        }

        $phAccountService = new PhAccountService();

        // 实时判断普惠可用金额是否允许调账,如果是调减的操作
        $accountInfo = $phAccountService->getInfoByUserIdAndType($record['user_id'], $record['account_type']);
        if (empty($accountInfo))
        {
            throw new \Exception('执行资金操作失败,被调账用户网贷账户信息读取失败,用户编号:'.$record['user_id'].',账户类型:'.$record['account_type']);
        }
        // 如果是调减操作, 则判断用户资金是否足够
        if (bccomp($record['money'], 0, 2) < 0 && bcadd($accountInfo['money'], $record['money'], 2) < 0)
        {
            throw new \Exception('执行资金操作失败,被调账用户可用余额不足,网贷可用余额:'.$accountInfo['money'].',调账金额:'.$record['money']);
        }
        // 执行调账逻辑
        $moneyType = self::MONEY_TYPE_INCRE;
        if(bccomp($record['money'], '0.00', 2) < 0)
        {
            $moneyType = self::MONEY_TYPE_DECRE;
        }
        $result = $phAccountService->changeMoney($record['order_id'], $accountInfo['accountId'], abs($record['money']), LoanAccountAdjustMoneyModel::$loan_account_adjust_money_type[$record['type']], $record['note'], $moneyType, true, $adminSession['adm_id']);
        return isset($result['status']) && $result['status'] == '00' ? true : false;
    }

}
