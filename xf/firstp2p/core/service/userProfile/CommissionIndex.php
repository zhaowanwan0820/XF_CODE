<?php

namespace core\service\userProfile;
use core\service\CouponBindService;
use core\dao\UserProfileModel;

class CommissionIndex extends UserProfile{

    public function process($userId){
        $ret = $this->calcDataByUserId($userId);
        $data = array(
            "cur_refere_user_id"=>$ret['refer_user_id'],
            "cur_commission"=>$ret['cur_commission'],
            "cur_paid_commission"=>$ret['cur_paid_rebate_money'],
            "cur_no_paid_commission"=>$ret['cur_no_paid_rebate_money'],
            "cur_invest_money" => $ret['cur_invest_money'],
            "cur_annual_invest" => $ret['cur_annual_invest'],
        );
        return $data;
    }

    public function newProfit($userId,$money,$isTzd=false){
        if(empty($money)){
            // 无资金变化，直接认为更新成功
            return true;
        }
        // 通知带不会有佣金的变化,只有结算的时候才会有
        if($isTzd == true){
            return true;
        }
        // 用户存在就更新，要不就插入
        $exist = $this->userDataExist($userId);
        if($exist){
            $data['cur_commission'] = 'cur_commission+'.$money;
            $data['cur_no_paid_commission'] = 'cur_no_paid_commission+'.$money;
            $ret = \core\dao\UserProfileModel::instance()->updateRecord($userId,$data);
        }else{
            $data['cur_commission'] = $money;
            $data['cur_no_paid_commission'] = $money;
            $ret = \core\dao\UserProfileModel::instance()->addNewRecord($userId,$data);
        }
        if(!empty($ret)){
            return true;
        }else{
            return false;
        }
    }

    public function payCommission($userId,$money,$isTzd=false){
        if(empty($money)){
            // 无资金变化，直接认为更新成功
            return true;
        }
        // 用户存在就更新，要不就插入
        $exist = $this->userDataExist($userId);
        if($exist){
            if($isTzd == false){
                // 通知带只有已反没有带反
                $data['cur_no_paid_commission'] = 'cur_no_paid_commission-'.$money;
            }
            $data['cur_paid_commission'] = 'cur_paid_commission+'.$money;
            $data['cur_commission'] = 'cur_commission+'.$money;
            $ret = \core\dao\UserProfileModel::instance()->updateRecord($userId,$data);
        }else{
            if($isTzd == false){
                $data['cur_no_paid_commission'] = -1*$money;
            }
            $data['cur_paid_commission'] = $money;
            $data['cur_commission'] = $money;
            $ret = \core\dao\UserProfileModel::instance()->addNewRecord($userId,$data);
        }
        if(!empty($ret)){
            return true;
        }else{
            return false;
        }
    }

    public function calcDataByUserId($userId){
        $cps = new CouponBindService();
        $referUserInfo = $cps->getByUserId($userId);
        if(empty($referUserInfo) || empty($referUserInfo['refer_user_id'])){
            return array(
                'refer_user_id'=>0,
                'cur_paid_rebate_money' => 0,
                'cur_no_paid_rebate_money' => 0,
                'cur_commission' => 0,
                'cur_invest_money' => 0,
                'cur_annual_invest' => 0,
            );
        }
        $ret['refer_user_id'] = $referUserInfo['refer_user_id'];
        $normalPaid = $this->normalDealPaidCommission($userId,$ret['refer_user_id']);
        $tzdPaid = $this->tzdDealPaidCommission($userId,$ret['refer_user_id']);
        $ret['cur_paid_rebate_money'] = $normalPaid + $tzdPaid;
        $ret['cur_no_paid_rebate_money'] = $this->normalDealNoPaidCommission($userId,$ret['refer_user_id']);
        $ret['cur_commission'] = $ret['cur_paid_rebate_money'] + $ret['cur_no_paid_rebate_money'];
        $ret['cur_invest_money'] = $this->curInviterInvest($userId,$ret['refer_user_id']);
        $ret['cur_annual_invest'] = $this->curAnnualInvest($userId,$ret['refer_user_id']);
        return $ret;

    }

    /**
    * 普通标的已反佣金
    */
    public function normalDealPaidCommission($userId,$referUserId){
        try{
            $sql = "SELECT SUM(referer_rebate_amount+referer_rebate_ratio_amount) AS paid_rebate_money
                FROM firstp2p_coupon_log WHERE refer_user_id='{$referUserId}' AND consume_user_id ='{$userId}' AND pay_status in (1,2) AND deal_type!='1'";
            $ret = \libs\db\Db::getInstance('firstp2p', 'vipslave')->getRow($sql);
            if( !empty($ret['paid_rebate_money']) )
                return $ret['paid_rebate_money'];
            else
                return 0;
        }catch(\Exception $e){
            return 0;
        }
    }
    /**
    * 普通标的未反佣金
    */
    public function normalDealNoPaidCommission($userId,$referUserId){
        try{
            $sql = "SELECT SUM(referer_rebate_amount+referer_rebate_ratio_amount) AS paid_rebate_money
                FROM firstp2p_coupon_log WHERE refer_user_id='{$referUserId}' AND consume_user_id ='{$userId}' AND pay_status in (0,3,4) AND deal_type!='1' AND deal_status!=2";
            $ret = \libs\db\Db::getInstance('firstp2p', 'vipslave')->getRow($sql);
            if( !empty($ret['paid_rebate_money']) )
                return $ret['paid_rebate_money'];
            else
                return 0;
        }catch(\Exception $e){
            return 0;
        }
    }

    /**
    * 通知贷已反佣金
    */
    public function tzdDealPaidCommission($userId,$referUserId){
        try{
            $sql = "SELECT SUM(referer_rebate_amount+referer_rebate_ratio_amount ) AS paid_rebate_money
                FROM firstp2p_coupon_pay_log WHERE refer_user_id='{$referUserId}' AND consume_user_id ='{$userId}'";
            $ret = \libs\db\Db::getInstance('firstp2p', 'vipslave')->getRow($sql);
            if( !empty($ret['paid_rebate_money']) )
                return $ret['paid_rebate_money'];
            else
                return 0;
        }catch(\Exception $e){
            return 0;
        }
    }

    /**
    * 投资金额
    */
    public function curInviterInvest($userId,$referUserId){
        try{
            $sql = "SELECT SUM(deal_load_money) AS to_invester_money
                FROM firstp2p_coupon_log WHERE refer_user_id='{$referUserId}' AND consume_user_id ='{$userId}' AND deal_status !=2";
            $ret = \libs\db\Db::getInstance('firstp2p', 'vipslave')->getRow($sql);
            if( !empty($ret['to_invester_money']) )
                return $ret['to_invester_money'];
            else
                return 0;
        }catch(\Exception $e){
            return 0;
        }
    }



    /**
    * 投资年华
    */
    public function curAnnualInvest($userId,$referUserId){
        try{
            $sql = "SELECT
                SUM(
                    CASE
                        WHEN d.loantype in (1,2,8) THEN cl.deal_load_money*d.repay_time*0.56/12
                        WHEN d.deal_type!=1 AND d.loantype=5 THEN cl.deal_load_money*d.repay_time/360
                        WHEN d.deal_type!=1 THEN cl.deal_load_money*d.repay_time/12
                        WHEN d.deal_type=1 THEN 10*cl.deal_load_money/360
                        ELSE 0
                    END
                ) as cur_annual_invest
                From firstp2p_coupon_log as cl LEFT JOIN firstp2p_deal as d ON cl.deal_id = d.id AND d.deal_status !=3 AND d.loantype != 7 AND d.is_delete =0 AND d.parent_id!=0
                WHERE cl.refer_user_id='{$referUserId}' AND cl.consume_user_id ='{$userId}' AND cl.deal_status !=2";

            $ret = \libs\db\Db::getInstance('firstp2p', 'vipslave')->getRow($sql);
            if( !empty($ret['cur_annual_invest']) )
                return $ret['cur_annual_invest'];
            else
                return 0;
        }catch(\Exception $e){
            return 0;
        }
    }
}

?>
