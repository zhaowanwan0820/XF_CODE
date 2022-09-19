<?php

namespace NCFGroup\Ptp\services;

use NCFGroup\Common\Extensions\Base\ServiceBase;
use NCFGroup\Protos\Ptp\RPCErrorCode;
use NCFGroup\Common\Extensions\Base\ResponseBase;
use \Assert\Assertion as Assert;
use NCFGroup\Protos\Ptp\ProtoDealProject;
use NCFGroup\Ptp\daos\DealProjectDAO;
use NCFGroup\Ptp\daos\DealDAO;
use NCFGroup\Ptp\daos\UserCarryDAO;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;
use libs\utils\Logger;

use NCFGroup\Protos\Ptp\RequestUpdateDealProjectBankInfo;
use NCFGroup\Protos\Ptp\ResponseUpdateDealProjectBankInfo;

/**
 * DealService
 * 标相关service
 * @uses ServiceBase
 * @package default
 */
class PtpDealProjectService extends ServiceBase {

    const LOAN_MONEY_TYPE_ENTRUST = 3;

    /**
     * 增加dealProject记录
     * @param ProtoDealProject $request
     * @return ProtoDealProject
     */
    public function addDealProject(ProtoDealProject $request) {
        $userId = $request->getUserId();
        $approveNumber = $request->getApproveNumber();
        $borrowAmount = $request->getBorrowAmount();
        $credit = $request->getCredit();
        $loanType = $request->getLoanType();
        $name = $request->getName();
        $rate = $request->getRate();
        $repayReroid = $request->getRepayReriod();
        $projectInfoUrl = $request->getProjectInfoUrl();
        $projectExtrainfoUrl = $request->getProjectExtrainfoUrl();
        $dealType = $request->getDealType();

        /**
         * 判断approve_number是否存在
         */
        $exist_obj = DealProjectDAO::getProject($approveNumber);
        if ($exist_obj) {
            $exist_obj->userId = $userId;
            $exist_obj->borrowAmount = $borrowAmount;
            $exist_obj->credit = $credit;
            $exist_obj->loantype = $loanType;
            $exist_obj->name = $name;
            $exist_obj->rate = $rate;
            $exist_obj->repayTime = $repayReroid;
            $exist_obj->projectInfoUrl = $projectInfoUrl;
            $exist_obj->projectExtrainfoUrl = $projectExtrainfoUrl;
            $exist_obj->dealType = $dealType;
            $ret = DealProjectDAO::updateProjectInfo($exist_obj);
        } else {
            $ret = DealProjectDAO::addProjectInfo($name, $userId, $approveNumber, $borrowAmount, $credit, $loanType, $rate, $repayReroid, $projectInfoUrl, $projectExtrainfoUrl);
        }

        $response = new ResponseBase();
        if ($ret === false) {
            $response->resCode = RPCErrorCode::FAILD;
        } else {
            $response->resCode = RPCErrorCode::SUCCESS;
            $response->projectId = $ret->id;
            $response->approveNumber = $ret->approveNumber;
        }
        return $response;
    }

    /**
     * 增加通知贷项目记录
     * @param ProtoDealProject $request
     * @return ProtoDealProject
     */
    public function addDealProjectCompound(ProtoDealProject $request) {
        $userId = $request->getUserId();
        $approveNumber = $request->getApproveNumber();
        $borrowAmount = $request->getBorrowAmount();
        $credit = $request->getCredit();
        $loanType = $request->getLoanType();
        $name = $request->getName();
        $rate = $request->getRate();
        $repayReroid = $request->getRepayReriod();
        $projectInfoUrl = $request->getProjectInfoUrl();
        $projectExtrainfoUrl = $request->getProjectExtrainfoUrl();
        $dealType = $request->getDealType();
        $lockPeriod = $request->getLockPeriod();
        $redemptionPeriod = $request->getRedemptionPeriod();

        /**
         * 判断approve_number是否存在
         */
        $exist_obj = DealProjectDAO::getProject($approveNumber);
        if ($exist_obj && $exist_obj->id > 0) {
            $exist_obj_Compound = DealProjectDAO::getProjectCompound($exist_obj->id);
            if (!$exist_obj_Compound) {
                $exist_obj_Compound = new \NCFGroup\Ptp\models\Firstp2pDealProjectCompound();
            }
            $exist_obj_Compound->lockPeriod = $lockPeriod;
            $exist_obj_Compound->redemptionPeriod = $redemptionPeriod;

            $exist_obj->userId = $userId;
            $exist_obj->borrowAmount = $borrowAmount;
            $exist_obj->credit = $credit;
            $exist_obj->loantype = $loanType;
            $exist_obj->name = $name;
            $exist_obj->rate = $rate;
            $exist_obj->repayTime = $repayReroid;
            $exist_obj->projectInfoUrl = $projectInfoUrl;
            $exist_obj->projectExtrainfoUrl = $projectExtrainfoUrl;
            $exist_obj->dealType = $dealType;
            $ret = DealProjectDAO::updateProjectInfoCompound($exist_obj, $exist_obj_Compound);
        } else {
            $ret = DealProjectDAO::addProjectInfoCompound($name, $userId, $approveNumber, $borrowAmount, $credit, $loanType, $rate, $repayReroid, $projectInfoUrl, $projectExtrainfoUrl, $dealType, $lockPeriod, $redemptionPeriod);
        }

        $response = new ResponseBase();
        if ($ret === false) {
            $response->resCode = RPCErrorCode::FAILD;
        } else {
            $response->resCode = RPCErrorCode::SUCCESS;
            $response->projectId = $ret->id;
            $response->approveNumber = $ret->approveNumber;
        }
        return $response;
    }

    public function getProjectInfo(SimpleRequestBase $request)
    {
        $params = $request->getParamArray();
        $result = DealProjectDAO::getProjectById(intval($params['id']));
        if (is_object($result)) {
            $result = $result->toArray();
        }

        return $result;
    }

    /**
     * 判断项目名称是否存在
     * @param SimpleRequestBase $request
     * @return bool
     */
    public function isProjectNameExisted(ProtoDealProject $request)
    {
        $result = DealProjectDAO::getProjectByName(addslashes(trim($request->getName())));
        if (is_object($result)) {
           return $result->toArray();
        }
        return false;
    }

    public function getProjectByIds(SimpleRequestBase $request) {
        $params = $request->getParamArray();
        $result = DealProjectDAO::getProjectByIds($params['projectIds']);
        $result = is_object($result) ? $result->toArray() : array();

        $projectInfo = array();
        foreach ($result as $item) {
            $projectInfo[$item['id']] = $item;
        }

        return $params['compress'] ? gzdeflate(json_encode($projectInfo, JSON_UNESCAPED_UNICODE), 9) : $projectInfo;
    }

    /**
     * 修改项目的银行卡账号
     * @param RequestUpdateDealProjectBankcard $request
     * @return ResponseUpdateDealProjectBankcard
     */
    public function updateBankInfo(RequestUpdateDealProjectBankInfo $request)
    {
        $response = new ResponseUpdateDealProjectBankInfo();
        try {
            $approve_number = $request->getApproveNumber();
            $bankcard = $request->getBankcard();
            $bank_id = $request->getBankId();
            $bankzone = $request->getBankzone();
            $card_name = $request->getCardName();
            $card_type = $request->getCardType();
            $clearing_type = $request->getClearingType();

            $log_params_info = sprintf('approve_number: %s | new_bankcard: %s', $approve_number, $bankcard);

            $project_obj = DealProjectDAO::getProject($approve_number);
            if (empty($project_obj) || self::LOAN_MONEY_TYPE_ENTRUST != $project_obj->loanMoneyType) {
                throw new \Exception('do not find the project or loan_money_type is not entrust');
            }

            $deal_counts = $this->_getNoWithdrawalDealCounts($project_obj);
            if (0 == $deal_counts && $project_obj->borrowAmount == $project_obj->moneyBorrowed) {
                throw new \Exception('do not meet the conditions');
            }

            // 到这里，说明有未提现的标的，则更新项目的银行账号
            if (false ===  DealProjectDAO::updateBankInfoById($project_obj->id, $bankcard, $bank_id, $bankzone, $card_name ,$card_type,$clearing_type)) {
                throw new \Exception('update project-bankcard failed');
            }

            $status = true;
            $affected_deal_count = $deal_counts;
            Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, 'success', $log_params_info, sprintf('affected_deal_counts:%s', $deal_counts), 'line:' . __LINE__)));
        } catch (\Exception $e) {
            $status = false;
            $affected_deal_count = 0;
            Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, 'fail', $log_params_info, $e->getMessage(), 'line:' . __LINE__)));
        }
        $response->setStatus($status);
        $response->setAffectedDealCount($affected_deal_count);
        return $response;
    }

    /**
     * 根据项目获取未提现的标的数量
     * @param model $project_id
     * @return int
     */
    private function _getNoWithdrawalDealCounts($project_obj)
    {
        $deal_id_arr = DealDAO::getDealIdsByProjectId($project_obj->id);
        $deal_id_arr_nowithdrawal = array();
        foreach ($deal_id_arr as $deal_id) {
            if (false === UserCarryDAO::isWithdrawal($deal_id)) {
                $deal_id_arr_nowithdrawal[] = $deal_id;
            }
        }
        Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, 'affected_deal_id:' . implode(',', $deal_id_arr_nowithdrawal))));

        return count($deal_id_arr_nowithdrawal);
    }
}
