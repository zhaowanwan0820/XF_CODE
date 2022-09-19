<?php
/**
 * 取消赎回API
 *
 * @author 
 */
class CancelRedeemCurrent extends ItzApi{
    public $logcategory = "cancel.redeem.current";

    /**
    * redeem_list_id: 赎回记录的ID
    */
    public function run($user_id, $redeem_list_id){
        Yii::log("RequestData: user_id=$user_id; redeem_list_id=$redeem_list_id", "info", $this->logcategory);
        $now_time = time();
        //简单参数验证
        if(empty($user_id) || !is_numeric($user_id) || $user_id < 0 || empty($redeem_list_id) || !is_numeric($redeem_list_id) || $redeem_list_id < 0){
            Yii::log("params illegal user_id:$user_id, redeem_list_id:$redeem_list_id", 'error', $this->logcategory);
            $this->code = 7200;
            return $this;
        }

        $result = AccountService::getInstance()->redeemCurrentFail($user_id, $redeem_list_id, 3);
        $this->code = $result['code'];
        $this->data = isset($result['data'])?$result['data']:array();
        Yii::log("code:{$this->code}, info:{$this->info}, data:".json_encode($this->data), "info", $this->logcategory);
        return $this;
    }
}