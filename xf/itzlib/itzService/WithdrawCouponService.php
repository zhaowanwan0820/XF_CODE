<?php
/**
 * @file WithdrawCouponService.php
 * @author (zhanglei@xxx.com)
 * @date 2017/09/18
 * 提现券
 **/

class WithdrawCouponService extends  ItzInstanceService {
	
	protected $expire1 = 1200;
    protected $expire2 = 86400;
    protected $secondaryFlag = false;
    
    public function __construct(  ){
        parent::__construct();
    }
    
    /**
     * 发送提现券方法
     * user_id
     * code 
     * num 一次发送数量
     * return boolean
     */
    public function sendWithdrawCoupon($user_id,$code,$num=1,$affix=array()){
    	//Yii::log(__FUNCTION__.print_r(func_get_args(),true),'info');
    	$user_id = intval($user_id);
    	$num = intval($num);
    	if( empty($user_id) || empty($num) || empty($code) ){
    		return false;
    	}
    	
    	try {
    		$data = array();
	    	for ($i=1;$i<=$num;$i++){
	    		$begin_time = time();
	    		$expire_day = 30;
	    		if(isset($affix['expire_day']) && intval($affix['expire_day']) > 0){
	    			$expire_day = intval($affix['expire_day']);
	    		}
	    		$expire_time = $this->expireTime($begin_time, $expire_day);
	    		$key = $i-1;
		    	$data[$key] = array(
		    		'user_id'       => $user_id,
		    		'status'        => 0,
		    		'src'           => $code,
		    		'amount'        => 2,
		    		'least_withdraw_amount' => 0,
		    		'begin_time'    => $begin_time,
		    		'expire_time'   => $expire_time,
		    		'remark'        => empty($affix['remark']) ? '积分兑换' : $affix['remark'],
		    		'addtime'       => $begin_time
		    	);
	    	}
	    	$sql =$this->makeInsertSqlByList($data);
	    	if(!$sql){
	    		return false;
    		}
    		$res = $this->deliverBySql($sql);
    		if(!res){
    			Yii::log('sendWithdrawCoupon insert error, user_id:'.$user_id, 'error', 'sendWithdrawCoupon');
    			return false;
    		}
    		return true;
    	} catch (Exception $e) {
    		Yii::log('sendWithdrawCoupon error '.print_r($e->getMessage(), true), 'error', 'sendWithdrawCoupon');
    		return false;
    	}
    }
    
    /**
     * 提现券使用方法
     * 嵌套事务里面使用
     * return array
     */
    public function useWithdrawCoupon($user_id,$cash_id){
    	Yii::log ( __FUNCTION__.print_r(func_get_args(),true),'info');
    	$returnResult = array(
    			'code'=>100,
    			'info'=>'',
    			'data'=>array()
    	);
    	$user_id = intval($user_id);
    	$cash_id = intval($cash_id);
    	if( empty($user_id) || empty($cash_id) ){
    		$returnResult['code'] = 100;
    		$returnResult['info'] = 'params error';
    		return $returnResult;
    	}
    	try {
    		$now_time = time();
    		$sql = "select id,src,amount,least_withdraw_amount,begin_time,expire_time,addtime from itz_withdraw_coupon where status=0 and expire_time>=:expire_time and user_id=:user_id order by expire_time asc limit 1 for update";
    		$params[':user_id'] = $user_id;
    		$params[':expire_time'] = $now_time;
    		$coupon_result = Yii::app()->db->createCommand($sql)->queryRow(true,$params);
    		if(empty($coupon_result)){
    			Yii::log("coupon_result is empty, cash_id:{$cash_id},user_id:{$user_id}","info","useWithdrawCoupon");
    			$returnResult['code'] = 200;
    			$returnResult['info'] = 'coupon_result is empty';
    			return $returnResult;
    		}
    		$res_coupon = BaseCrudService::getInstance()->update("ItzWithdrawCoupon",array(
    				"id" => $coupon_result['id'],
    				"status"=>1,
    				"cash_id" => $cash_id,
    				"use_time" => $now_time
    		),"id");
    		if(!$res_coupon){
    			Yii::log("coupon update is error, cash_id:{$cash_id},user_id:{$user_id}","error","useWithdrawCoupon");
    			$returnResult['code'] = 100;
    			$returnResult['info'] = 'coupon update is error';
    			return $returnResult;
    		}
    		$returnResult['code'] = 0;
    		$returnResult['info'] = 'success';
    		$returnResult['data'] = $coupon_result;
    		return $returnResult;
    	} catch (Exception $e) {
    		Yii::log("coupon Exception is error, cash_id:{$cash_id},user_id:{$user_id}，msg:".print_r($e->getMessage(),true), "error","useWithdrawCoupon");
    		$returnResult['code'] = 100;
    		$returnResult['info'] = 'error';
    		return $returnResult;
    	}
    }
    
    
    /**
     * 提现券退回方法
     * return boolean
     */
    public function backWithdrawCoupon($user_id,$cash_id){
    	Yii::log ( __FUNCTION__.print_r(func_get_args(),true),'info');
    	$user_id = intval($user_id);
    	$cash_id = intval($cash_id);
    	if( empty($user_id) || empty($cash_id) ){
    		return false;
    	}
    	try {
    		$sql = "update itz_withdraw_coupon set status=0, use_time=null, cash_id=0 where status=1 and user_id={$user_id} and cash_id={$cash_id}";
    		$result = Yii::app()->db->createCommand($sql)->execute();
    		if(!$result){
    			Yii::log("backWithdrawCoupon update is error, cash_id:{$cash_id},user_id:{$user_id}","error");
    			return false;
    		}
    		return true;
    	} catch (Exception $e) {
    		Yii::log("backWithdrawCoupon update error: ".print_r($e->getMessage(),true),"error");
    		return false;
    	}
    }
    
    public function addCoupon($data){
    	Yii::log ( __FUNCTION__." ".print_r(func_get_args(),true),'debug');
    	$model = new ItzWithdrawCoupon();
    	$model->attributes = $data;
    	if($model->save(false)){
    		return $model->getAttributes();
    	}else{
    		Yii::log("ItzWithdrawCoupon add error: ".print_r($model->getErrors(),true),"error");
    		return false;
    	}
    }
    
    /**
     * 生成sql
     * @param unknown $list
     * @return boolean|string
     */
    public function makeInsertSqlByList($list){
    	if (count($list) < 1) {
    		return false;
    	} else {
    		$sql = "INSERT INTO `%s` (`%s`) VALUES %s";
    		$fields = implode('`,`', array_keys($list[0]));
    		$tabName = 'itz_withdraw_coupon';
    		$values = "";
    		foreach ($list as $val) {
    			$values .= "('" . implode("','", $val) . "'),";
    		}
    		return rtrim(sprintf($sql, $tabName, $fields, $values), ',');
    	}
    }
    /**
     * 执行sql方法
     * @param unknown $sql
     * @return unknown|boolean
     */
    public function deliverBySql($sql){
    	try {
    		$insertRows = Yii::app()->db->createCommand($sql)->execute();
    		return $insertRows;
    	} catch (Exception $e) {
    		echo "执行错误:  {$e->getCode()} : [{$e->getMessage()}]， SQL： {$sql} \n\r";
    		return false;
    	}
    }
    
    
    /**
     * 计算过期时间
     * 根据实际生效时间 fromTime 进行偏移计算。
     * @param $beginTime 开始时间戳
     * @param $expireDay 天
     * @return int
     */
    public function expireTime($beginTime, $expireDay=1){
    	// 新过期时间计算方式, expireTime 为天数
    	$fromTime = $beginTime ? $beginTime : time();
    	$days = $expireDay;
    	$offset = "+{$days} days";
    	$result = strtotime($offset . ' -1 days', $fromTime);
    	return strtotime("23:59:59", $result);
    }
	
}
