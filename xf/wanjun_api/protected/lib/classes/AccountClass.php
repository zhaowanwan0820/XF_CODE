<?php
/*
 * 用户账户类
 */

class AccountClass  {
        
    /**
     * 获取用户账户信息汇总
     **/
    public function getAccountInfo($user_id){
        $DwAccountModel = new Account();
        $criteria = new CDbCriteria; 
        $attributes = array(
          "user_id"    =>   $user_id,   
        );
        $DwAccountResult =$DwAccountModel->findByAttributes($attributes,$criteria);
        return $DwAccountResult;
     }
     /**
     * 获取用户银行卡信息
     */
    public function  getBankInfo($user_id,$offset,$limit){
        $DwAccountBankModel = new DwAccountBank();
        $criteria = new CDbCriteria;
        $criteria->offset = $offset;
        if(!empty($limit)){ 
            $criteria->limit = $limit;
        }
        $criteria->condition = "bank_status = 1";
        $attributes = array(
          "user_id"    =>   $user_id,
        );
        $DwAccountBankModelResult =$DwAccountBankModel->findAllByAttributes($attributes,$criteria);
        return $DwAccountBankModelResult;
    }
    
     /**
     * 获取用户充值记录
     */
    public function  getAccountRecharge($user_id,$offset,$limit){
        $AccountRechargeModel = new AccountRecharge();
        $criteria = new CDbCriteria; 
        $criteria->offset = $offset;
        if(!empty($limit)){ 
            $criteria->limit = $limit;
        }
        $attributes = array(
          "user_id"    =>   $user_id,  
        );
        $AccountRechargeResult =$AccountRechargeModel->findAllByAttributes($attributes,$criteria);
        return $AccountRechargeResult;
    }
    
        
    /**
     * 获取用户资金账户流水
     */
    public function getAccountLog($user_id,$offset,$limit,$order="",$more_attributes=array(),$more_criteria=null){
        $AccountLogModel = new AccountLog();
        $criteria = new CDbCriteria; 
        $criteria->offset = $offset;
        if($order) $criteria->order = $order;
        if($limit!="ALL") $criteria->limit = $limit;
        $attributes = array(
          "user_id"    =>   $user_id,  
        );
        if(!empty($more_attributes)){
            $attributes = array_merge($attributes,$more_attributes);
        }
        if(!empty($more_criteria)){
            $criteria->mergeWith($more_criteria);
        }
        $AccountLogResult =$AccountLogModel->findAllByAttributes($attributes,$criteria);
        return $AccountLogResult;
    }
    
    /**
     * 奖励账户流水
     */
    public function getAccountVitualLog($user_id,$offset,$limit){
        $AccountVirtualLogModel = new AccountVirtualLog();
        $criteria = new CDbCriteria; 
        $criteria->offset = $offset;
        if(!empty($limit)){ 
            $criteria->limit = $limit;
        }
        $attributes = array(
          "user_id"    =>   $user_id,  
        );
        $AccountVirtualLogResult =$AccountVirtualLogModel->findAllByAttributes($attributes,$criteria);
        return $AccountVirtualLogResult;
    }
    /**
     * 用户问题调查列表
     */
   public function getQuestionList(){
    $ItzUserQuestionModel = new ItzUserQuestion;
    
   
    $criteria = new CDbCriteria;
    //只取出显示的
    $criteria->condition = "is_show = 1";
    $criteria->order = '`sort` asc';
    $ItzUserQuestionResult = $ItzUserQuestionModel->findAll($criteria);
    return $ItzUserQuestionResult;
      
   }

    

}
