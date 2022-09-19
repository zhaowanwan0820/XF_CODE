<?php
namespace core\service\repay;


use core\dao\repay\DealRepayModel;
use core\enum\DealRepayEnum;
use core\service\BaseService;
use core\service\user\UserService;
use core\dao\deal\DealAgencyModel;
use core\service\deal\DealRepayAccountService;

class RepayBaseService extends BaseService {

    /**
     * 获取标的所有可还款账户信息
     * @param DealModel $deal
     * @return array
     */
    public function getAllRepayAccountInfo(DealModel $deal){

        // 借款人
        $borrowUser = UserService::getUserById($deal['user_id']);
        $repayUser[] = array('userName' => $borrowUser['real_name'],'type'=> DealRepayEnum::DEAL_REPAY_TYPE_SELF);

        // 代垫机构
        if($deal['advance_agency_id'] > 0){
            $advance_agency = DealAgencyModel::instance()->find($deal['advance_agency_id']);
            $repayUser[] = array('userName' => $advance_agency['short_name'] == '' ? $advance_agency['name']:$advance_agency['short_name'],'type'=> DealRepayEnum::DEAL_REPAY_TYPE_DAIDIAN);
        }

        // 担保机构(直接代偿、间接代偿)
        if($deal['agency_id'] > 0){
            $advance_agency = DealAgencyModel::instance()->find($deal['agency_id']);
            //代偿机构
            $repayUser[] = array('userName' => $advance_agency['short_name'] == '' ? $advance_agency['name']:$advance_agency['short_name'],'type'=> DealRepayEnum::DEAL_REPAY_TYPE_DAICHANG);
            //间接代偿机构 去掉间接代偿
            //$repayUser[] = array('userName' => '间接代偿' . ($advance_agency['short_name'] == '' ? $advance_agency['name']:$advance_agency['short_name']),'type'=> DealRepayEnum::DEAL_REPAY_TYPE_JIANJIE_DAICHANG,);
        }

        //代充值机构
        if($deal['generation_recharge_id'] > 0){
            $generation_recharge = DealAgencyModel::instance()->find($deal['generation_recharge_id']);
            $repayUser[] = array('userName' => $generation_recharge['short_name'] == '' ? $generation_recharge['name']:$generation_recharge['short_name'],'type'=> DealRepayEnum::DEAL_REPAY_TYPE_DAICHONGZHI);
        }
        return $repayUser;
    }

    /**
     * 计算标的的最近一次还款时间（开始计息时间）
     * @param $deal
     * @return mixed
     */
    public function getMaxRepayTimeByDeal($deal) {
        $res = DealRepayModel::instance()->getMaxRepayTimeByDealId($deal['id']);
        if(!$res->repay_time){
            $interest_time = $deal['repay_start_time'];
        }else{
            $interest_time = $res['repay_time'];
        }
        return $interest_time;
    }
}