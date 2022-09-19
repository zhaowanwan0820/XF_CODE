<?php

namespace core\service\userProfile;
use core\dao\UserProfileModel;
use core\service\CouponBindService;
use core\dao\Model;

class InvestIndex extends UserProfile{

    public function process($userId){
        $ret = $this->calcDataByUserId($userId);
        $data = array();
        $data["all_invest"] = empty($ret['all_invest'])?0:$ret['all_invest'];
        $data["invest_count"] = empty($ret['invest_count'])?0:$ret['invest_count'];
        $data["money_m_rate"] = empty($ret['money_m_rate'])?0:$ret['money_m_rate'];
        $data["money_m_time"] = empty($ret['money_m_time'])?0:$ret['money_m_time'];
        $data["all_period"] = empty($ret['all_period'])?0:$ret['all_period'];
        $data["annual_invest"] = empty($ret['annual_invest'])?0:$ret['annual_invest'];
        return $data;
    }

    public function newInvest($newInvest){
        if(empty($newInvest['money'])){
            // 无资金变化，直接认为更新成功
            return true;
        }
        $dealService = new \core\service\DealService();
        $dealInfo = $dealService->getDeal($newInvest['deal_id'], true, false);
        $loanType = intval($dealInfo['loantype']);
        $repayTime = $dealInfo['repay_time'];
        $rate = $dealInfo['rate'];
        $dealType = $dealInfo['deal_type'];
        $userId = $newInvest['user_id'];
        if(empty($dealInfo)){
            // 标的信息无效，直接认为更新成功
            return true;
        }
        // 用户存在就更新，要不就插入
        $exist = $this->userDataExist($newInvest['user_id']);
        $data = array();
        $cur_annual_invest = 0;


        if($dealType == 0){
            $cur_annual_invest = $loanType==5?($newInvest['money']*$repayTime/360):($newInvest['money']*$repayTime/12);
            $loadTime = $loanType==5?$repayTime:$repayTime*30;
        }else{
            $cur_annual_invest = $newInvest['money']*10/360;
            $loadTime = 10;
        }
        if( in_array($loanType,array(1,2,8)) ){
            $cur_annual_invest *= 0.56;
        }
        if($exist){
            $op = '+';
            if($newInvest['money'] < 0){
                $op = '-';
            }
            // 更新的时候顺便可以更新当前的
            $data['all_invest'] = 'all_invest'.$op.$newInvest['money'];
            $data['invest_count'] = 'invest_count'.$op.'1';
            $data['annual_invest'] = 'annual_invest'.$op.$cur_annual_invest;
            $data['money_m_time'] = 'money_m_time'.$op.($newInvest['money']*$loadTime);
            $data['money_m_rate'] = 'money_m_rate'.$op.($newInvest['money']*$rate);
            $data['all_period'] = 'all_period'.$op.$loadTime;
            $data['cur_invest_money'] = 'cur_invest_money'.$op.$newInvest['money'];
            $data['cur_annual_invest'] = 'cur_annual_invest'.$op.$cur_annual_invest;
            $data['all_period'] = 'all_period'.$op.$loadTime;
            $ret = \core\dao\UserProfileModel::instance()->updateRecord($userId,$data);
        }else{
            // 首次进来更新的把邀请人id也弄进来
            $cps = new CouponBindService();
            $referUserInfo = $cps->getByUserId($userId);
            if(empty($referUserInfo) || empty($referUserInfo['refer_user_id'])){
                $data['refer_user_id'] = 0;
            }else{
                $data['refer_user_id'] = $referUserInfo['refer_user_id'];
            }

            $data['all_invest'] = $newInvest['money'];
            $data['invest_count'] = 1;
            $data['annual_invest'] = $cur_annual_invest;
            $data['money_m_time'] = $newInvest['money']*$loadTime;
            $data['money_m_rate'] = $newInvest['money']*$rate;
            $data['all_period'] = $loadTime;
            $data['cur_invest_money'] = $newInvest['money'];
            $data['cur_annual_invest'] = $cur_annual_invest;
            $ret = \core\dao\UserProfileModel::instance()->addNewRecord($userId,$data);
        }
        if(!empty($ret)){
            return true;
        }else{
            return false;
        }
    }

    private function calcDataByUserId($userId){
        try{
            $sql = "SELECT COUNT(*) AS invest_count,SUM(dl.money) AS all_invest,
                SUM(
                        CASE
                        WHEN d.loantype in (1,2,8) THEN dl.money*d.repay_time*0.56/12
                        WHEN d.deal_type!=1 AND d.loantype=5 THEN dl.money*d.repay_time/360
                        WHEN d.deal_type!=1 THEN dl.money*d.repay_time/12
                        WHEN d.deal_type=1 THEN 10*dl.money/360
                        ELSE 0
                        END
                   ) as annual_invest,
                SUM(
                        CASE
                        WHEN d.deal_type!=1 AND d.loantype=5 THEN d.repay_time
                        WHEN d.deal_type!=1 THEN d.repay_time*30
                        WHEN d.deal_type=1 THEN 10
                        ELSE 0
                        END
                   ) as all_period,
                SUM(
                        CASE
                        WHEN d.deal_type!=1 AND d.loantype=5 THEN dl.money*d.repay_time
                        WHEN d.deal_type!=1 THEN dl.money*d.repay_time*30
                        WHEN d.deal_type=1 THEN dl.money*10
                        ELSE 0
                        END
                ) as money_m_time,
                SUM(dl.money*d.rate) as money_m_rate
                    FROM `firstp2p_deal` AS d  LEFT JOIN `firstp2p_deal_load` AS dl
                    ON dl.deal_id = d.id WHERE d.deal_status!=3 AND d.is_delete =0 AND d.parent_id!=0 AND dl.user_id = {$userId}";
            $ret = \libs\db\Db::getInstance('firstp2p', 'vipslave')->getRow($sql);
            return $ret;
        }catch(\Exception $e){
            return array();
        }
    }


}

?>
