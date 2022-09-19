<?php
/**
 * 取消申购活期API
 */
class CancelPurchaseCurrent extends ItzApi{
    public $logcategory = "cancel.purchase.current";

    public function run($user_id, $purchase_id) {
        Yii::log("CancelPurchaseCurrent RequestData: user_id=$user_id; purchase_id=$purchase_id", "info", $this->logcategory);
        //进行参数验证
        if (!is_numeric($user_id) || $user_id =='' || $user_id<=0 ||!is_numeric($purchase_id) || $purchase_id == ''|| $purchase_id<=0) {
            Yii::log('CancelPurchaseCurrent: params is illegal,user_id:'.$user_id.',purchase_id:'.$purchase_id, 'error', $this->logcategory);
            $this->code = 7200;
            return $this;
        }
        
        $result = AccountService::getInstance()->purchaseCurrentFail($user_id, $purchase_id, 3);
        if (empty($result['data'])) {
            $result['data'] = array();
        }
        Yii::log("CancelPurchaseCurrent: result:".print_r($result,true), "info", $this->logcategory);
        $this->code = $result['code'];
        $this->data = $result['data'];
        return $this; 
    }
}
