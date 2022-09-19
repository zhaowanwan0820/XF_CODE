<?php
/**
 * @file UserAccountService.php
 * @author (zhaowanwan@xxx.com)
 * @date 2016/9/18
 * 用户资金名词计算service
 **/
class UserAccountService extends  ItzInstanceService
{
	private $isNovice = 0;
	private $tenderAddTime = 0;

	/**
     * 获取用户现有资产
     * @param $user_id int 用户ID
     * @return array
     */
	public function getUserAssets($user_id){
        $returnResult = array(
            'code' => '', 'data' => 0.00, 'info' => ''
        );
        if (!isset($user_id) || $user_id == '') {
            $returnResult['code'] = 2000;
            $returnResult['info'] = Yii::app()->c->codeConfig[2000];
            return $returnResult;
        }
        //用户可用余额+冻结资金+冻结奖励资金
        $criteria = new CDbCriteria;
        $criteria->select = 'sum(use_money + no_use_money + no_use_virtual_money) as use_money';
        $criteria->condition = " user_id=:user_id ";
        $criteria->params[':user_id'] = $user_id;
        $accountInfo = Account::model()->find($criteria)->attributes;
        if (count($accountInfo) == 0) {
            $returnResult['code'] = 2002;
            $returnResult['info'] = Yii::app()->c->codeConfig[2002];
            return  $returnResult;
        }
        //活期賬戶信息
        $currentInfo = $this->getCurrentAccountInfo($user_id);
        if ($currentInfo['code'] == 0) {
            $returnResult['data'] += $currentInfo['data']['capital'];
        }
        //待收本金和待收利息
        $criteria_c= new CDbCriteria;
        $criteria_c->select = 'sum(capital + interest) as capital';
        $criteria_c->condition = " user_id=:user_id  AND `status` in (0,2,3,5) ";
        $criteria_c->params[':user_id'] = $user_id;
        $collectionInfo = BorrowCollection::model()->find($criteria_c)->attributes;
        if (count($collectionInfo) > 0) {
            $returnResult['data'] += $collectionInfo['capital'];
        }
        // 加上用户正在匹配状态的收益
        // $sql = "select a.*,b.formal_time,b.repayment_time,b.project_duration,b.apr,b.style,b.time_limit from dw_borrow_tender a left join dw_borrow b on a.borrow_id = b.id where a.user_id=:user_id and a.status=0";
        // $matchTenders = Yii::app()->db->createCommand($sql)->bindParam(":user_id",$user_id,PDO::PARAM_STR)->queryAll();
        $matchTenders = BorrowTender::model()->findAllByAttributes(['user_id'=>$user_id,'status'=>0]);
        if(!empty($matchTenders)){
            foreach ($matchTenders as $k => $v) {
                $borrowInfo = Borrow::model()->findByPk($v->borrow_id);
                $returnResult['data'] += $v->interest;
                $returnResult['data'] += $v->account;
                $newTenderCouponDetail = unserialize($v->coupon_detail);
                if( $v->coupon_type==3 ||  $v->coupon_type==4 ){
                    $experience_day = 0;
                    $midtime = strtotime('midnight');
                    if($v->coupon_type == 3) {
                        $experience_day = (int)$newTenderCouponDetail['experience_day'];
                    } else if($v->coupon_type == 4) {
                        $experience_day = (int)$borrowInfo->project_duration;
                    }
                    $interest_reward = DwAccountService::getInstance()->calculateInterestReward($v->account,$v->coupon_value,$experience_day,$newTenderCouponDetail['interest_max_money']);
                    if(FunctionUtil::float_bigger_bc($interest_reward,0)){
                        $returnResult['data'] += $interest_reward;
                    }
                }
            }
        }
        $returnResult['code'] = 0;
        $returnResult['info'] = '获取用户现有资产成功';
        $returnResult['data'] += $accountInfo['use_money'];
        return  $returnResult;

    }
    /**
     * 获取用户活期账户信息
     * @param int $user_id 用户ID
     * @return array
     */
    public function getCurrentAccountInfo($user_id){
        $returnResult = array(
            'code' => '', 'info' => '', 'data' => array()
        );
        if (!isset($user_id) && $user_id == '') {
            $returnResult['code'] = 2000;
            $returnResult['info'] = Yii::app()->c->codeConfig[2000];
            return  $returnResult;
        }
        $currentInfo = DwCurrentAccount::model()->find('user_id=:user_id', array(':user_id' => $user_id))->attributes;
        if (count($currentInfo) == 0) {
            $returnResult['code'] = 2001;
            $returnResult['info'] = Yii::app()->c->codeConfig[2001];
            return  $returnResult;
        }
        $returnResult['code'] = 0;
        $returnResult['data'] = $currentInfo;
        $returnResult['info'] = '用户活期账户信息获取成功';
        return  $returnResult;
    }
    /**
     * 获取用户累计充值信息
     * @param int $user_id 用户ID
     * @return array
     */
    public function getUserRechargeTotal($user_id){
        $returnResult = array(
            'code' => '', 'info' => '', 'data' => 0.00,
        );
        if (!isset($user_id) && $user_id == '') {
            $returnResult['code'] = 2000;
            $returnResult['info'] = Yii::app()->c->codeConfig[2000];
            return  $returnResult;
        }
        $criteria_recharge = new CDbCriteria;
        $criteria_recharge->select = 'sum(money) as money';
        $criteria_recharge->condition = "status=1 AND user_id=:user_id";
        $criteria_recharge->params[':user_id'] = $user_id;
        $rechargeHistory = AccountRecharge::model()->find($criteria_recharge)->attributes;
        if (count($rechargeHistory) == 0) {
            $returnResult['code'] = 2003;
            $returnResult['info'] = Yii::app()->c->codeConfig[2003];
            return  $returnResult;
        }
        $returnResult['code'] = 0;
        $returnResult['data'] += $rechargeHistory['money'];
        $returnResult['info'] = '用户累计充值信息获取成功';
        return  $returnResult;
    }
    /**
     * 获取用户累计提现信息
     * @param int $user_id 用户ID
	 * @param int $type 1减去手续费后的实际到账金额2不包含手续费的金额
     * @return array
     */
    public function getUserWithdrawTotal($user_id, $type = 1){
        $returnResult = array(
            'code' => 0, 'info' => '', 'data' => 0.00,
        );
        if ((!isset($user_id) && $user_id == '') || !in_array($type, array(1, 2))) {
            $returnResult['code'] = 2000;
            $returnResult['info'] = Yii::app()->c->codeConfig[2000];
            return  $returnResult;
        }
        //目前admin后台与老版用户中心累计提现均为用户实际到账金额累计，新版用户中心为用户累计发起提现金额
        $select = ($type == 2) ? 'sum(total) as total' : ' sum(credited) as credited ';
        $criteria_cash = new CDbCriteria;
        $criteria_cash->select = $select;
        $criteria_cash->condition = "status=3 AND user_id=:user_id";
        $criteria_cash->params[':user_id'] = $user_id;
        $cashHistory = AccountCash::model()->find($criteria_cash)->attributes;
        if (count($cashHistory) == 0) {
            $returnResult['code'] = 2004;
            $returnResult['info'] = Yii::app()->c->codeConfig[2004];
            return  $returnResult;
        }
        $withdraw_total = ($type == 2) ? $cashHistory['total'] : $cashHistory['credited'];
        $returnResult['data'] += $withdraw_total;
        $returnResult['info'] = '用户累计提现信息获取成功';
        return  $returnResult;
    }
    /**
     * 获取用户累计提现手续费
     * @param int $user_id 用户ID
     * @return array
     */
    public function getUserWithdrawFee($user_id){
        $returnResult = array(
            'code' => '', 'info' => '', 'data' => 0.00,
        );
        if (!isset($user_id) && $user_id == '') {
            $returnResult['code'] = 2000;
            $returnResult['info'] = Yii::app()->c->codeConfig[2000];
            return  $returnResult;
        }
        $criteria_cash = new CDbCriteria;
        $criteria_cash->select = 'sum(fee) as fee';
        $criteria_cash->condition = "status=3 AND user_id=:user_id";
        $criteria_cash->params[':user_id'] = $user_id;
        $cashHistory = AccountCash::model()->find($criteria_cash)->attributes;
        if (count($cashHistory) == 0) {
            $returnResult['code'] = 2004;
            $returnResult['info'] = Yii::app()->c->codeConfig[2004];
            return  $returnResult;
        }
        $returnResult['code'] = 0;
        $returnResult['data'] += $cashHistory['fee'];
        $returnResult['info'] = '用户累计提现手续费信息获取成功';
        return  $returnResult;
    }
    /**
 * 获取用户可用余额
 * @param $user_id int 用户ID
 * @return array
 */
    public function getUserAvailableMoney($user_id){
        $returnResult = array(
            'code' => '', 'data' => 0.00, 'info' => ''
        );
        if (!isset($user_id) || $user_id == '') {
            $returnResult['code'] = 2000;
            $returnResult['info'] = Yii::app()->c->codeConfig[2000];
            return $returnResult;
        }
        $criteria = new CDbCriteria;
        $criteria->select = 'use_money';
        $criteria->condition = " user_id=:user_id ";
        $criteria->params[':user_id'] = $user_id;
        $accountInfo = Account::model()->find($criteria)->attributes;
        if (count($accountInfo) == 0) {
            $returnResult['code'] = 2002;
            $returnResult['info'] = Yii::app()->c->codeConfig[2002];
            return  $returnResult;
        }
        $returnResult['code'] = 0;
        $returnResult['info'] = '获取用户可用余额成功';
        $returnResult['data'] += $accountInfo['use_money'];
        return  $returnResult;
    }
    /**
     * 获取用户冻结资金
     * @param $user_id int 用户ID
     * @return array
     */
    public function getUserFreezingMoney($user_id){
        $returnResult = array(
            'code' => '', 'data' => 0.00, 'info' => ''
        );
        if (!isset($user_id) || $user_id == '') {
            $returnResult['code'] = 2000;
            $returnResult['info'] = Yii::app()->c->codeConfig[2000];
            return $returnResult;
        }
        $criteria = new CDbCriteria;
        $criteria->select = ' sum(no_use_money + no_use_virtual_money) as no_use_money';
        $criteria->condition = " user_id=:user_id ";
        $criteria->params[':user_id'] = $user_id;
        $accountInfo = Account::model()->find($criteria)->attributes;
        if (count($accountInfo) == 0) {
            $returnResult['code'] = 2002;
            $returnResult['info'] = Yii::app()->c->codeConfig[2002];
            return  $returnResult;
        }
        $returnResult['code'] = 0;
        $returnResult['info'] = '获取用户冻结资金成功';
        $returnResult['data'] += $accountInfo['no_use_money'];
        return  $returnResult;
    }
    /**
     * 获取用户资金账户信息
     * @param $user_id int 用户ID
     * @return array
     */
    public function getUserAccountInfo($user_id){
        $returnResult = array(
            'code' => '', 'data' => array(), 'info' => ''
        );
        if (!isset($user_id) || $user_id == '') {
            $returnResult['code'] = 2000;
            $returnResult['info'] = Yii::app()->c->codeConfig[2000];
            return $returnResult;
        }
        $criteria = new CDbCriteria;
        $criteria->condition = " user_id=:user_id ";
        $criteria->params[':user_id'] = $user_id;
        $accountInfo = Account::model()->find($criteria)->attributes;
        if (count($accountInfo) == 0) {
            $returnResult['code'] = 2002;
            $returnResult['info'] = Yii::app()->c->codeConfig[2002];
            return  $returnResult;
        }
        $returnResult['code'] = 0;
        $returnResult['info'] = '获取用户资金账户信息成功';
        $returnResult['data'] = $accountInfo;
        return  $returnResult;
    }
    /**获取用户优惠券统计的张数和总额（默认已使用的）
     * @param int type 优惠券类型：1-投资卷,2-抵现卷 3-加息券
     * @param int $user_id 用户ID
     * @param int $status_remark优惠券状态标识1可使用2已使用3已过期4未使用（未生效&可使用） 状态标识与库里的status无关
     */
    public function getUserCoupon($user_id, $type, $status_remark = 2){
        $returnResult = array(
            'code' => 0, 'info' => '', 'data' => array(
                                                    'num'=>0,
                                                    'amount' => 0.00,
                                                ));
        if (!isset($user_id) || $user_id == '' || !isset($type) || $type == '') {
            $returnResult['code'] = 2000;
            $returnResult['info'] = Yii::app()->c->codeConfig[2000];
            return $returnResult;
        }
        //优惠券条件
        $condition = ' user_id=:user_id ';
        $condition .= $this->getCouponCondition($status_remark);
        $condition .= is_array($type) ? " and type in (" . implode(',', $type) . ")" : " and type = {$type}";
        $criteria = new CDbCriteria;
        $criteria->select = 'sum(amount) as amount, count(id) as id ';
        $criteria->condition = $condition;
        $criteria->params[':user_id'] = $user_id;
        $couponInfo = Coupon::model()->find($criteria);
        if ($couponInfo['id'] == 0) {
            $returnResult['code'] = 2005;
            $returnResult['info'] = Yii::app()->c->codeConfig[2005];
            return $returnResult;
        }
        $coupon['num'] = $couponInfo->id;
        $coupon['amount'] = $couponInfo->amount;
        $returnResult['code'] = 0;
        $returnResult['data'] = $coupon;
        $returnResult['info'] = '获取用户优惠券信息成功';
        return  $returnResult;
    }
    /**
     * 根据状态标识获取优惠券的查询条件
     * @param int $status_remark优惠券状态标识1可使用2已使用3已过期4未使用（未生效&可使用） 状态标识与库里的status无关
     */
    public function  getCouponCondition($status_remark){
        $condition = '';
        if (!isset($status_remark) || $status_remark == ''){
            return $condition;
        }
        //优惠券使用状态条件
        if ($status_remark == 1) {//1可使用
            $condition .= " and status in (0,1) AND expire_time >= " . time() . " AND  begin_time <= " . time() ;
        } elseif ($status_remark == 2) {//2已使用
            $condition .= " and status = 2 and new_tender_id > 0 ";
        } elseif ($status_remark == 3) {//3已过期
            $condition .= " and expire_time <  " . time() . "  and status in (0,1,3) ";
        } elseif ($status_remark == 4) {//4未使用（未生效&可使用）
            $condition .= " and  ((status in (0,1) AND expire_time >= " . time() . " AND  begin_time <= " . time() . ") or ( begin_time > " . time() . "))";
        }
        return $condition;
    }
    /**
     * 获取用户可用积分
     * @param $user_id int 用户ID
     * @return array
     */
    public function getUserAvailableCredit($user_id){
        $returnResult = array(
            'code' => '', 'data' => 0, 'info' => ''
        );
        if (!isset($user_id) || $user_id == '') {
            $returnResult['code'] = 2000;
            $returnResult['info'] = Yii::app()->c->codeConfig[2000];
            return $returnResult;
        }
        $criteria = new CDbCriteria;
        $criteria->condition = " user_id=:user_id ";
        $criteria->params[':user_id'] = $user_id;
        $accountInfo = Credit::model()->find($criteria)->attributes;
        if (count($accountInfo) == 0) {
            $returnResult['code'] = 2006;
            $returnResult['info'] = Yii::app()->c->codeConfig[2006];
            return  $returnResult;
        }
        $returnResult['code'] = 0;
        $returnResult['info'] = '获取用户可用积分信息成功';
        $returnResult['data'] = $accountInfo['value'];
        return  $returnResult;
    }
    /**
     * 获取用户积分信息
     * @param $user_id int 用户ID
     * @return array
     */
    public function getUserCredit($user_id){
        $returnResult = array(
            'code' => '', 'data' => 0, 'info' => ''
        );
        if (!isset($user_id) || $user_id == '') {
            $returnResult['code'] = 2000;
            $returnResult['info'] = Yii::app()->c->codeConfig[2000];
            return $returnResult;
        }
        $criteria = new CDbCriteria;
        $criteria->condition = " user_id=:user_id ";
        $criteria->params[':user_id'] = $user_id;
        $accountInfo = Credit::model()->find($criteria)->attributes;
        if (count($accountInfo) == 0) {
            $returnResult['code'] = 2006;
            $returnResult['info'] = Yii::app()->c->codeConfig[2006];
            return  $returnResult;
        }
        $returnResult['code'] = 0;
        $returnResult['info'] = '获取用户可用积分信息成功';
        $returnResult['data'] = $accountInfo;
        return  $returnResult;
    }
    /**
     * 获取用户待收本金
     * @param $user_id int 用户ID
     * @return array
     */
    public function getUserUnreceivedCapital($user_id){
        $returnResult = array(
            'code' => '', 'data' => 0.00, 'info' => ''
        );
        if (!isset($user_id) || $user_id == '') {
            $returnResult['code'] = 2000;
            $returnResult['info'] = Yii::app()->c->codeConfig[2000];
            return $returnResult;
        }
        $criteria = new CDbCriteria;
        $criteria->select = ' sum(capital) as capital';
        $criteria->condition = " user_id=:user_id  AND `status` in (0,2,3,5,16) AND type != 5 ";
        $criteria->params[':user_id'] = $user_id;
        $collectionInfo = BorrowCollection::model()->find($criteria)->attributes;
        if ( !empty($collectionInfo['capital']) )  {
            $returnResult['data'] = $collectionInfo['capital'];
        }
        // 加上用户正在匹配状态的收益
        /*
       $matchTenders = BorrowTender::model()->findAllByAttributes(['user_id'=>$user_id,'status'=>0]);
       // $sql = "select a.* from dw_borrow_tender a where a.user_id=:user_id and a.status=0";
       // $matchTenders = Yii::app()->db->createCommand($sql)->bindParam(":user_id",$user_id,PDO::PARAM_STR)->queryAll();
       // // var_dump($matchTenders);die;
       if(!empty($matchTenders)){
           foreach ($matchTenders as $k => $v) {
               $returnResult['data'] += $v->account;
           }
       }
       //智选计划的再投金额

       $plans = BorrowTender::model()->findAllByAttributes(['debt_type'=>35,"user_id"=>$user_id,"status"=>1]);
       foreach($plans as $t) {
           $returnResult['data'] += $t->account;
       }*/
        $returnResult['code'] = 0;
        $returnResult['info'] = '获取用户待收本金成功';
        return  $returnResult;
    }
    /**
     * 获取用户待收利息
     * @param $user_id int 用户ID
     * @return array
     */
    public function getUserUnreceivedInterest($user_id){
        $returnResult = array(
            'code' => '', 'data' => 0.00, 'info' => ''
        );
        if (!isset($user_id) || $user_id == '') {
            $returnResult['code'] = 2000;
            $returnResult['info'] = Yii::app()->c->codeConfig[2000];
            return $returnResult;
        }
        $criteria = new CDbCriteria;
        $criteria->select = ' sum(interest) as interest';
        $criteria->condition = " user_id=:user_id  AND `status` in (0,2,3,5) AND type != 5 ";
        $criteria->params[':user_id'] = $user_id;
        $collectionInfo = BorrowCollection::model()->find($criteria)->attributes;
        if (count($collectionInfo) > 0) {
            $returnResult['data'] = $collectionInfo['interest'];
        }
        $returnResult['code'] = 0;
        $returnResult['info'] = '获取用户待收利息成功';
        return  $returnResult;
    }
    /**
     * 获取用户待收加息
     * @param $user_id int 用户ID
     * @return array
     */
    public function getUserUnreceivedRewardInterest($user_id){
        $returnResult = array(
            'code' => '', 'data' => 0.00, 'info' => ''
        );
        if (!isset($user_id) || $user_id == '') {
            $returnResult['code'] = 2000;
            $returnResult['info'] = Yii::app()->c->codeConfig[2000];
            return $returnResult;
        }
        $criteria = new CDbCriteria;
        $criteria->select = ' sum(interest) as interest';
        $criteria->condition = " user_id=:user_id  AND `status` in (0,2,3,5) AND type = 5 ";
        $criteria->params[':user_id'] = $user_id;
        $collectionInfo = BorrowCollection::model()->find($criteria)->attributes;
        if (count($collectionInfo) > 0 && $collectionInfo['interest'] > 0) {
            $returnResult['data'] = $collectionInfo['interest'];
        }
        $returnResult['code'] = 0;
        $returnResult['info'] = '获取用户待收加息成功';
        return  $returnResult;
    }

    /**
     * 获取用户加息收益
     * @param $user_id int 用户ID
     * @return array
     */
    public function getUserIncreaseInterestTotal($user_id){
        $returnResult = array(
            'code' => '', 'data' => 0.00, 'info' => ''
        );
        if (!isset($user_id) || $user_id == '') {
            $returnResult['code'] = 2000;
            $returnResult['info'] = Yii::app()->c->codeConfig[2000];
            return $returnResult;
        }
        $criteria = new CDbCriteria;
        $criteria->select = ' sum(repay_yesaccount) as repay_yesaccount';
        $criteria->condition = " user_id=:user_id  AND type = 5 ";
        $criteria->params[':user_id'] = $user_id;
        $collectionInfo = BorrowCollection::model()->find($criteria)->attributes;
        if (count($collectionInfo) > 0 && $collectionInfo['repay_yesaccount'] > 0) {
            $returnResult['data'] = $collectionInfo['repay_yesaccount'];
        }
        $returnResult['code'] = 0;
        $returnResult['info'] = '获取用户加息收益成功';
        return  $returnResult;
    }
    /**
     * 获取用户累计回息
     * @param $user_id int 用户ID
     * @return array
     */
    public function getUserReceivedInterestTotal($user_id){
        $returnResult = array(
            'code' => '', 'data' => 0.00, 'info' => ''
        );
        if (!isset($user_id) || $user_id == '') {
            $returnResult['code'] = 2000;
            $returnResult['info'] = Yii::app()->c->codeConfig[2000];
            return $returnResult;
        }
        $criteria = new CDbCriteria;
        $criteria->select = ' sum(interest) as interest';
        $criteria->condition = " user_id=:user_id  AND status = 1 ";
        $criteria->params[':user_id'] = $user_id;
        $collectionInfo = BorrowCollection::model()->find($criteria)->attributes;
        if (count($collectionInfo) > 0 && $collectionInfo['interest'] > 0) {
            $returnResult['data'] = $collectionInfo['interest'];
        }
        $returnResult['code'] = 0;
        $returnResult['info'] = '获取用户累计回息成功';
        return  $returnResult;
    }
    /**
     * 获取用户累計回本
     * @param $user_id int 用户ID
     * @return array
     */
    public function getUserReceivedCapitalTotal($user_id){
        $returnResult = array(
            'code' => '', 'data' => 0.00, 'info' => ''
        );
        if (!isset($user_id) || $user_id == '') {
            $returnResult['code'] = 2000;
            $returnResult['info'] = Yii::app()->c->codeConfig[2000];
            return $returnResult;
        }
        $criteria = new CDbCriteria;
        $criteria->select = ' sum(repayment_yesaccount - repayment_yesinterest ) as repayment_yesaccount';
        $criteria->condition = " user_id=:user_id ";
        $criteria->params[':user_id'] = $user_id;
        $tenderInfo = BorrowTender::model()->find($criteria)->attributes;
        if (count($tenderInfo) > 0 && $tenderInfo['repayment_yesaccount'] > 0) {
            $returnResult['data'] = $tenderInfo['repayment_yesaccount'];
        }
        $returnResult['code'] = 0;
        $returnResult['info'] = '获取用户累计回本成功';
        return  $returnResult;
    }
    /**
     * 获取用户赚取利息
     * @param $user_id int 用户ID
     * @return array
     */
    public function getUserEarnInterestTotal($user_id){
        $returnResult = array(
            'code' => '', 'data' => 0.00, 'info' => ''
        );
        if (!isset($user_id) || $user_id == '') {
            $returnResult['code'] = 2000;
            $returnResult['info'] = Yii::app()->c->codeConfig[2000];
            return $returnResult;
        }
        //累计定期应收利息
        $criteria = new CDbCriteria;
        $criteria->select = ' sum(interest) as interest';
        $criteria->condition = " user_id=:user_id  AND `status` in (0,1,2,3,5,8) ";
        $criteria->params[':user_id'] = $user_id;
        $collectionInfo = BorrowCollection::model()->find($criteria)->attributes;
        if (count($collectionInfo) > 0 && $collectionInfo['interest'] > 0) {
            $returnResult['data'] += $collectionInfo['interest'];
        }
        //累计活期已收利息
        $criteria_c = new CDbCriteria;
        $criteria_c->select = ' sum(interest) as interest';
        $criteria_c->condition = " user_id=:user_id  AND `status` =1 ";
        $criteria_c->params[':user_id'] = $user_id;
        $currentInfo = ItzCurrentCollection::model()->find($criteria_c)->attributes;
        if (count($currentInfo) > 0 && $currentInfo['interest'] > 0) {
            $returnResult['data'] += $currentInfo['interest'];
        }
        $returnResult['code'] = 0;
        $returnResult['info'] = '获取用户赚取利息成功';
        return  $returnResult;
    }

    /**
     * 获取用户累计债权转出成功对应手续费
     * @param $user_id int 用户ID
     * @return array
     */
    public function getUserSellDebtFeeTotal($user_id){
        $returnResult = array(
            'code' => '', 'data' => 0.00, 'info' => ''
        );
        if (!isset($user_id) || $user_id == '') {
            $returnResult['code'] = 2000;
            $returnResult['info'] = Yii::app()->c->codeConfig[2000];
            return $returnResult;
        }
        $criteria = new CDbCriteria;
        $criteria->select = ' sum(sold_money) as sold_money';
        $criteria->condition = " user_id=:user_id and status in (2,3,4)  ";
        $criteria->params[':user_id'] = $user_id;
        $debtInfo = Debt::model()->find($criteria)->attributes;
        if (count($debtInfo) > 0 && $debtInfo['sold_money'] > 0) {
            $returnResult['data'] = sprintf("%.2f", $debtInfo['sold_money']*0.005);
        }
        $returnResult['code'] = 0;
        $returnResult['info'] = '获取用户累计债权转出成功对应手续费成功';
        return  $returnResult;
    }
    /**
     * 获取用户累计债权转出成功金额（含折让金、手续费）
     * @param $user_id int 用户ID
     * @return array
     */
    public function getUserSellDebtTotal($user_id){
        $returnResult = array(
            'code' => '', 'data' => 0.00, 'info' => ''
        );
        if (!isset($user_id) || $user_id == '') {
            $returnResult['code'] = 2000;
            $returnResult['info'] = Yii::app()->c->codeConfig[2000];
            return $returnResult;
        }
        $criteria = new CDbCriteria;
        $criteria->select = ' sum(money) as money';
        $criteria->condition = " user_id=:user_id and direction = 1 and ( `type` in ('debt_finish','lease_debt_finish','factoring_debt_finish','art_debt_finish','shengxin_debt_finish') or log_type in ('debt_finish')) ";
        $criteria->params[':user_id'] = $user_id;
        $accountLogInfo = AccountLog::model()->find($criteria)->attributes;
        if (!is_null($accountLogInfo['money']) ) {
            $returnResult['data'] += $accountLogInfo['money'];
        }
        $returnResult['code'] = 0;
        $returnResult['info'] = '获取用户累计债权转出成功金额成功';
        return  $returnResult;
    }

    /**
     * 获取用户累计债权投资金额（不含折让金）
     * @param $user_id int 用户ID
     * @return array
     */
    public function getUserDebtInvestTotal($user_id){
        $returnResult = array(
            'code' => '', 'data' => 0.00, 'info' => ''
        );
        if (!isset($user_id) || $user_id == '') {
            $returnResult['code'] = 2000;
            $returnResult['info'] = Yii::app()->c->codeConfig[2000];
            return $returnResult;
        }
        $criteria = new CDbCriteria;
        $criteria->select = ' sum(account) as account';
        $criteria->condition = " user_id=:user_id and status = 2 ";
        $criteria->params[':user_id'] = $user_id;
        $debtTenderInfo = DebtTender::model()->find($criteria)->attributes;
        if (!is_null($debtTenderInfo['account']) ) {
            $returnResult['data'] += $debtTenderInfo['account'];
        }
        $returnResult['code'] = 0;
        $returnResult['info'] = '获取用户累计债权投资金额成功';
        return  $returnResult;
    }

    /**
     * 获取用户累计投资金额（含债权、优惠券）
     * @param $user_id int 用户ID
     * @return array
     */
    public function getUserInvestTotal($user_id){
        $returnResult = array(
            'code' => '', 'data' => 0.00, 'info' => ''
        );
        if (!isset($user_id) || $user_id == '') {
            $returnResult['code'] = 2000;
            $returnResult['info'] = Yii::app()->c->codeConfig[2000];
            return $returnResult;
        }
        //定期产品累计投资
        $criteria = new CDbCriteria;
        $criteria->select = ' sum(account_init) as account_init';
        $criteria->condition = " user_id=:user_id ";
        $criteria->params[':user_id'] = $user_id;
        $borrowTenderInfo = BorrowTender::model()->find($criteria)->attributes;
        if ($borrowTenderInfo['account_init'] > 0) {
            $returnResult['data'] += $borrowTenderInfo['account_init'];
        }
        //活期产品累计投资
        $criteria_c = new CDbCriteria;
        $criteria_c->select = ' sum(money) as money';
        $criteria_c->condition = " user_id=:user_id and status = 2 ";
        $criteria_c->params[':user_id'] = $user_id;
        $currentPurchaseInfo = ItzCurrentPurchase::model()->find($criteria_c)->attributes;
        if ($currentPurchaseInfo['money'] > 0) {
            $returnResult['data'] += $currentPurchaseInfo['money'];
        }
        $returnResult['code'] = 0;
        $returnResult['info'] = '获取用户累计投资金额（含债权、优惠券）成功';
        return  $returnResult;
    }

    /**
     * 获取用户累计直投金额（不含优惠券）
     * @param $user_id int 用户ID
     * @return array
     */
    public function getUserDirectInvestTotal($user_id){
        $returnResult = array(
            'code' => '', 'data' => 0.00, 'info' => ''
        );
        if (!isset($user_id) || $user_id == '') {
            $returnResult['code'] = 2000;
            $returnResult['info'] = Yii::app()->c->codeConfig[2000];
            return $returnResult;
        }
        //直投的type
        $borrow_tender_debt_type_invest = array_keys(Yii::app()->params['borrow_tender_debt_type_invest']);
        $invest_type = implode(',',$borrow_tender_debt_type_invest);
        //定期产品累计投资
        $criteria = new CDbCriteria;
        $criteria->select = ' sum(account_init) as account_init';
        $criteria->condition = " user_id=:user_id and debt_type in  ({$invest_type})";
        $criteria->params[':user_id'] = $user_id;
        $borrowTenderInfo = BorrowTender::model()->find($criteria)->attributes;
        if ($borrowTenderInfo['account_init'] > 0) {
            $returnResult['data'] += $borrowTenderInfo['account_init'];
        }
        //抵现券已使用总金额+投资券已使用总金额
        $couponInfo = $this->getUserCoupon($user_id, array(1,2), 2);
        $returnResult['data'] -= $couponInfo['data']['amount'];
        $returnResult['code'] = 0;
        $returnResult['info'] = '获取用户累计直投金额（不含优惠券）成功';
        return  $returnResult;
    }

    /**
     * 获取用户免费提现额度
     * @param $user_id int 用户ID
     * @return array
     */
    public function withdrawfreeRecharge($user_id){
        $returnResult = array(
            'code' => '', 'data' => 0.00, 'info' => ''
        );
        if (!isset($user_id) || $user_id == '') {
            $returnResult['code'] = 2000;
            $returnResult['info'] = Yii::app()->c->codeConfig[2000];
            return $returnResult;
        }
        //定期产品累计投资
        $accountInfo = $this->getUserAccountInfo($user_id);
        if (FunctionUtil::float_bigger($accountInfo['data']['recharge_amount'], 0)) {
            $rechargeMoney15 = DwAccountService::getInstance()->withdrawfreeRecharge($user_id);
            $returnResult['data'] += $rechargeMoney15;
        }
        $returnResult['data'] += $accountInfo['data']['withdraw_free'];
        $returnResult['code'] = 0;
        $returnResult['info'] = '获取用户免费提现额度成功';
        return  $returnResult;
    }

	/**
	 * 判断用户是否持有智选计划拆分项目
	 * @param $user_id
	 * @return bool
	 */
	public function checkUserIsHaveSplitWisePlan($user_id){

		$wisePlanSplit = Yii::app()->params['wisePlanSplit'];
		if(!$wisePlanSplit['splitStatus'] || !$user_id){
			return false;
		}
		$isHaveSplitTender = ItzWiseTender::model()->findByAttributes(['user_id'=>$user_id,'show_status'=>1]);
		if(!$isHaveSplitTender){
			return false;
		}
		return $wisePlanSplit;
	}

	/**
     * 获取用户待收数据
     * @param $user_id int 用户ID
     * @return array
     */
    public function getUserUnreceivedTotal($user_id){
        $returnResult = array(
            'code' => 0, 'info' => '' , 'data' => array(
                                                    'user_unreceived_capital' => 0.00,
                                                    'user_unreceived_interest' => 0.00,
                                                    'user_unreceived_rewardInterest' => 0.00,
                                                ));
        if (!isset($user_id) || $user_id == '') {
            $returnResult['code'] = 2000;
            $returnResult['info'] = Yii::app()->c->codeConfig[2000];
            return $returnResult;
        }
        $wisePlanSplit = Yii::app()->params['wisePlanSplit'];

        $criteria = new CDbCriteria;
        $criteria->select = ' type, sum(capital) as capital, sum(interest) as interest ';
        $criteria->condition = " user_id=:user_id  and `status` in (0,2,3,5,16) ";
        //拆分过程不计入计划的金额
        if(!$wisePlanSplit['splitStatus']){
            $criteria->condition .= " and related_id is null ";
        }
        $criteria->params[':user_id'] = $user_id;
        $criteria->group = ' type ';
        $borrow_collections = BorrowCollection::model()->findAll($criteria);
        if (count($borrow_collections) > 0) {
            // $returnResult['code'] = 2007;
            // $returnResult['info'] = Yii::app()->c->codeConfig[2007];
            // return $returnResult;
            foreach ($borrow_collections as $key => $val){
                if ($val->type == 5 || $val->type == 7 || $val->type == 8) {
                    //待收加息
                    $returnResult['data']['user_unreceived_rewardInterest'] += $val['interest'];
                    continue;
                }
                //待收本金
                $returnResult['data']['user_unreceived_capital'] += $val['capital'];
                //待收利息
                $returnResult['data']['user_unreceived_interest'] += $val['interest'];
            }
        }

         $wisePlanSplit = Yii::app()->params['wisePlanSplit'];
        if($wisePlanSplit['splitStatus']){
            return $returnResult;
        }

        /***刚刚投资未生成还款计划的****start****/
		$matchTenders = BorrowTender::model()->findAll("user_id=:user_id and status = 0 ", array(":user_id"=>$user_id));
        if(empty($matchTenders)){
			foreach ($matchTenders as $k => $v) {
				$borrowInfo = Borrow::model()->findByPk($v->borrow_id);
				$returnResult['data']['user_unreceived_interest'] += $v->interest;
				$returnResult['data']['user_unreceived_capital'] += $v->account;
				$newTenderCouponDetail = unserialize($v->coupon_detail);
				if( $v->coupon_type==3 ||  $v->coupon_type==4 ){
					$experience_day = 0;
					if($v->coupon_type == 3) {
						$experience_day = (int)$newTenderCouponDetail['experience_day'];
					} else if($v->coupon_type == 4) {
						$experience_day = (int)$borrowInfo->project_duration;
					}
					$interest_reward = DwAccountService::getInstance()->calculateInterestReward($v->account,$v->coupon_value,$experience_day,$newTenderCouponDetail['interest_max_money']);
					if(FunctionUtil::float_bigger_bc($interest_reward,0)){
						$returnResult['data']['user_unreceived_rewardInterest'] += $interest_reward;
					}
				}
			}
        }
        

		/***刚刚投资未生成还款计划的****end****/



		/*****以下在智选计划拆分后就不需要了******/
		$matchTenders = BorrowTender::model()->findAll("user_id=:user_id and status = 1 and debt_type = 35 ", array(":user_id"=>$user_id));
		if(empty($matchTenders)){
			$returnResult['info'] = '获取用户待收数据成功';
			return  $returnResult;
		}
		foreach ($matchTenders as $k => $v) {
			$this->tenderAddTime = $v->addtime;
			$this->isNovice = 0;
			if($v->extra_reward_type==2){
				$this->isNovice = $v->id;
			}
			$interestDays = $this->getDays($v->addtime);
			$returnResult['data']["user_unreceived_capital"] += $v->account;

			$extra_reward = unserialize($v->extra_reward);
			//加息券
			$coupon_reward = unserialize($v->coupon_detail);
			//利息
			$interest = $this->getTenderInterest($interestDays,$v->account);

			//平台加息
			$reward1 = $extra_reward&&$extra_reward["duration"]>=$interestDays?DwAccountService::getInstance()->calculateInterestReward($v->account_init,$extra_reward["apr"],$interestDays,$extra_reward['account_limit']):0;
			//券加息
			$reward2 = $coupon_reward&&$coupon_reward["experience_day"]>=$interestDays?DwAccountService::getInstance()->calculateInterestReward($v->account_init,$coupon_reward["coupon_value"],$interestDays,$coupon_reward['interest_max_money']):0;
			//新手预约奖励
			$reward3=0;
			if($this->isNovice){
				//TODO 时间比较
				$config = Yii::app()->c->linkconfig["zxjh_config"];
				$interest = $this->getNewNoviceTenderInterest($interestDays,$v->account,4,$config['novice_borrow_apr']);
				//平台加息退出发
				$isSendBorrowRewards = ItzBorrowReward::model()->findByAttributes(['type'=>1,'user_id'=>$user_id,'tender_id'=>$this->isNovice,'status'=>1,'novice_project'=>1]);
				$reward1 = $isSendBorrowRewards?0:($extra_reward?DwAccountService::getInstance()->calculateInterestReward($v->account_init,$extra_reward["apr"],$extra_reward["duration"]>=$interestDays?$interestDays:$extra_reward["duration"],$extra_reward['account_limit']):0);
			}
			if($v->reserve_reward>0){
				$is_send = false;
				$reserves = ItzBorrowReward::model()->findByAttributes(['type'=>2,'tender_id'=>$v->id]);
				if(!$reserves){
					$is_send = true;
				}
				if($reserves->status == 1 ){
					$is_send = true;
				}
				$reward3 = $is_send?0:$v->reserve_reward;
			}
			$returnResult['data']['user_unreceived_interest'] += $interest;
			$returnResult['data']["user_unreceived_rewardInterest"] += $reward1+$reward2+$reward3;
		}

		$returnResult['info'] = '获取用户待收数据成功';
        return  $returnResult;
    }

    private function getNewNoviceTenderInterest($days,$money,$precision = 2,$now_apr=0)
    {
        $interest = ($now_apr/100 * $money * $days)/365;
		return round($interest,$precision);
    }

    /**
     * 获取用户已赚利息
     * @param $user_id int 用户ID
     * @return array
     */
    public function getUserInterestTotal($user_id){
        $returnResult = array(
            'code' => 0, 'info' => '', 'data' => array(
                                                    'user_invest_interest_total'=>0.00,
                                                    'increase_interest_total'=>0.00,
                                                ));
        if (!isset($user_id) || $user_id == '') {
            $returnResult['code'] = 2000;
            $returnResult['info'] = Yii::app()->c->codeConfig[2000];
            return $returnResult;
        }
		//新手预约奖励
		$sql = "select sum(account) as account from itz_borrow_reward where type=2 and novice_project=1 and status = 1 and user_id=$user_id";
		$reserveAccount = ItzBorrowReward::model()->findBySql($sql);
		$returnResult['data']['increase_interest_total'] += $reserveAccount['account']?:0;
        //累计定期已收利息
        $criteria = new CDbCriteria;
        $criteria->select = ' interest, repay_yesaccount, quit_fee,type,status,repay_type';
        $criteria->condition = " user_id=:user_id and status in (1,8,16)";
        $criteria->params[':user_id'] = $user_id;
        $collectionInfo = BorrowCollection::model()->findAll($criteria);
        if (count($collectionInfo) == 0){
            $returnResult['code'] = 2007;
            $returnResult['info'] = Yii::app()->c->codeConfig[2007];
            return $returnResult;
        }
        foreach ($collectionInfo as $key => $val){
            //加息收益
            if ($val->type == 5 || $val->type == 7 || $val->type == 8) {
                $returnResult['data']['increase_interest_total'] += $val['repay_yesaccount'];
                continue;
            }
            //已收利息统计status值为1和8
			//todo collection新增字段，区别本金、利息
			$returnResult['data']['user_invest_interest_total'] += $val->repay_type==2?$val['repay_yesaccount']:$val['interest'];

			if($val['quit_fee'] > 0 ){
				$returnResult['data']['user_invest_interest_total'] += $val['quit_fee'];
			}

        }
		//智选计划拆分后的以赚利息
		$wisePlanSplit = $this->checkUserIsHaveSplitWisePlan($user_id);
		if($wisePlanSplit){
			$criteria = new CDbCriteria;
			$criteria->select = 'sum(interest) as interest ';
			$criteria->condition = " user_id=:user_id  AND  status = 1 AND repay_yestime >= {$wisePlanSplit['splitEndTime']} ";
			$criteria->params[':user_id'] = $user_id;
			$returnResult['data']['user_invest_interest_total'] += ItzWisePlanCollection::model()->find($criteria)->interest?:0;
		}
        $returnResult['info'] = '获取用户已赚利息成功';
        return  $returnResult;
    }
    /**
     * 获取用户资金情况统计表数据
     * @param $user_id int 用户ID
     * @return array
     */
	public function getUserStat($user_id){
        $return_result = array(
            'code' => 0, 'info' => '', 'data' => array(
                            'yesterday_earn_interest' => '',//昨日赚取利息
                            'average_daily_earn_interest' => '',//日均赚取利息
                            'average_daily_wait_interest' => ''//日均待收利息
            ));
        if (!isset($user_id) || $user_id == '') {
            $return_result['code'] = 2000;
            $return_result['info'] = Yii::app()->c->codeConfig[2000];
            return $return_result;
        }
        //获取用户资金状况
        $params[':user_id'] = $user_id;
        $stat_sql = "select yesterday_earn_interest,average_daily_earn_interest,average_daily_wait_interest from cdw_user_stat where user_id=:user_id ";
        try {
            $stat_res = Yii::app()->db->createCommand($stat_sql)->bindValues($params)->queryRow();
        } catch (Exception $e) {
            Yii::log('select cdw_user_stat Error info:'.print_r($e->getMessage(),true), "error");
            return $return_result;
        }
        if($stat_res){
            $return_result['data'] = $stat_res;
        }
        return $return_result;
    }

    /**
     * 统计用户获得红包金额
     * @param int $user_id 用户ID
     * @return array
     */
    public function getUserHongbaoRecord($user_id){
        $returnResult = array(
            'code' => 0, 'info' => '', 'data' => 0.00,
        );
        if (!isset($user_id) && $user_id == '') {
            $returnResult['code'] = 2000;
            $returnResult['info'] = Yii::app()->c->codeConfig[2000];
            return  $returnResult;
        }
        $criteria = new CDbCriteria;
        $criteria->select = 'sum(money) as money';
        $criteria->condition = "user_id=:user_id";
        $criteria->params[':user_id'] = $user_id;
        $record = HongbaoRecord::model()->find($criteria)->attributes;
        if (count($record) == 0) {
            return  $returnResult;
        }
        $returnResult['data'] += $record['money'];
        $returnResult['info'] = '用户红包信息获取成功';
        return  $returnResult;
    }

    /**
     * 统计用户获得体验金金额
     * @param int $user_id 用户ID
     * @return array
     */
    public function getUserAccountExpergold($user_id){
        $returnResult = array(
            'code' => 0, 'info' => '', 'data' => 0.00,
        );
        if (!isset($user_id) && $user_id == '') {
            $returnResult['code'] = 2000;
            $returnResult['info'] = Yii::app()->c->codeConfig[2000];
            return  $returnResult;
        }
        $criteria = new CDbCriteria;
        $criteria->select = 'sum(use_expergold) as use_expergold';
        $criteria->condition = "user_id=:user_id";
        $criteria->params[':user_id'] = $user_id;
        $record = AccountExpergold::model()->find($criteria)->attributes;
        if (count($record) == 0) {
            return  $returnResult;
        }
        $returnResult['data'] += $record['use_expergold'];
        $returnResult['info'] = '用户体验金信息获取成功';
        return  $returnResult;
    }

    public function getDays($join_time,$end_time = 0,$delay_days = 1)
    {
        $join_day = strtotime("midnight",$join_time);//加入日零点
        $end_day = strtotime("midnight",$end_time?:time());//退出日零点
        $days = ($end_day-$join_day)/86400 - $delay_days; //天数
        return $days>0?intval($days):0;
    }
    public function getTenderApr($days)
    {

		$config = Yii::app()->c->linkconfig["zxjh_config"];

		$now = time();
		$limit = strtotime($config['low_apr_time']);
		if(($this->tenderAddTime?:$now) <$limit){
			$wise_plan_apr_list = $config['wise_plan_apr_list'];
		}else{
			$wise_plan_apr_list = $config['wise_plan_lowapr_list'];
		}
        $now_apr = array_values($wise_plan_apr_list)[0];
        foreach($wise_plan_apr_list as $day=>$apr)
        {
            if($days+1 >= $day){
                $now_apr = $apr;
            }
        }
        return $now_apr;
    }

    public function getTenderInterest($days,$money,$precision = 2)
    {
		$now_apr = $this->getTenderApr($days);
        $interest = $money * (pow((1+ $now_apr/100/365),$days) - 1);
        return round($interest,$precision);
    }

}
