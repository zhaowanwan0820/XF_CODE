<?php

/**
 * Credit class file
 * @信贷系统一键上标
 * @author: liuzhenpeng
 *
 */

namespace core\service;

use core\dao\DealModel;
use core\dao\DealProjectModel;
use core\dao\DealExtModel;
use core\dao\DealCompoundModel;
use core\dao\CouponDealModel;
use core\dao\DealProjectCompoundModel;
use core\dao\DealAgencyModel;
use libs\utils\Logger;
use libs\utils\Rpc;
use NCFGroup\Protos\Contract\RequestSetDealCId;

class CreditDealService extends BaseService
{

   /**
    * @检查审批单号是否存在
    * @param string $approval_number
    * @return bool
    */ 
    public function chkApprovalNumber($approval_number)
    {
        $condition = "approve_number=':approval_number'";
        return DealProjectModel::instance()->findBy($condition, '*', array(':approval_number' => $approval_number));
    }

    /**
     * @增加新的标的
     * @param array $deal_data
     * @return bool
     */
    public function addProjectDeal($deal_data)
    {
        $res = false;

        $GLOBALS['db']->startTrans();
        try{
            $dealProjectModel = new DealProjectModel();
            $DealProjectModel->status         = 0;
            $dealProjectModel->name           = $deal_data['name'];
            $dealProjectModel->user_id        = $deal_data['user_id'];
            $dealProjectModel->approve_number = $deal_data['approve_number'];
            $dealProjectModel->borrow_amount  = $deal_data['borrow_amount'];
            $dealProjectModel->credit         = $deal_data['credit'];
            $dealProjectModel->loantype       = $deal_data['loan_type'];
            $dealProjectModel->rate           = $deal_data['rate'];
            $dealProjectModel->repay_time     = $deal_data['repay_period'];
            $dealProjectModel->intro          = $deal_data['project_info_url'];
            $dealProjectModel->create_time    = get_gmtime();
            $dealProjectModel->card_name      = $deal_data['card_name'];
            $dealProjectModel->bankcard       = $deal_data['bankcard'];
            $dealProjectModel->bankzone       = $deal_data['bankzone'];
            $dealProjectModel->bank_id        = $deal_data['bankid'];
            $dealProjectModel->loan_money_type = $deal_data['loan_money_type'];
            $dealProjectModel->borrow_fee_type = $deal_data['loan_fee_rate_type'];
            $dealProjectModel->entrust_sign    = $deal_data['entrust_sign'];
            $dealProjectModel->deal_type       = $deal_data['deal_type'];
            $dealProjectModel->entrust_agency_sign   = $deal_data['entrust_agency_sign'];
            $dealProjectModel->entrust_advisory_sign = $deal_data['entrust_advisory_sign'];
            $deal_project_res = $dealProjectModel->insert();
            if(!$deal_project_res){
                throw new \Exception("添加标的项目信息失败, firstp2p_deal_project");
            }
            $projectId = $dealProjectModel->db->insert_id();

            $dealModel = new DealModel();
            $dealModel->pay_agency_id = (new DealAgencyModel)->getUcfPayAgencyId();
            $dealModel->agency_id     = $deal_data['agency_id'];
            $dealModel->deal_type     = $deal_data['deal_type'];
            $dealModel->name          = $deal_data['name'];
            $dealModel->consult_fee_rate = $deal_data['consult_fee_rate'];
            $dealModel->packing_rate     = $deal_data['packing_rate'];
            $dealModel->guarantee_fee_rate = $deal_data['guarantee_fee_rate'];
            $dealModel->advance_agency_id  = $deal_data['advance_agency_id'];
            $dealModel->loantype       	   = $deal_data['loan_type'];
            $dealModel->sub_name           = '';
            $dealModel->cate_id            = 3;
            $dealModel->manager            = '';
            $dealModel->manager_mobile     = '';
            $dealModel->is_effect          = 1;
            $dealModel->is_delete          = 0;
            $dealModel->sort               = 1;
            $dealModel->icon_type          = 1;
            $dealModel->icon               = '';
            $dealModel->seo_title          = '';
            $dealModel->seo_keyword        = '';
            $dealModel->seo_description    = '';
            $dealModel->name_match         = '';
            $dealModel->name_match_row     = '';
            $dealModel->deal_cate_match    = '';
            $dealModel->deal_cate_match_row= '';
            $dealModel->tag_match          = '';
            $dealModel->tag_match_row      = '';
            $dealModel->type_match         = '';
            $dealModel->type_match_row     = '';
            $dealModel->is_recommend       = 0;
            $dealModel->buy_conut          = 0;
            $dealModel->load_money         = 0;
            $dealModel->repay_money        = 0;
            $dealModel->start_time         = 0;
            $dealModel->success_time       = 0;
            $dealModel->repay_start_time   = 0;
            $dealModel->last_repay_time    = 0;
            $dealModel->next_repay_time    = 0;
            $dealModel->bad_time           = 0;
            $dealModel->deal_status        = 0;
            $dealModel->end_date           = 7;
            $dealModel->voffice            = 0;
            $dealModel->vposition          = 0;
            $dealModel->services_fee       = 0;
            $dealModel->publish_wait       = 0;
            $dealModel->is_send_bad_msg    = 0;
            $dealModel->bad_msg            = '';
            $dealModel->send_half_msg_time = 0;
            $dealModel->send_three_msg_time= 0;
            $dealModel->is_has_loans       = 0;
            $dealModel->loan_type          = $deal_data['loan_type'];
            $dealModel->warrant            = 2;
            $dealModel->min_loan_money     = 100;
            $dealModel->update_json        = '';
            $dealModel->manage_fee_text    = '';
            $dealModel->note               = '';
            $dealModel->coupon_type        = 1;
            $dealModel->approve_number     = $deal_data['approve_number'];
            $dealModel->pay_fee_rate       = $deal_data['annual_payment_rate'];
            $dealModel->is_hot             = 0;
            $dealModel->is_new             = 0;
            $dealModel->borrow_amount      = $deal_data['borrow_amount'];
            $dealModel->repay_time         = $deal_data['repay_period'];
            $dealModel->advisory_id        = $deal_data['advisory_id'];
            $dealModel->rate               = $deal_data['rate'];
            $dealModel->day                = $deal_data['overdue_day'];
            $dealModel->user_id            = $deal_data['user_id'];
            $dealModel->type_id            = $deal_data['type_id'];
            $dealModel->loan_fee_rate      = $deal_data['manage_fee_rate'];
            $dealModel->income_fee_rate    = $deal_data['rate_yields'];
            $dealModel->prepay_rate        = $deal_data['prepay_rate'];
            $dealModel->prepay_penalty_days= $deal_data['prepay_penalty_days'];
            $dealModel->prepay_days_limit  = $deal_data['prepay_days_limit'];
            $dealModel->overdue_rate       = $deal_data['overdue_rate'];
            $dealModel->overdue_day        = $deal_data['overdue_day'];
            $dealModel->contract_tpl_type  = $deal_data['contract_tpl_type'];
            $dealModel->create_time        = get_gmtime();
            $dealModel->annual_payment_rate= $deal_data['annual_payment_rate'];
            $dealModel->update_time        = 0;
            $dealModel->project_id         = $projectId;
            $deal_res = $dealModel->insert();
            if(!$deal_res){
                throw new \Exception("添加标的信息失败, firstp2p_deal");
            }
            $dealId = $dealModel->db->insert_id();
            
            $dealExtModel = new DealExtModel();
            $dealExtModel->deal_id = $dealId;
            $dealExtModel->income_base_rate = $deal_data['rate_yields'];
            $dealExtModel->leasing_contract_num = $deal_data['leasing_contract_num'];
            $dealExtModel->lessee_real_name     = $deal_data['lessee_real_name'];
            $dealExtModel->leasing_money        = $deal_data['leasing_money'];
            $dealExtModel->entrusted_loan_entrusted_contract_num = $deal_data['entrusted_loan_entrusted_contract_num'];
            $dealExtModel->entrusted_loan_borrow_contract_num    = $deal_data['entrusted_loan_borrow_contract_num'];
            $dealExtModel->base_contract_repay_time              = $deal_data['base_contract_repay_time'];
            $dealExtModel->leasing_contract_title                = $deal_data['leasing_contract_title'];
            $dealExtModel->line_site_id     = $deal_data['line_site_id'];
            $dealExtModel->line_site_name   = $deal_data['line_site_name'];
            $dealExtModel->guarantee_fee_rate_type = $deal_data['guarantee_fee_rate_type'];
            $dealExtModel->loan_application_type   = $deal_data['loan_application_type'];
            $dealExtModel->loan_fee_rate_type      = $deal_data['loan_fee_rate_type'];
            $dealExtModel->pay_fee_rate_type       = $deal_data['pay_fee_rate_type'];
            $dealExtModel->consult_fee_rate_type   = $deal_data['consult_fee_rate_type'];
            $dealExtModel->contract_transfer_type  = $deal_data['contract_transfer_type'];
            $dealExtModel->overdue_break_days      = $deal_data['overdue_break_days'];
            $dealExtModel->first_repay_interest_day= $deal_data['fixed_replay'];
            $dealExtModel->deal_name_prefix        = '';
            $dealExtModel->pay_fee_ext             = '';
            $dealExtModel->guarantee_fee_ext       = '';
            $dealExtModel->consult_fee_ext         = '';
            $dealExtModel->consult_fee_rate        = '';
            $dealExtModel->loan_fee_ext            = '';
            $dealExtModel->management_fee_ext      = '';
            $dealExtModel->loan_type               = 0;
            $dealExtModel->use_info                = '';
            $deal_ext_res = $dealExtModel->insert();
            if(!$deal_ext_res){
                throw new \Exception("添加标的关联信息失败, firstp2p_deal_ext");
            }

            $couponDealModel = new CouponDealModel();
            $couponDealModel->deal_id = $dealId;
            $couponDealModel->pay_auto= 1;
            $couponDealModel->rebate_days = ($deal_data['repay_period_type'] == 1) ? $deal_data['repay_period'] : ($deal_data['repay_period']*30);
            $coupon_deal_res = $couponDealModel->insert();
            if(!$coupon_deal_res){
                throw new \Exception("添加优惠码信息失败, firstp2p_coupon_deal");
            }
            
            /*通知贷*/
            if($deal_data['deal_type'] == 1){
                $dealProjectCompoundModel = new DealProjectCompoundModel();
                $dealProjectCompoundModel->lock_period       = $deal_data['lock_period'];
                $dealProjectCompoundModel->redemption_period = $deal_data['redemption_period'];
                $dealProjectCompoundModel->project_id        = $projectId;
                $deal_project_compound_res = $dealProjectCompoundModel->insert();
                if(!$deal_project_compound_res){
                    throw new \Exception("添加通知贷项目信息失败, firstp2p_deal_project_compound");
                } 
                $dealCompoundModel = new DealCompoundModel();
                $dealCompoundModel->lock_period       = $deal_data['lock_period'];
                $dealCompoundModel->redemption_period = $deal_data['redemption_period'];
                $dealCompoundModel->deal_id           = $dealId;
                $dealCompoundModel->create_time       = get_gmtime();
                $dealCompoundModel->update_time       = get_gmtime();
                $dealCompoundModel->end_date          = 0;
                $dealCompoundModel->rate_day          = 0;
                $deal_compound_res = $dealCompoundModel->insert();
                if(!$deal_compound_res){
                    throw new \Exception("添加通知贷标的信息失败, firstp2p_deal_compound");
                }
            }
            
            /*合同服务*/
            $rpc = new Rpc('contractRpc');
            $contractRequest = new RequestSetDealCId();
            $contractRequest->setDealId($dealId);
            $contractRequest->setCategoryId(intval($deal_data['contract_tpl_type']));
            $contractRequest->setType(0);
            $contractRequest->setSourceType($deal_data['deal_type']);
            $contractResponse = $rpc->go("\NCFGroup\Contract\Services\Category","setDealCId",$contractRequest);
            if($contractResponse->status != true){
                throw new \Exception("合同服务调用失败：".$contractResponse->errorCode.":".$contractResponse->errorMsg);
            }

            \FP::import("app.deal");
            if($deal_site_statu = update_deal_site($dealId, array(5)) == true){
                $rs = $GLOBALS['db']->commit();
                if(empty($rs)){
                    throw new \Exception('提交事务失败');
                }

                $res = true;
            }
        }catch (\Exception $ex){
            $msg_title = ($deal_data['deal_type'] == 1) ? 'openapi addProjectInfoCompound && deal errors' : 'openapi addProjectInfo && addDeal errors';
            \libs\utils\Logger::debug($msg_title . '|' . $ex->getMessage());
            $GLOBALS['db']->rollback();
        }

        if($res === true){
            $deal_service = new \core\service\DealService();
            $deal_service->initDeal($dealId);
            return $dealId;
        }

        return false;
    }

}

