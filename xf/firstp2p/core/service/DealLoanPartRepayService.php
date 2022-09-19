<?php
namespace core\service;

use core\dao\DealLoanPartRepayModel;
use core\dao\DealExtModel;
use core\dao\EnterpriseModel;
use core\dao\DealRepayModel;
use core\dao\DealPrepayModel;
use core\dao\DealModel;
use core\dao\DealRepayOplogModel;
use core\dao\PartialRepayModel;
use core\dao\UserModel;
use core\service\UserCarryService;
use core\dao\DealLoanTypeModel;
use libs\utils\Logger;

/**
 * 用户部分回款计划
 *
 * Class DealLoanPartRepayService
 * @package core\service
 */
class DealLoanPartRepayService extends BaseService
{

    /**
     * 执行还款
     * @param $deal_repay_id 还款计划ID
     * @param $ignore_impose_money
     * @param $negative 0 不可扣负 1 可扣负
     * @return mixed
     * @throws \Exception
     */
    public function repay($deal_repay_id, $ignore_impose_money, $admin = array(),$negative=1,$repayType=0, $submitUid = 0, $auditType = 0, $orderId = '') {
        $deal_repay_id = intval($deal_repay_id);
        if (empty($deal_repay_id)) {
            throw new \Exception("参数错误");
        }

        $userModel = new UserModel();
        $deal_repay_model = new DealRepayModel();
        $dealPartRepayModel = new DealLoanPartRepayModel();
        $dealService = new DealService();
        $userCarryService = new UserCarryService();

        //有状态为2的数据，或者状态为3的数据为空都不执行还款
        $savedCount = $dealPartRepayModel->getRepayCountByDealRepayId($deal_repay_id,DealLoanPartRepayModel::STATUS_SAVED);
        $adoptedCount = $dealPartRepayModel->getRepayCountByDealRepayId($deal_repay_id,DealLoanPartRepayModel::STATUS_ADOPTED);
        if($savedCount>0 || $adoptedCount== 0) {
            throw new \Exception("获取部分用户还款信息失败[$deal_repay_id]");
        }


        $deal_repay = $deal_repay_model->find($deal_repay_id);
        if (empty($deal_repay)) {
            throw new \Exception("获取还款计划失败[$deal_repay_id]");
        }

        //根据真实的部分还款数据修正还款数据
        $deal_repay = $dealPartRepayModel->formatPartRepay($deal_repay,$deal_repay_id);
        $deal = DealModel::instance()->find($deal_repay['deal_id']);

        if($repayType == 1){//代垫
            if($deal['advance_agency_id'] > 0){
                $advanceAgencyUserId = $dealService->getRepayUserAccount($deal['id'],1);
                $user = $userModel->find($advanceAgencyUserId);
                $deal_repay['user_id'] = $user['id'];
            }else{
                throw new \Exception('还款失败,未设置代垫机构!');
            }
        } elseif ($repayType == 2){//代偿
            if($deal['agency_id'] > 0){//担保机构代偿
                $advanceAgencyUserId = $dealService->getRepayUserAccount($deal['id'],2);
                $user = $userModel->find($advanceAgencyUserId);
                $deal_repay['user_id'] = $user['id'];
            }else{
                throw new \Exception('还款失败,未设置代偿机构!');
            }
        }

        $totalRepayMoney = 0.00;
        $rs = $dealPartRepayModel->repay($deal_repay,$ignore_impose_money, $totalRepayMoney,$negative,$repayType, $orderId);
        if($rs === false){
            throw new \Exception("还款失败[$deal_repay_id]");
        }else if($rs === 2){
            return true;
        }

        $rs = $userCarryService->updateWithdrawLimitAfterRepalyMoney($deal_repay['id'],$deal_repay['repay_money']);
        if($rs === false){
            throw new \Exception("更新金额限制失败[".$deal_repay['user_id']."]");
        }

        $dealModel = new DealModel();
        $deal = $dealModel->find($deal_repay['deal_id']);

        //添加还款操作记录
        $repayOpLog = new DealRepayOplogModel();
        $repayOpLog->operation_type = DealRepayOplogModel::REPAY_TYPE_PART;//部分还款
        $repayOpLog->operation_time = get_gmtime();
        $repayOpLog->operation_status = 1;
        $repayOpLog->operator = $admin['adm_name'];
        $repayOpLog->operator_id = $admin['adm_id'];

        //标的信息
        $repayOpLog->deal_id = $deal['id'];
        $repayOpLog->deal_name = $deal['name'];
        $repayOpLog->borrow_amount = $deal['borrow_amount'];
        $repayOpLog->rate = $deal['rate'];
        $repayOpLog->loantype = $deal['loantype'];
        $repayOpLog->repay_period = $deal['repay_time'];
        $repayOpLog->user_id = $deal['user_id'];

        //存管&&还款方式
        $repayOpLog->repay_type = $repayType;
        $repayOpLog->report_status = $deal['report_status'];

        //还款的信息
        $repayOpLog->deal_repay_id = $deal_repay['id'];
        $repayOpLog->repay_money = $totalRepayMoney;
        $repayOpLog->real_repay_time = get_gmtime();
        $repayOpLog->submit_uid = intval($submitUid);
        $repayOpLog->audit_type= intval($auditType);
        $repayOpLog->save();

        return true;
    }


    /**
     * 根据还款Id获取部分还款数据
     * @param $deal_repay_id 还款Id
     * @return \libs\db\Model
     */
    public function getPartRepayListByRepayId($deal_repay_id,$status=''){
        $dealPartRepayModel = new DealLoanPartRepayModel();
        $list = $dealPartRepayModel->getPartRepayListByRepayId($deal_repay_id,$status);
        foreach ($list as $key => $item) {
            $user = UserModel::instance()->find($item['loan_user_id']);
            if (!empty($user)) {
                $user['user_type_name'] = getUserTypeName($user['id']);
                // 获取用户企业名称，若不是企业用户，则企业名称为用户真实名称
                if (UserModel::USER_TYPE_ENTERPRISE == $user['user_type']) {
                    $user['real_name'] = getUserFieldUrl($user, EnterpriseModel::TABLE_FIELD_COMPANY_NAME);
                } else {
                    $user['real_name'] = getUserFieldUrl($user, UserModel::TABLE_FIELD_REAL_NAME);
                }
            }
            $list[$key]['user_name'] = getUserFieldUrl($user);
            $list[$key]['real_name'] = $user['real_name'];
        }
        return $list;
    }

}