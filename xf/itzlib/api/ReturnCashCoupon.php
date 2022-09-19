<?php
/**
 * 提现返券
 */
class ReturnCashCoupon extends ItzApi{
    public $logcategory = "return.cash.coupon";

    public function  run($user_id,$cash_money_extra){ 
		#参数验证
        if (empty($user_id) || !is_numeric($user_id) || $user_id <= 0) {
            Yii::log("ReturnCashCoupon: user_id=$user_id;", "warning", $this->logcategory);
            $this->code = 2003;
            return $this;
        }
        #提现手续费需要大于0才能发券
        if (FunctionUtil::float_bigger_equal(0, $cash_money_extra, 2)) {
            Yii::log("ReturnCashCoupon: cash_money_extra=$cash_money_extra;", "warning", $this->logcategory);
            $this->code = 1003;
            return $this;
        }
        
        #拆分金额
        $num_50 = intval($cash_money_extra/50);
        $rest = (int)ceil($cash_money_extra-($num_50*50));
        $tmp = array(
            array('num'=>$num_50,'amount'=>50),
            array('num'=>1,'amount'=>$rest)
        );
        
        #发券
        foreach ($tmp as  $v) {
            $result = $this->sendCoupon($user_id,$v['amount'],$v['num']);
            if (!$result) {
                Yii::log('ReturnCashCoupon Error user_id:'.$user_id.'couponCode:cashCoupn'.',amount:'.$v['amount'].',num:'.$v['num'],"error",$this->logcategory);
                $this->code = 5012;
                return $this;
            } 
        }
        $this->code = 0;
        return $this;
    }
   
   /**
    * [发券]
    * @param  [int]   $amount    [description]
    * @param  [int]   $invest_amount [description]
    * @param  [int]   $num       [description]
    * @return [bool]  true/false [description]
    */
    public function sendCoupon($user_id,$amount,$num=0){
        for ($i=0; $i < $num; $i++) {
            $affix['amount'] = $amount;
            $affix['invest_amount'] = $amount/0.005;
            $result = CouponSlotClass::getInstance()->couponSlot($user_id, 'TXQ', $affix);
            if (!$result) {
                return false;
            }
        }
        return true;
    }
}