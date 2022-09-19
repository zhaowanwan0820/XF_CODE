<?php
namespace core\service\project;

use core\dao\deal\DealModel;
use core\dao\project\DealProjectModel;
use core\dao\supervision\SupervisionWithdrawModel;
use core\dao\deal\DealInfoModel;
use libs\utils\DBDes;
use core\service\BaseService;
use core\enum\DealProjectEnum;
use libs\utils\Logger;
use core\service\project\DealProjectRiskAssessmentService;

class ProjectService extends BaseService{


    /**
     * 查询项目信息
     * @param $id
     */
    public function getProInfo($id, $deal_id = 0){
        $pro = DealProjectModel::instance()->findViaSlave($id);
        $pro['intro_html'] = '';

        // 标的活动简介
        if (!empty($deal_id)) {
            $deal_intro = DealInfoModel::getDealActivityIntroductionByDealId($deal_id);
            $pro['intro_html'] .= $deal_intro;
        }

        if($pro && $pro['intro']){
            $pro['intro_html'] .= str_replace("\n", "<br/>", $pro['intro']);
        }
        if (!empty($pro['risk_bearing'])){
            $pro_risk_service = new DealProjectRiskAssessmentService();
            $pro['risk'] =  $pro_risk_service->getAssesmentNameById($pro['risk_bearing']);

        }
        return $pro;
    }
    /**
     * 更新项目的已上标金额
     * @param $id
     * @return float||bool
     */
    public function updateProBorrowed($id){
        $project = DealProjectModel::instance()->find($id);
        if($project){
            $money_borrowed = DealProjectModel::instance()->getProBorrowed($id);
            $project->money_borrowed = $money_borrowed;
            if($project->save()){
                return $money_borrowed;
            }
        }
        return false;
    }

    /**
     * 更新项目的已投资金额
     * @param $id
     * @return float||bool
     */
    public function updateProLoaned($id){
        $project = DealProjectModel::instance()->find($id);
        if($project){
            $money_loaned = DealProjectModel::instance()->getProLoaned($id);
            $project->money_loaned = $money_loaned;
            if($project->save()){
                return $money_loaned;
            }
        }
        return false;
    }

    /**
     * 更新项目的已上标金额和已投金额
     * @param $id
     * @param $borrow_amount
     * @param $loan_amount
     */
    public function updateProBorrowedLoanedById($id,$borrow_amount, $loan_amount){
        if (empty($id)){
            return false;
        }

        $deal_project_model = new DealProjectModel();
        $id = intval($id);
        $sql_where = " AND (`money_borrowed`+'".floatval($borrow_amount)."') >=0 and (`money_loaned`+'".floatval($loan_amount)."') >=0 and ";
        $sql_where .= " borrow_amount >= (`money_borrowed`+'".floatval($borrow_amount)."') and borrow_amount >= (`money_loaned`+'".floatval($loan_amount)."') ";
        $sql = "UPDATE ".$deal_project_model->tableName()." SET money_borrowed=`money_borrowed`+'".floatval($borrow_amount)."', money_loaned=`money_loaned`+'".floatval($loan_amount)."'
             WHERE id='$id' ".$sql_where;
        return  $deal_project_model->updateRows($sql);
    }

    /**
     * @param $deal_project_id
     * @return bool
     */
    public function copyDealProject($deal_project_id) {
        $deal_project_id = intval($deal_project_id);
        if ($deal_project_id <= 0) {
            return false;
        }

        $obj = DealProjectModel::instance()->find($deal_project_id);
        $row = $obj->getRow();

        if (!$row) {
            return false;
        }

        $GLOBALS['db']->startTrans();

        try {
            $dp_model = new DealProjectModel();
            unset($row['id']);
            $dp_model->setRow($row);
            $dp_model->name = $dp_model->name . "[复制]";
            $dp_model->money_borrowed = 0;
            $dp_model->money_loaned = 0;
            $dp_model->business_status = DealProjectEnum::$PROJECT_BUSINESS_STATUS['waitting'];
            if ($dp_model->save() === false) {
                throw new \Exception("copy project fail");
            }
            $id = $dp_model->id;

            $deal = $dp_model->getFirstDealByProjectId($deal_project_id);
            if (!$deal) {
                throw new \Exception("project has no deal");
            }

            $deal_service = new \core\service\deal\DealService();
            $rs = $deal_service->copyDeal($deal['id'], $id);
            if ($rs === false) {
                throw new \Exception("copy deal fail");
            }

            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            return false;
        }
        return true;
    }
    /**
     * 获取项目的风险承受能力
     * @param $id
     */
    public function getProRisk($id){
        if (!is_numeric($id)){
            return false;
        }
        $pro = DealProjectModel::instance()->findViaSlave($id,'risk_bearing');
        $pro['risk'] = [
            'name' => '',
            'describe' => '',
        ];
        if (!empty($pro['risk_bearing'])){
            $pro_risk_service = new DealProjectRiskAssessmentService();
            $pro['risk'] =  $pro_risk_service->getAssesmentNameById($pro['risk_bearing']);
        }

        return $pro;
    }

    /**
     * @根据信贷审批单号查询项目
     * @param  string $approveNumber
     * @return bool
     */
    public function getDealProjectByApproveNumber($approveNumber, $fields = "*")
    {
        $dealProjectModel = new DealProjectModel();
        return $dealProjectModel->findBy("`approve_number` = '" . $dealProjectModel->escape($approveNumber) . "'", $fields, array(), true);
    }

    /**
     * 修改项目的银行卡账号
     * @param RequestUpdateDealProjectBankcard $request
     * @return ResponseUpdateDealProjectBankcard
     */
    public function updateBankInfo($approve_number,$bankcard,$bank_id,$bankzone,$card_name,$card_type)
    {
        try {
            $log_params_info = sprintf('approve_number: %s | new_bankcard: %s', $approve_number, $bankcard);

            $dealProject = new DealProjectModel();
            $project_obj = $dealProject->getProjectInfoByApproveNumber($approve_number);
            if (empty($project_obj) || DealProjectEnum::LOAN_MONEY_TYPE_ENTRUST != $project_obj['loan_money_type']) {
                throw new \Exception('do not find the project or loan_money_type is not entrust');
            }

            $deal_counts = $this->_getNoWithdrawalDealCounts($project_obj);
            if (0 == $deal_counts && $project_obj['borrow_amount'] == $project_obj['money_borrowed']) {
                throw new \Exception('do not meet the conditions');
            }
            // 到这里，说明有未提现的标的，则更新项目的银行账号
            if (false ===  $dealProject::updateBankInfoById($project_obj['id'], $bankcard, $bank_id, $bankzone, $card_name ,$card_type)) {
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
        return $affected_deal_count;
    }

    /**
     * 根据项目获取未提现的标的数量
     * @param model $project_id
     * @return int
     */
    private function _getNoWithdrawalDealCounts($project_obj)
    {
        $dealModel = new DealModel();
        $deal_id_arr = $dealModel->getDealIdsByProjectId($project_obj['id']);
        $deal_id_arr_nowithdrawal = array();
        foreach ($deal_id_arr as $deal_id) {
            if (false === SupervisionWithdrawModel::instance()->isWithdrawal($deal_id)) {
                $deal_id_arr_nowithdrawal[] = $deal_id;
            }
        }
        Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, 'affected_deal_id:' . implode(',', $deal_id_arr_nowithdrawal))));
        return count($deal_id_arr_nowithdrawal);
    }
}
