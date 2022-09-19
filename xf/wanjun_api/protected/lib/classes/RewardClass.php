<?php
/**
 * @file RewardClass.php
 **/

class RewardClass  {


    /**
     * 获取所有的奖项
     **/
    public function getRewardList(){
        $ItzRewardModel = new ItzReward();
        $criteria = new CDbCriteria; 
        $criteria->condition = "re_isvalid = 1";
        $ItzRewardResult =$ItzRewardModel->findAll($criteria);
        return $ItzRewardResult;
     }
    
    /**
     * 获取用户中奖纪录
    **/
    public function getRewardUsers($offset=0,$limit=10,$order="",$more_attributes=array(),$more_criteria=null,$condition=""){
        $ItzRewardUserModel = new ItzRewardUser();
        $criteria = new CDbCriteria; 
        $criteria->condition = $condition;
        $criteria->offset = $offset;
        if($order) $criteria->order = $order;
        if($limit!="ALL") $criteria->limit = $limit;
        $attributes = array();
        if(!empty($more_attributes)){
            $attributes = array_merge($attributes,$more_attributes);
        }
        if(!empty($more_criteria)){
            $criteria->mergeWith($more_criteria);
        }
        $ItzRewardUserResult =$ItzRewardUserModel->with('userInfo','rewardInfo')->findAllByAttributes($attributes,$criteria);
        return $ItzRewardUserResult;
     }
    

}
