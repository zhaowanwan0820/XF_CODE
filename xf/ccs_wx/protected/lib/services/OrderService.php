<?php
/**
 * @file OrderService.php
 * @author (zhanglei@itouzi.com)
 * @date 2017/02/14
 * 订单相关
 **/
class OrderService extends ItzInstanceService {
	
	public $_opmp_host = ''; // opmp对应url
	public $_ccs_host = ''; // opmp对应url
	
    public function __construct( ){
        parent::__construct();
        $this->_opmp_host = Yii::app()->c->opmpUrl;
        $this->_ccs_host = Yii::app()->c->ccsUrl;
    }
    
    //订单挂断电话提交
    public function setOrderExamine($admin_id,$data=array()){
    	Yii::log ( __FUNCTION__.'--'.print_r(func_get_args(),true),'info');
    	$admin_id = intval($admin_id);
    	$order_id = isset($data['orderid']) ? intval($data['orderid']) : '';
    	$is_pass = isset($data['is_pass']) ? intval($data['is_pass']) : '';
    	$remark = isset($data['remark']) ? trim($data['remark']) : '';
    	$order_status = isset($data['order_status']) ? intval($data['order_status']) : '';
    	$cancel_type = isset($data['cancel_type']) ? intval($data['cancel_type']) : '';
    	$appType = isset($data['approver_type']) ? intval($data['approver_type']) : '';
    	if(empty($is_pass) || empty($order_status) || empty($remark) || empty($order_id) ){
    		$returnResult['code'] = 1003;
    		$returnResult['info'] = '缺少参数';
    		return $returnResult;
    	}
    	// approver_type  1首次回访、2凭证审核、3冷静期后回访、4待取消
    	// 1=>'待付款', 2=>'待确认', 3=>'冷静期', 4=>'待回访', 5=>'待基金成立', 6=>'收益中', 7=>'已结清', 8=>'待取消', 9=>'已取消', 10=>'投资失败'
    	if( $order_status == 2 ){
    		$approver_type = 2;
    	}elseif($order_status == 4){
    		$approver_type = 3;
    	}elseif($order_status == 8){
    		$approver_type = $appType==2 ? 5 : 4;
    	}else{
    		$returnResult['code'] = 1003;
    		$returnResult['info'] = '订单状态传递错误';
    		return $returnResult;
    	}
    	$_url = $this->_opmp_host.'/cs-approve/'.$order_id;
    	$params = array(
    		"is_pass"=> $is_pass,
    		"approver_type"=> $approver_type,
    		"remark"=> addslashes($remark),
    		"approver_id"=> base64_encode($admin_id),
    		"approver_ip"=> FunctionUtil::ip_address()
    	);
    	//"cancel_type"=> $cancel_type
    	if(!empty($cancel_type)){
    		$params['cancel_type'] = $cancel_type;
    	}
    	Yii::log ( 'setOrderExamine curl java cs-approve:'.$_url.'>>'.print_r($params,true),'info');
    	$result = CcsCurlService::getInstance()->setCurlLoop($_url,$params,$method='POST');
    	return $result;
    }
    
    
    /**
     * 提交合同地址
     * @param unknown $admin_id
     * @param unknown $data
     */
    public function address($admin_id,$data=array()){
    	Yii::log ( __FUNCTION__.print_r(func_get_args(),true),'info');
    	$result = array('code'=>1,'info'=>'','data'=>array());
    	$order_id = intval($data['order_id']);
    	$address = trim($data['agreement_address']);
    	if(empty($order_id) || empty($address)){
    		$result['code'] = 100;
    		$result['info'] = '参数错误！';
    		return $result;
    	}
    	$params = array();
    	$_url = $this->_opmp_host.'/agreement-update/'.$order_id;
    	$params['creator_id'] = base64_encode($admin_id);
    	$params['express_address'] = addslashes($address);
    	$params['creator_ip'] = FunctionUtil::ip_address();
    	$result = CcsCurlService::getInstance()->setCurlLoop($_url,$params,$method='POST');
    	return $result;
    }
    
    /**
     * 获取列表
     * @param array $data
     */
    public function getList($data=array(),$page=1,$limit=10){
    	$result = array();
    	$_url = $this->_ccs_host.'/orders';
    	$params = $data;
    	$params['page_num'] = !empty($params['page']) ? intval($params['page']) : $page;
    	$params['page_size'] = !empty($params['limit']) ? intval($params['limit']) : $limit;
    	unset($params['page']);
    	unset($params['limit']);
    	$info = CcsCurlService::getInstance()->setCurlLoop($_url,$params,$method='GET');
    	if($info['data']['total']){
	    	$result['data']['listTotal'] = $info['data']['total'];
	    	$result['data']['listInfo'] = $info['data']['list'];
    	}else{
    		$result['data'] = array();
    	}
    	$result['code'] = $info['code'];
    	$result['info'] = $info['info'];
    	return $result;
    }
    
    /**
     * 详情信息
     * @param number $id
     * @return multitype:|unknown
     */
    public function getDetail($id=0){
    	$id = intval($id);
    	if(empty($id)){
    		return array(
    			'code'=>100,
    			'info'=>'参数错误',
    			'data'=>array()
    		);
    	}
    	$params = array();
    	$_url = $this->_ccs_host.'/order/'.$id;
    	$info = CcsCurlService::getInstance()->setCurlLoop($_url,$params,$method='GET');
		$list = $info['data'];
    	foreach($list['visit'] as $k=>$v){
    		if($v['approver_type'] == 3){
    			//取冷静期后回访录音
    			$list['calm_end_audio_path'] = $v['audio_path'];
    		}
    		$sql = " select admin_name from ccs_relation WHERE admin_id = ". $v['approver_id'];
    		$username= Yii::app()->ccsdb->createCommand($sql)->queryRow();
    		$list['visit'][$k]['approver_name'] = $username['admin_name'];
    	}
    	foreach($list['trace_records'] as $k=>$v){
    		if($v['flag'] == 2){
    			$sql = " select admin_name from ccs_relation WHERE admin_id = ". $v['approverId'];
    			$username= Yii::app()->ccsdb->createCommand($sql)->queryRow();
    			$list['trace_records'][$k]['approverName'] = $username['admin_name'];
    		}
			
    	}
    	
    	$result['data']['listInfo'] = $list;
    	$result['code'] = $info['code'];
    	$result['info'] = $info['info'];
    	return $result;
    }
    
    /**
     * 修改开户行信息
     */
    public function editBankDeposit($admin_id,$data=array()){
    	Yii::log ( __FUNCTION__.print_r(func_get_args(),true),'info');
    	$result = array('code'=>1,'info'=>'','data'=>array());
    	$order_id = intval($data['order_id']);
    	$bank_deposit = trim($data['bank_deposit']);
    	if(empty($order_id) || empty($bank_deposit)){
    		$result['code'] = 100;
    		$result['info'] = '参数错误！';
    		return $result;
    	}
    	$params = array();
    	$_url = $this->_opmp_host.'/deposit-update/'.$order_id;
    	$params['creator_id'] = base64_encode($admin_id);
    	$params['bank_deposit'] = addslashes($bank_deposit);
    	$params['creator_ip'] = FunctionUtil::ip_address();
    	
    	$result = CcsCurlService::getInstance()->setCurlLoop($_url,$params,$method='POST');
    	return $result;
    }
    
}