<?php
/*
 * Coupon && CouponLog
 */

class CouponClass  {
	//阳光智选屏蔽新手加息券；
	protected  $common_condition = " and src <>  'sun_novice' " ;
	//获取Coupon列表
	public function getCoupons($offset=0,$limit=10,$order='addtime desc',$condition='',$params=array(),$with_array=array(),$addInCondition=array()){
        Yii::log ( __FUNCTION__.print_r(func_get_args(),true),'debug');
        $returnResult = array();
        $attributes = array(); $CDbCriteria = new CDbCriteria;
		$condition = !$condition?:$condition.$this->common_condition;
        $CDbCriteria->condition = $condition;
        $CDbCriteria->params = $params;
        $CDbCriteria->order = $order;
        foreach ($addInCondition as $key => $value) {
            $CDbCriteria->addInCondition($key, $value);
        };
        if($limit != 'All')	$CDbCriteria->limit = $limit;
		$CDbCriteria->offset = $offset;
        $DwCouponModel = new Coupon();
        $returnResult = $DwCouponModel->findAllByAttributes($attributes,$CDbCriteria);
        return $returnResult;
    }
    
	//获取Coupon总数
	public function getCouponCount($condition='',$params=array(),$addInCondition=array()){
        Yii::log ( __FUNCTION__.print_r(func_get_args(),true),'debug');
        $returnResult = array();
        $attributes = array(); $CDbCriteria = new CDbCriteria;
		$condition = !$condition?:$condition.$this->common_condition;
        $CDbCriteria->condition = $condition;
        $CDbCriteria->params = $params;
        foreach ($addInCondition as $key => $value) {
            $CDbCriteria->addInCondition($key, $value);
        };
        $CDbCriteria->limit = 'All';
        $DwCouponModel = new Coupon();
        $returnResult = $DwCouponModel->countByAttributes($attributes,$CDbCriteria);
        return $returnResult;
    }
    
	//获取Coupon单条信息
	public function getCouponInfo($condition='',$params=array(),$with_array=array()){
        Yii::log ( __FUNCTION__.print_r(func_get_args(),true),'debug');
        $returnResult = array();
        $attributes = array(); $CDbCriteria = new CDbCriteria;
        $CDbCriteria->condition = $condition;
        $CDbCriteria->params = $params;
        $DwCouponModel = new Coupon();
        $returnResult = $DwCouponModel->findByAttributes($attributes,$CDbCriteria);
        
        return $returnResult;
    }
    // coupon表 insert操作
	public function addCoupon($data){
        $DwCouponModel = new Coupon();
        foreach($data as $key=>$value){
            $DwCouponModel->$key = $value;
        }
        if($DwCouponModel->save()==false){
            Yii::log("DwCouponModel error: ".print_r($DwCouponModel->getErrors(),true),"error");
            return false;
        }else{
            return $DwCouponModel;
        }
    }
    
    // coupon表 update操作
    public function updateCoupon($data,$attr_name){
    	$DwCouponModel = new Coupon();
        $result = $DwCouponModel->findByAttributes(array($attr_name=> $data[$attr_name]));
        if($result== null){
            Yii::log("update error : no such result DwCouponModel error");
            return false;
        }else{
            foreach($data as $key=>$value){
                if($key != $attr_name){
                    $result->$key = $value;
                }
            }
            if($result->save()==false){
                Yii::log("DwCouponModel error: ".print_r($result->getErrors(),true),"error");
                return false;
            }else{
                return $result;
            }
        }
    }
    
	//获取CouponLog 列表
	public function getCouponLogs($offset=0,$limit=10,$order='addtime desc',$condition='',$params=array(),$with_array=array()){
        Yii::log ( __FUNCTION__.print_r(func_get_args(),true),'debug');
        $DwCouponLogModel = new CouponLog();
        $returnResult = array();
        $attributes = array(); $CDbCriteria = new CDbCriteria;
        $CDbCriteria->condition = $condition;
        $CDbCriteria->params = $params;
        $CDbCriteria->order = $order;
        if($limit != 'All')	$CDbCriteria->limit = $limit;
		$CDbCriteria->offset = $offset;
        $returnResult = $DwCouponLogModel->findAllByAttributes($attributes,$CDbCriteria);
        
        return $returnResult;
    }
    
	//获取CouponLog 总数
	public function getCouponLogCount($condition='',$params=array()){
        Yii::log ( __FUNCTION__.print_r(func_get_args(),true),'debug');
        $DwCouponLogModel = new CouponLog();
        $returnResult = array();
        $attributes = array(); $CDbCriteria = new CDbCriteria;
        $CDbCriteria->condition = $condition;
        $CDbCriteria->params = $params;
        $CDbCriteria->limit = 'All';
        $returnResult = $DwCouponLogModel->countByAttributes($attributes,$CDbCriteria);
        return $returnResult;
    }
    
	//获取CouponLog 单条信息
	public function getCouponLogInfo($condition='',$params=array(),$with_array=array()){
        Yii::log ( __FUNCTION__.print_r(func_get_args(),true),'debug');
        $DwCouponLogModel = new CouponLog();
        $returnResult = array();
        $attributes = array(); $CDbCriteria = new CDbCriteria;
        $CDbCriteria->condition = $condition;
        $CDbCriteria->params = $params;
        $returnResult = $DwCouponLogModel->findByAttributes($attributes,$CDbCriteria);
        
        return $returnResult;
    }
    /* 2016-03-02号注释coupon_log的insert[coupon_log表弃用]
    // couponLog表 insert操作
	public function addCouponLog($data){
        $DwCouponLogModel = new CouponLog();
        foreach($data as $key=>$value){
            $DwCouponLogModel->$key = $value;
        }
        if($DwCouponLogModel->save()==false){
            Yii::log("DwCouponModel error: ".print_r($DwCouponLogModel->getErrors(),true),"error");
            return false;
        }else{
            return $DwCouponLogModel;
        }
    }
    */
    // couponLog表 update操作
    public function updateCouponLog($data,$attr_name){
    	$DwCouponLogModel = new CouponLog();
        $result = $DwCouponLogModel->findByAttributes(array($attr_name=> $data[$attr_name]));
        if($result== null){
            Yii::log("update error : no such result DwCouponModel error");
            return false;
        }else{
            foreach($data as $key=>$value){
                if($key != $attr_name){
                    $result->$key = $value;
                }
            }
            if($result->save()==false){
                Yii::log("DwCouponModel error: ".print_r($result->getErrors(),true),"error");
                return false;
            }else{
                return $result;
            }
        }
    }
    
    //获取Coupon总数
	public function getCouponLeastInvest($condition='',$params=array()){
        Yii::log ( __FUNCTION__.print_r(func_get_args(),true),'debug');
        $DwCouponModel = new Coupon();
        $CDbCriteria = new CDbCriteria;
        $CDbCriteria->select = 'MIN(least_invest_amount) as least_invest_amount';
        $CDbCriteria->condition = $condition;
        $CDbCriteria->params = $params;
        $returnResult = $DwCouponModel->find($CDbCriteria);
        return $returnResult;
    }
}
