<?php

namespace core\service;
use core\dao\IncomeExcessModel;
use core\dao\UserModel;
use core\dao\DealModel;
use libs\utils\Logger;
use core\dao\InterestExtraLogModel;
use core\dao\InterestExtraModel;

/**
 * 超额收益服务类
 * @author 王传路 <wangchuanlu@ucfgroup.com>
 * Date: 2015-12-29
 */
class IncomeExcessService {

    private $userModel = null;
    private $dealModel = null;
    private $incomeExcessModel = null;
    private $interestExtraLogModel = null;
    private $interestExtraService = null;

    public function __construct() {
        $this->userModel = new UserModel();
        $this->dealModel = new DealModel();
        $this->incomeExcessModel = new IncomeExcessModel();
        $this->interestExtraLogModel = new InterestExtraLogModel();
        $this->interestExtraService = new InterestExtraService();
    }

/**
 *  更新状态
 * @param unknown $excessId
 * @param unknown $changeData
 * @return boolean
 */
    public function updateIncomeExcessByExcessId($excessId,$changeData) {
        $changeData['update_time'] = time();
        //更新审核状态
        $res=$this->incomeExcessModel->update($changeData, "id=".$excessId );
        if(!$res) {
            return false ;
        }
        return true;
    }

/**
 * 超额收益更新为待还款状态
 * @param unknown $dealId
 * @return boolean
 */
    public function pendingRepay($dealId) {
        $incomdeExcessInfo = $this->incomeExcessModel->getIncomeExcessInfoByDealId($dealId);
        //以下情况直接返回
        //1、标未配置超额收益  2、状态不是审核通过  3、配置收益率为0
        if(empty($incomdeExcessInfo)
                || ($incomdeExcessInfo['status'] != InterestExtraService::INTEREST_STATUS_3)
                || (bccomp($incomdeExcessInfo['rate'],0,5) <= 0) ) {
            return true;
        }

        //更新审核状态
        $res = $this->incomeExcessModel->update(
                    array(
                        'status' => InterestExtraService::INTEREST_STATUS_1,
                        'update_time' => time(),
                    ),
                    "deal_id = ".$dealId." AND income_type = ".InterestExtraService::INCOME_TYPE_EXCESS
                );
        if(!$res) {
            return false ;
        }
        return true;
    }

    /**
     * 删除标信息
     * @param unknown $dealId 标id
     * @param unknown $adminId 管理员id
     * @return boolean
     */
    public function delDealByIds($dealIds,$adminId) {
        $deal = array();

        $now = time();
        $todayBeginTime = strtotime(date('Y-m-d'));

        $sql = "INSERT INTO
            %s (
            `deal_id`,
            `income_type`,
            `status`,
            `admin_id`,
            `type`,
            `create_time`,
            `rate`,
            `success_time`,
            `repay_start_time`,
            `interest_days`,
            `update_time`)
        VALUES
            (%d,%d,%d,%d,%d,%d,%d,%d,%d,%d,%d)
        ON DUPLICATE KEY UPDATE
            `status` = VALUES(status),
            `update_time` = VALUES(update_time)";

        $GLOBALS['db']->startTrans();
        try {
            foreach ($dealIds as $dealId) {
                $dealService = new DealService();
                $deal = $dealService->getDeal($dealId,true,false);
                $temp_sql = sprintf($sql,
                        InterestExtraModel::instance()->tableName(),
                        $deal['id'],
                        InterestExtraService::INCOME_TYPE_EXCESS,
                        InterestExtraService::INTEREST_STATUS_N2,
                        $adminId,
                        0,
                        $now,
                        0,
                        $deal['success_time'],
                        $deal['repay_start_time'],
                        0,
                        $now);
                $GLOBALS['db']->query($temp_sql);
                if($GLOBALS['db']->affected_rows() < 1) {
                    throw new \Exception('加锁失败');
                }
            }
            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__, "deal_id:" .$dealId,"删除失败", "error:" . $e->getMessage())));
            return false;
        }
        return true;
    }

    /**
     * 保存超额收益信息
     * @param unknown $dealId
     * @param unknown $excessRate 超额收益利率
     * @param unknown $adminId 管理员ID
     * @throws \Exception
     * @return boolean
     */
    public function saveIncomeExcessByDealId($dealId,$excessRate,$adminId) {
        $dealService = new DealService();
        $deal = $dealService->getDeal($dealId,true,false);

        $interestDays = $deal['repay_time'];
        if ( $deal['loantype'] != $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_DAY']) {
            $interestDays = $deal['repay_time'] * DealModel::DAY_OF_MONTH;
        }

        if(!empty($deal)) {
            try {
                $addInfo = array();
                $addInfo['deal_id']          = $deal['id'];
                $addInfo['income_type']      = InterestExtraService::INCOME_TYPE_EXCESS;
                $addInfo['status']           = InterestExtraService::INTEREST_STATUS_0;
                $addInfo['admin_id']         = $adminId;
                $addInfo['type']             = 0;
                $addInfo['create_time']      = time();
                $addInfo['rate']             = $excessRate;
                $addInfo['success_time']     = $deal['success_time'];
                $addInfo['repay_start_time'] = $deal['repay_start_time'];
                $addInfo['interest_days']    = $interestDays;//贴息天数
                $addInfo['audit_time']       = 0;

                $res= $this->incomeExcessModel->insert($addInfo);
                if(!$res)
                {
                    throw new \Exception('数据插入失败');
                }
            } catch (\Exception $e) {
                return false;
            }
        }
        return true;
    }

    /**
     * 获取符合贴息条件的标列表
     * @param array $param
     * @return array
     */
    public function getIncomeExcessDealsList($param) {
        return $this->incomeExcessModel->getIncomeExcessDealsList($param);
    }

    /**
     * 获取待审核标的
     * @param array $param
     * @return array
     */
    public function getIncomeExcessAuditList($param) {
        return $this->incomeExcessModel->getIncomeExcessAuditList($param);
    }
    /**
     * 获取已贴息表列表
     * @param array $param
     * @return array
     */
    public function getIncomeExcessHistory($param) {
        return $this->incomeExcessModel->getIncomeExcessHistory($param);
    }

    /**
     * 通过标id获取要符合条件标
     * @param array $deal_id
     * @return array
     */
    public function getIncomeExcessInfoByDealId($deal_id) {
        return $this->incomeExcessModel->getIncomeExcessInfoByDealId($deal_id);
    }

    /**
     * 检查标的是否有状态为审核中的
     * @param array $dealIds
     * @return array
     */
    public function checkIsDealInAudit($dealIds) {
        return $this->incomeExcessModel->checkIsDealInAudit($dealIds);
    }
}