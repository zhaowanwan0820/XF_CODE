<?php
/**
 * @file CallService.php
 * @author (zhanglei@itouzi.com)
 * @date 2017/02/08
 * 外呼相关
 **/
class CallService extends  ItzInstanceService {
	
	//外呼相关账号
  	//正式
	public $_vcc_code = '1014122901'; // 外呼企业代码vcc_code
	public $_vcc_pwd = '81e594bf96322631c5c1318499a7981a'; //外呼账号对应的接口密码
	public $_vcc_host = 'http://m.icsoc.net'; //外呼账号对应url
	public $_vcc_ip = 'sockets.icsoc.net'; //外呼服务器IP
	
 	//测试账号
	/*public $_vcc_code = '4216060207'; // 外呼企业代码vcc_code
	public $_vcc_pwd = '106abef29bf10bb044130b3412190c14'; //外呼账号对应的接口密码
	public $_vcc_host = 'http://m.icsoc.net'; //外呼账号对应url
	public $_vcc_ip = 'socket.icsoc.net'; //外呼服务器IP*/
	
	public $_opmp_host = '';	//
	
    public function __construct( ){
        parent::__construct();
        
        $this->_opmp_host = Yii::app()->c->opmpUrl;
    }
    /**
     * 订单呼叫 - 填写备注和状态
     * @param unknown $admin_id
     * @param unknown $data
     */
    public function setCallRemark($admin_id,$data=array()){
    	Yii::log ( __FUNCTION__.print_r(func_get_args(),true),'info');
    	$returnResult = array(
    		'code' => '', 'info' => '', 'data' => array()
    	);
    	$admin_id = intval($admin_id);
    	$call_id = isset($data['callid']) ? addslashes($data['callid']) : 0;
    	$order_id = isset($data['orderid']) ? intval($data['orderid']) : 0;
    	$category = isset($data['category']) ? intval($data['category']) : 0;
    	$status = isset($data['status']) ? intval($data['status']) : 0;
    	$remark = isset($data['remark'])? trim($data['remark']) : '';
    	$action_id = isset($data['action_id']) ? intval($data['action_id']) : 0;
    	$now_time = time();
    	if(empty($call_id) || empty($category)){
    		$returnResult['code'] = 1003;
    		$returnResult['info'] = '缺少参数';
    		return $returnResult;
    	}
    	if(in_array($category, array(1,2,3))){
    		if(empty($status)){
    			$returnResult['code'] = 1004;
    			$returnResult['info'] = '请选择回访状态';
    			return $returnResult;
    		}
    		if(empty($remark)){
    			$returnResult['code'] = 1004;
    			$returnResult['info'] = '请输入备注信息';
    			return $returnResult;
    		}
    		if(empty($order_id)){
    			$returnResult['code'] = 1003;
    			$returnResult['info'] = '缺少参数';
    			return $returnResult;
    		}
    	}
    	//开启事务
    	Yii::app()->ccsdb->beginTransaction();
    	$record_info['category'] = $category;
    	$record_info['status'] = $status;
    	$record_info['remark'] = addslashes($remark);
    	$record_info['updatetime'] = $now_time;
    	$res = Yii::app()->ccsdb->createCommand()->update('ccs_call_record',$record_info,'call_id=:call_id', array(':call_id'=>$call_id));
    	if($res){
    		if(in_array($category, array(1,2,3))){ //首次回访,冷静期后回访和待取消回访
    			if(in_array($status, array(4,5,6,7,9))){
	    			if(empty($order_id)){
	    				$returnResult['code'] = 1003;
	    				$returnResult['info'] = '缺少参数';
	    				//回滚
	    				Yii::app()->ccsdb->rollback();
	    				return $returnResult;
	    			}
	    			$params = array();
	    			//请求java接口
	    			$_url = $this->_opmp_host.'/cs-approve/'.$order_id;
	    			$params = array(
	    				"remark"=> addslashes($remark),
	    				"approver_id"=> base64_encode($admin_id),
	    				"approver_ip"=> FunctionUtil::ip_address()
	    			);
	    			if($category==1){
	    				$params['approver_type'] = 1;
	    			}elseif($category==2){
	    				$params['approver_type'] = 3;
	    			}elseif($category==3){
	    				$params['approver_type'] = 6;
	    			}
	    			if(in_array($status, array(4,6,9))){
	    				$params['is_pass'] = 1;
	    			}elseif(in_array($status, array(5,7))){
	    				$params['is_pass'] = 2;
	    			}
	    			Yii::log ('CallService setCallRemark curl java cs-approve:'.$_url.'>>'.print_r($params,true),'info');
	    			$result = CcsCurlService::getInstance()->setCurlLoop($_url,$params,$method='POST');
	    			if($result['code']!==0){
	    				Yii::log ('CallService setCallRemark curl java cs-approve error:'.$_url.'>>'.print_r($params,true).'! Result info:'.print_r($result,true),'error');
	    				//回滚 并提示失败
	    				Yii::app()->ccsdb->rollback();
	    				$returnResult['code'] = 100;
	    				$returnResult['info'] = '更新opmp数据失败！';
	    				return $returnResult;
	    			}
	    		}
    		}else if($category == 21){
    			if(empty($action_id)){
    				$returnResult['code'] = 1;
    				$returnResult['info'] = '缺少参数';
    				//回滚
    				Yii::app()->ccsdb->rollback();
    				return $returnResult;
    			}
    			$params['call_status'] = 2;
    			$params['updatetime'] = time();
    			$res = Yii::app()->ccsdb->createCommand()->update('ccs_user_action',$params,'id=:action_id', array(':action_id'=>$action_id));
    			if(!$res){
    				Yii::log ( __FUNCTION__." update user action fail id=".$action_id,'error');
    				//回滚
    				Yii::app()->ccsdb->rollback();
    				$returnResult['code'] = 1;
    				$returnResult['info'] = '更新呼叫状态失败！';
    				return $returnResult;
    			}
    		}else if($category == 22){
    			if(empty($action_id)){
    				$returnResult['code'] = 1;
    				$returnResult['info'] = '缺少参数';
    				//回滚
    				Yii::app()->ccsdb->rollback();
    				return $returnResult;
    			}
    			$params['call_status'] = 2;
    			$params['updatetime'] = time();
    			$res = Yii::app()->ccsdb->createCommand()->update('ccs_reg_fail',$params,'id=:action_id', array(':action_id'=>$action_id));
    			if(!$res){
    				Yii::log ( __FUNCTION__." update ccs_reg_fail fail id=".$action_id,'error');
    				//回滚
    				Yii::app()->ccsdb->rollback();
    				$returnResult['code'] = 1;
    				$returnResult['info'] = '更新呼叫状态失败！';
    				return $returnResult;
    			}
    		}
    		
    		//提交事务
    		Yii::app()->ccsdb->commit();
    		$returnResult['code'] = 0;
    		$returnResult['info'] = 'success';
    		return $returnResult;
    	}else {
    		Yii::log ( __FUNCTION__." update order call record fail call_id=".$call_id,'error');
    		$returnResult['code'] = 1;
    		$returnResult['info'] = '添加数据失败';
    		//回滚
    		Yii::app()->ccsdb->rollback();
    		return $returnResult;
    	}
    }
    
    /**
     * 客服--外呼回调
     * @return multitype:string multitype: number
     */
    public function setOutCallRecord($admin_id,$data=array()){
     	Yii::log ( __FUNCTION__.print_r(func_get_args(),true),'info');
     	$returnResult = array(
     		'code' => '', 'info' => '', 'data' => array()
		 );
		//源数据类型
		$source = isset($data['source'])?addslashes($data['source']):"ccs";
     	$admin_id = intval($admin_id);
     	$call_id = isset($data['callid'])?addslashes($data['callid']):0;
     	$type = isset($data['type'])?intval($data['type']):0;
     	 
     	$now_time = time();
     	if( empty($call_id) || empty($type) ){
     		$returnResult['code'] = 1003;
     		$returnResult['info'] = '缺少参数';
     		return $returnResult;
		 }
     	if($type==1){//呼叫
     		$user_phone = isset($data['phone']) ? trim($data['phone']) : '';
     		$call_type = isset($data['calltype']) ? intval($data['calltype']) : 1;
     		if( empty($user_phone) || empty($call_type)){
     			$returnResult['code'] = 1003;
     			$returnResult['info'] = '缺少参数';
     			return $returnResult;
     		}
     				
     		//添加数据
     		$info['call_id'] = $call_id;
     		$info['admin_id'] = $admin_id;
     		$info['user_phone'] = $user_phone;
     		$info['type'] = $call_type;
     		$info['start_time'] = $info['addtime'] = $info['updatetime'] = $now_time;
     		$res = $this->addCallRecord($info);
     		if($res){
     			$returnResult['code'] = 0;
     			$returnResult['info'] = 'success';
     		}else{
     			Yii::log ( __FUNCTION__." insert order call record fail admin_id=".$admin_id.',call_id='.$call_id,'error');
     			$returnResult['code'] = 1;
     			$returnResult['info'] = '插入初始通话记录失败！';
     		}
     	}elseif($type==2){ //挂断
     		$user_info = $this->getUserInfoByAid($admin_id);
     		$record_info['end_time'] = $record_info['updatetime'] = $now_time;
     		$record_info['ag_phone'] = $user_info['ag_phone'];
     		$record_info['admin_name'] = $user_info['admin_name'];
     		$res = Yii::app()->ccsdb->createCommand()->update('ccs_call_record',$record_info,'call_id=:call_id', array(':call_id'=>$call_id));
     		if($res){
     			$returnResult['code'] = 0;
     			$returnResult['info'] = 'success';
     		}else {
     			Yii::log ( __FUNCTION__." update order call record fail call_id=".$call_id,'error');
     			$returnResult['code'] = 1;
     			$returnResult['info'] = '更新初始通话记录失败';
     		}
     	}else {
     		$returnResult['code'] = 1005;
     		$returnResult['info'] = '参数传递错误';
     	}
     	return $returnResult;
     }
    
    
    /**
     * 订单呼出回调
     */
    public function setOrderTeleBack($admin_id,$data=array()){
    	Yii::log ( __FUNCTION__.print_r(func_get_args(),true),'info');
    	$returnResult = array(
    		'code' => '', 'info' => '', 'data' => array()
    	);
   		$admin_id = intval($admin_id);
    	$call_id = isset($data['callid'])?addslashes($data['callid']):0;
    	$type = isset($data['type'])?intval($data['type']):0;
    	
    	$now_time = time();
    	if( empty($call_id) || empty($type) ){
    		$returnResult['code'] = 1003;
    		$returnResult['info'] = '缺少参数';
    		return $returnResult;
    	}
    	if($type==1){//呼叫
    		$order_id = isset($data['orderid']) ? intval($data['orderid']) : 0;
    		$user_phone = isset($data['phone']) ? trim($data['phone']) : '';
    		$order_status = isset($data['order_status']) ? intval($data['order_status']) : 0;
    		if( empty($user_phone) || empty($order_id) || empty($order_status)){
    			$returnResult['code'] = 1003;
    			$returnResult['info'] = '缺少参数';
    			return $returnResult;
    		}
    		
    		//添加数据
    		$info['call_id'] = $call_id;
    		$info['admin_id'] = $admin_id;
    		$info['app_id'] = $order_id;
    		$info['user_phone'] = $user_phone;
    		$info['type'] = 0;
    		$info['order_status'] = $order_status;
    		$info['start_time'] = $info['addtime'] = $info['updatetime'] = $now_time;
    		$res = $this->addCallRecord($info);
    		if($res){
    			$returnResult['code'] = 0;
    			$returnResult['info'] = 'success';
    		}else{
    			Yii::log ( __FUNCTION__." insert order call record fail admin_id=".$admin_id.',call_id='.$call_id,'error');
    			$returnResult['code'] = 1;
    			$returnResult['info'] = '插入初始通话记录失败！';
    		}
    	}elseif($type==2){ //挂断
    		$user_info = $this->getUserInfoByAid($admin_id);
    		
    		$record_info['end_time'] = $record_info['updatetime'] = $now_time;
    		$record_info['ag_phone'] = $user_info['ag_phone'];
    		$record_info['admin_name'] = $user_info['admin_name'];
    		
    		$res = Yii::app()->ccsdb->createCommand()->update('ccs_call_record',$record_info,'call_id=:call_id', array(':call_id'=>$call_id));
    		if($res){
    			$returnResult['code'] = 0;
    			$returnResult['info'] = 'success';
    		}else{
    			Yii::log ( __FUNCTION__." update order call record fail call_id=".$call_id,'error');
    			$returnResult['code'] = 1;
    			$returnResult['info'] = '更新初始通话记录失败';
    		}
    	}else{
    		$returnResult['code'] = 1005;
    		$returnResult['info'] = '参数传递错误';
    	}
    	return $returnResult;
    }
    
    /**
     * 修改手机分机号码
     * @param array $post
     * @return multitype:string multitype: number
     */
    public function editCallNum($admin_id,$post = array()){
    	Yii::log ( __FUNCTION__.print_r(func_get_args(),true),'info');
    	$returnResult = array(
    		'code' => '', 'info' => '', 'data' => array()
    	);
    	$admin_id = intval($admin_id);
    	$data['ag_phone'] = $callNum = intval($post['call_num']);
    	if(empty($admin_id) || empty($callNum)){
    		$returnResult['code'] =  1003;
    		$returnResult['info'] =  '缺少必要参数';
    		return $returnResult;
    	}
    	$result = $this->updateRelationByAid($admin_id, $data);
    	if ($result) {
    		$returnResult['code'] =  0;
    		$returnResult['info'] =  '更新成功';
    	}else {
    		$returnResult['code'] =  1;
    		$returnResult['info'] =  '更新失败';
    	}
    	return $returnResult;
    }
    
    /**
     * relation update操作
     * @param int $id
     * @param array $data
     */
    public function updateRelationByAid($id,$data){
    	Yii::log ( __FUNCTION__.print_r(func_get_args(),true),'info');
    	$data['updatetime'] = time();
    	$relation_model = new CcsRelation();
    	$tableName = $relation_model->tableName();
    	return Yii::app()->ccsdb->createCommand()->update($tableName,$data,'admin_id=:admin_id', array(':admin_id'=>$id));
    }
    
    /**
     * 获取通话记录列表
     */
    public function getCallRecordList($admin_id,$data=array(),$limit=10,$page=1){
    	Yii::log ( __FUNCTION__.'--'.print_r(func_get_args(),true),'info');
    	$returnResult = array(
    		'code' => '1', 'info' => 'error', 'data' => array('listTotal' => 0, 'listInfo' => array())
    	);
    	$admin_id = intval($admin_id);
    	$user_phone = isset($data['user_phone']) ? trim($data['user_phone']) : '';
    	$type = isset($data['type']) ? intval($data['type']) : 0;
    	$call_status = isset($data['call_status']) ? intval($data['call_status']) : 0;
    	$category = isset($data['category']) ? intval($data['category']) : 0;
    	$admin_name = isset($data['admin_name']) ? trim($data['admin_name']) : '';
    	//$addtime = 0;
    	$start_time = !empty($data['start_time']) ? trim($data['start_time']) : 0;
    	$end_time = !empty($data['end_time']) ? trim($data['end_time']) : 0;
    	
    	$conditions = ' type > 0 ';
    	$order = ' id desc';
    	//搜索条件
    	/* if($admin_id){
    		$conditions .= ' and admin_id='.$admin_id;
    	} */
    	//手机号
    	if($user_phone){
    		$conditions .= " and user_phone='{$user_phone}'";
    	}
    	if($type){
    		$conditions .= ' and type='.$type;
    	}
    	if($call_status){
    		$conditions .= ' and call_status='.$call_status;
    	}
    	if($category){
    		$conditions .= ' and category='.$category;
    	}
    	if($admin_name){
    		$conditions .= " and admin_name='{$admin_name}'";
    	}
    	if($start_time){
    		$conditions .= " and addtime >= ". $start_time;
    	}
    	if( $end_time ){
    		$conditions .= " and addtime <= ". strtotime(date('Y-m-d',$end_time).' 23:59:59');
    	}
    	//分页条数设置
    	$limit = in_array($data['limit'], array(10,15,20,25,30,35,40,45,50)) ? intval($data['limit']) : $limit;
    	//请求页数
    	$page = (isset($data['page']) && $data['page'] != '') ? intval($data['page']) : $page;
    	 
    	//统计总数
    	$listTotal = CcsCallRecord::model()->count($conditions);
    	if ($listTotal == 0) {
    		$returnResult['code'] = 0;
    		$returnResult['info'] = '暂无数据！';
    		return $returnResult;
    	}
    	$returnResult['data']['listTotal'] = intval($listTotal);
    	
    	//详细信息分页展示
    	$criteria = new CDbCriteria();
    	$criteria->select = 'id,type,category,admin_name,user_phone,talk_time,remark,call_status,remark,call_id,record_url,addtime';
    	$criteria->condition = $conditions;
    	$criteria->order = $order;
    	$criteria->limit = $limit;
    	$criteria->offset = ($page - 1) * $limit;
    	$list = CcsCallRecord::model()->findAll($criteria);
    	$status_arr = array( 1=>'呼叫',2=>'接通',3=>'未接',4=>'拒接');
    	$category_arr = array(	1=>'首次回访',
							2=>'冷静期后回访',
							3=>'取消订单回访',
							7=>'注册与登录',
							8=>'充值与提现',
							9=>'投资及转让',
							10=>'认证与平台安全',
							11=>'法律法规',
							12=>'积分与优惠券问题',
							13=>'快捷卡问题',
							14=>'论坛问题',
							15=>'私募基金相关',
    						20=>'其他问题',
    						21=>'充值失败回呼'
    				);
    	$type_arr = array(1=>'呼出',2=>'呼入');
    	foreach ($list as $key=>$value){
    		$findDate = $value->attributes;
    		$findDate['call_status'] = $status_arr[$findDate['call_status']];
    		$findDate['category'] = isset($category_arr[$findDate['category']]) ? $category_arr[$findDate['category']] : '--';
    		$findDate['type'] = isset($type_arr[$findDate['type']]) ? $type_arr[$findDate['type']] : '--';
    		$findDate['addtime'] = date('Y-m-d H:i',$findDate['addtime']);
    		//手机号脱敏
    		$findDate['user_phone'] = substr_replace($findDate['user_phone'],'****',3,4);
    		$listInfo[] = $findDate;
    	}
    	$returnResult['code'] = 0;
    	$returnResult['info'] = 'success';
    	$returnResult['data']['listInfo'] = $listInfo;
    	return $returnResult;
    }
    /**
     * 手动添加通话记录
     * zlei
     */
    public function handAddCallRecord($admin_id=0,$data){
    	Yii::log ( __FUNCTION__.'--'.print_r(func_get_args(),true),'info');
    	$result = array('code'=>'','info'=>'','data'=>array());
    	
    	$params['admin_id'] = $admin_id = intval($admin_id);
    	$params['user_phone'] = trim($data['user_phone']);
    	//校验手机号格式
    	if (!FunctionUtil::IsMobile($params['user_phone'])) {
    		$result['code'] = 5502;
    		$result['info'] = "手机号码格式错误。";
    		return $result;
    	}
    	$params['addtime'] = intval($data['addtime']);
    	$params['type'] = intval($data['type']);
    	$params['category'] = intval($data['category']);
    	$params['call_status'] = intval($data['call_status']);
    	$user_info = $this->getUserInfoByAid($admin_id);
    	if($user_info){
    		$params['admin_name'] = $user_info['admin_name']; // 后台用户数据
    	}
    	$remark = trim($data['remark']);
    	if(empty($remark)){
    		$result['code'] = 100;
    		$result['info'] = "请输入备注。";
    		return $result;
    	}
    	if(mb_strlen($remark)>100){
    		$result['code'] = 100;
    		$result['info'] = "请输入备注过长。";
    		return $result;
    	}
    	$params['remark'] = addslashes($remark);
    	$params['append'] = 2;//手动添加
    	$params['call_id'] = md5(time().rand(100, 999999));//手动添加
    	$params['updatetime'] = time();
    	$res = $this->addCallRecord($params);
    	if($res){
    		$result['code'] = 0;
    		$result['info'] = '添加成功';
    	}else{
    		$result['code'] = 1;
    		$result['info'] = '添加失败';
    	}
    	return $result;
    }
    
    /**
     * 提交通话录音和用户问卷调查
     */
    public function uploadAudioExam($order_id,$data=array()){
    	Yii::log ( __FUNCTION__.'--'.print_r(func_get_args(),true),'info');
    	$result = array('code'=>'','info'=>'','data'=>array());
    	$order_id = intval($order_id);
    	if( empty($order_id) || empty($data) ){
    		 $result['code']=100;
    		 $result['info']='参数错误';
    		 return $result;
    	}
    	$_url = $this->_opmp_host.'/cs-audio-exam/'.$order_id;
    	$params = $data;
    	return CcsCurlService::getInstance()->setCurlLoop($_url,$params,$method='POST');
    }
    
    /**
     * 获取外呼用户信息
     * @param number $admin_id
     * @return Ambigous <string, multitype:>
     */
    public function getUserInfo($admin_id=0,$type="ccs"){
    	Yii::log ( __FUNCTION__.'--'.print_r(func_get_args(),true),'info');
    	$result = $this->getUserInfoByAid($admin_id,$type);
    	if($result){
    		$result['ag_password'] = md5($result['ag_password']);
    		$call = array(
    			'vcc_ip'=>$this->_vcc_ip,
    			'vcc_host'=>$this->_vcc_host,
    			'vcc_code'=>$this->_vcc_code,
    		);
    		$result = array_merge($result,$call);
    		$return['code'] = 0;
    		$return['info'] = 'success';
    		$return['data'] = $result;
    	}else{
            //如果不存在 去申请坐席  ( v1.4版本改为手动签入 )
            $user_info['username'] = Yii::app()->user->name;
            $res = $this->setAgInfo($admin_id,$user_info);
            if($res){
                $return['code'] = 0;
                $return['info'] = '添加坐席成功';
            }else{
                $return['code'] = 1;
                $return['info'] = '添加坐席失败，请联系管理员';
            }
        }
    	return $return;
    }
    
    /**
     * 根据指定字段
     * @param string $field
     * @param string $value
     * @return array
     */
    public function getUserInfoByAid($admin_id=0,$type="ccs",$force=false){
    	Yii::log ( __FUNCTION__.'--'.print_r(func_get_args(),true),'info');
    	$admin_id = intval($admin_id);
    	if(empty($admin_id)){
    		return array();
		}
		$sql = "select * from crm_relation where admin_id = ".$admin_id;
		$crm = Yii::app()->crmdb->createCommand($sql)->queryRow();
		$sql = "select * from ccs_relation where admin_id = ".$admin_id;
		$ccs = Yii::app()->ccsdb->createCommand($sql)->queryRow();
		
		$data = [];
		if($force){
			$data =  $$type;
		}else{
			if($$type){
				$data = $$type;
			}elseif($crm){
				$data = $crm;
			}else{
				$data = $ccs;
			}
		}
		return $data;
    }
    
    /**
     * 用户关系 insert操作
     * @param array $data
     * @return boolean
     */
    public function addRelation($data){
    	Yii::log (__FUNCTION__.'--'.print_r(func_get_args(),true),'info');
    	$relation_model = new CcsRelation();
    	foreach($data as $key=>$value){
    		$relation_model->$key = $value;
    	}
    	if($relation_model->save()==false){
    		Yii::log("ccs_relation_model error: ".print_r($relation_model->getErrors(),true),"error");
    		return false;
    	}else{
			//将用户加入crmadmin表，新增加的暂定为客服。
			$crmAdmin = new CrmAdmin();
			$crmAdmin->admin_id = $relation_model->admin_id;
			$crmAdmin->relation_id = $relation_model->id;
			$crmAdmin->name = $relation_model->admin_name;
			$crmAdmin->addtime = $relation_model->addtime;
			$crmAdmin->type = 2;
			$crmAdmin->status = 1;
			$crmAdmin->save();
    		return true;
    	}
    }
    
    /**
     * 通话记录 insert操作
     * @param array $data
     * @return boolean
     */
    public function addCallRecord($data){
    	Yii::log (__FUNCTION__.'--'.print_r(func_get_args(),true),'info');
    	$model = new CcsCallRecord();
    	foreach($data as $key=>$value){
    		$model->$key = $value;
    	}
    	if($model->save()==false){
    		Yii::log("ccs_call_record_model error: ".print_r($model->getErrors(),true),"error");
    		return false;
    	}else{
    		return true;
    	}
	}
	
	/**
	 * 获取agent信息
	 */
	public function getAgentInfo($ag_id = "",$filter = ""){
		$url = $this->_vcc_host."/v2/wintelapi/agent/list";
		$method = "POST";
		if($ag_id){
			$params["ag_id"] = $ag_id;
		}
		if($filter){
			$info['filter']['keyword'] = $filter;
			$params["info"] = json_encode($info);
		}
		$result = $this->setWinCurlLoop($url,$params,$method);
		if($result['code'] == 0){
			return $result['data'];
		}
		return [];
	}
    
    /**
     * 添加坐席
     * zlei
     */
    public function setAgInfo($admin_id=0,$user_info=array()){
		$admin_id = intval($admin_id);
		if(empty($admin_id)){
			return false;
		}
		$url = $this->_vcc_host."/v2/wintelapi/agent/add";
		$method = "POST";
		/*
            vcc_code 企业代码	必须
            que_id	技能组ID	可选
            ag_num	坐席工号	必须
            ag_name	坐席名称	可选
            ag_password	坐席密码	必须
            ag_role	坐席前台类型	必须
            user_role	坐席角色	可选
            belong_queues	所属技能组	可选
         */
		// 正式上线需修改。。。
		//$ag_password = $admin_id.rand(1000, 9999);
		$ag_password = '123456';
		$params = array(
			'vcc_code'=>$this->_vcc_code,
			//'que_id'=>200053,
			'ag_num'=>$admin_id.rand(10, 99),
			'ag_name'=> $user_info['username'],
			'ag_password'=>md5($ag_password),
			'ag_role'=>0,
			'user_role'=>610,
			'belong_queues'=>json_encode(array(205667,205668))
		);
		$result = $this->setWinCurlLoop($url,$params,$method);

		$data['ag_id'] = $result['data']['lastId'];
		$data['ag_num'] = $params['ag_num'];
		$data['ag_password'] = $ag_password;
		$data['ag_role'] = $params['ag_role'];
		$data['ag_user_role'] = $params['user_role'];
		//将数据添加到数据库
		$data['admin_id'] = $admin_id;
		$data['admin_name'] = $data['ag_name'] = $user_info['username'];
		$data['addtime'] = $data['updatetime'] = time();
		$res = $this->addRelation($data);
		if($res){
			return true;
		}else {
			return false;
		}
	}
	/**
	 * 获取通话录音
	 * @param unknown $data
	 * @return multitype:string multitype: number
	 */
	public function getDownRecord($data=array()){
		Yii::log ( __FUNCTION__.print_r(func_get_args(),true),'info');
		$returnResult = array(
			'code' => '', 'info' => '', 'data' => array()
		);
		$call_id = isset($data['call_id']) ? $data['call_id']:0;
		$type = isset($data['type'])?$data['type']:0;
		if(empty($call_id)){
			$returnResult['code'] = 1003;
			$returnResult['info'] = '缺少必要参数';
			return $returnResult;
		}
		$url = $this->_vcc_host.'/v2/wintelapi/record/playrecord';
		$method = 'GET';
		$params = array(
			'vcc_code'=>$this->_vcc_code,
			'call_id'=>	$call_id,
			'result_type'=>1
		);
		return $this->setWinCurlLoop($url, $params, $method);
	}
	
	/**
	 * 获取呼出记录
	 * @param array $data
	 */
	public function getCallOutInfo($data=array()){
		Yii::log ( __FUNCTION__.print_r(func_get_args(),true),'info');
		$url = $this->_vcc_host.'/v2/wintelapi/detail/call';
		$method = 'POST';
		$info = array(
			'filter'=>$data
		);
		$params = array(
			'vcc_code'=>$this->_vcc_code,
			'info'=> json_encode($info)
		);
		return $this->setWinCurlLoop($url,$params,$method);
	}


	public function getCallInList($data=array()){
		//Yii::log ( __FUNCTION__.print_r(func_get_args(),true),'info');
		$url = $this->_vcc_host.'/v2/wintelapi/detail/callin';
		$method = 'POST';
		$info = array(
			'filter'=>$data
		);
		$params = array(
			'vcc_code'=>$this->_vcc_code,
			'info'=> json_encode($info)
		);
		return $this->setWinCurlLoop($url,$params,$method);
	}
	
	/**
	 * 导出通话记录
	 * hl
	 */
	public function exportCallRecord($data = array()){
		Yii::log ( __FUNCTION__.print_r(func_get_args(),true),'info');
		$returnResult = array(
			'code' => '', 'info' => '', 'data' => array()
		);
		//日志审计参数
		$logParams = array(
			'user_id' => Yii::app()->user->id, 'system' => 'admin', 
			'action' => 'export', 'resource' => 'ccs', 'parameters' => '', 'status' => 'fail'
		);

		//导出时间
		$parameters['export_time'] = time();
		$logParams['parameters'] = json_encode($parameters);
		ini_set("memory_limit", "-1");
		ini_set('ini_setmax_execution_time', '1000');
		
		//默认导出7天
		if (empty($data)){
			$data['start_time'] = strtotime('-7 days');
			$data['end_time'] = time();
		}
		
		$admin_id=Yii::app()->user->id;
		$achievementResult = $this->getCallRecordList($admin_id,$data,100);
		$achievementList = $achievementResult['data']['listInfo'];
		$parameters['num'] = count($achievementList);
		$logParams['parameters'] = json_encode($parameters);
		
		//引入excel类
		Yii::import("itzlib.plugins.phpexcel.*");
		$PHPExcelObj = new PHPExcel();

		//设置导出的title
		$PHPExcelObj->getActiveSheet()->setTitle(date("Y-m-d") . '外呼咨询');
		$PHPExcelObj->getActiveSheet()->setCellValue('A1', '客户手机号');
		$PHPExcelObj->getActiveSheet()->setCellValue('B1', '通话时间');
		$PHPExcelObj->getActiveSheet()->setCellValue('C1', '电话类型');
		$PHPExcelObj->getActiveSheet()->setCellValue('D1', '外呼/咨询分类');
		$PHPExcelObj->getActiveSheet()->setCellValue('E1', '接通情况');
		$PHPExcelObj->getActiveSheet()->setCellValue('F1', '客服');
		$PHPExcelObj->getActiveSheet()->setCellValue('G1', '备注');
		
		//设置列宽
		$PHPExcelObj->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
		$PHPExcelObj->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
		$PHPExcelObj->getActiveSheet()->getColumnDimension('D')->setWidth(22);
		$PHPExcelObj->getActiveSheet()->getColumnDimension('F')->setAutoSize(true);
		
		$i = 2;
		foreach ($achievementList as $key => $value) {

			//配置数据源
			$outputData = array();
			$outputData['user_phone'] = $value['user_phone'];        					//客户手机号
			$outputData['addtime'] = $value['addtime']; 								//通话时间
			$outputData['type'] = $value['type']; 										//电话类型
			$outputData['category'] = $value['category']; 								//外呼/咨询分类
			$outputData['call_status'] = $value['call_status']; 						//接通情况
			$outputData['admin_name'] = $value['admin_name'];       					//座席
			$outputData['remark'] = $value['remark'];       							//备注

			//填充数据
			$PHPExcelObj->getActiveSheet()->setCellValueExplicit('A' . $i, $outputData['user_phone']);
			$PHPExcelObj->getActiveSheet()->setCellValueExplicit('B' . $i, $outputData['addtime']);
			$PHPExcelObj->getActiveSheet()->setCellValueExplicit('C' . $i, $outputData['type']);
			$PHPExcelObj->getActiveSheet()->setCellValueExplicit('D' . $i, $outputData['category']);
			$PHPExcelObj->getActiveSheet()->setCellValueExplicit('E' . $i, $outputData['call_status']);
			$PHPExcelObj->getActiveSheet()->setCellValueExplicit('F' . $i, $outputData['admin_name']);
			$PHPExcelObj->getActiveSheet()->setCellValueExplicit('G' . $i, $outputData['remark']);
			$i++;
		}
		//审计日志
		$logParams['status'] = 'success';
		AuditLog::getInstance()->method('add', $logParams);

		//设置导出文件名
		if ($data['start_time'] && $data['end_time']){
			$file_name = "外呼咨询";
		}else{
			$file_name = "外呼咨询(已筛选)";  ///筛选的时间段
		}
		
		$outputFileName = $file_name . ' 列表 .xlsx';
		$xlsWriter = new PHPExcel_Writer_Excel2007($PHPExcelObj);

		// TODO: 兼容Excell2003
		$xlsWriter->setOffice2003Compatibility(true);
		header("Content-type: application/vnd.ms-excel");
		header("Content-Type: application/force-download");
		header("Content-Type: application/octet-stream");
		header("Content-Type: application/download");
		header('Content-Disposition:attachment;filename="' . $outputFileName . '"');
		header("Content-Transfer-Encoding: binary");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Pragma: no-cache");
		$xlsWriter->save("php://output");
		exit;
	}
	
	public function getPhoneByUserId($data=array()){
		Yii::log ( __FUNCTION__.print_r(func_get_args(),true),'info');
		$returnResult = array(
				'code'=>0,'info'=>'','data'=>array()
		);
		
		$user_id=intval($data['user_id']);
		$UserModel = new User();
		$criteria = new CDbCriteria;
		$attributes = array(
				"user_id"    =>   $user_id
		);
		$UserResult =$UserModel->findByAttributes($attributes,$criteria);
		
		$returnResult['code'] = 0;
		$returnResult['info'] = '获取用户手机号成功';
		$returnResult['data'] = $UserResult['phone'];
		return $returnResult;
	}
	
    /* =========================调取外呼接口相关======================== */
    /**
     * 循环curl winCall api
     * zlei
     */
    public function setWinCurlLoop($url,$params=array(),$method='POST'){
    	Yii::log ( __FUNCTION__.print_r(func_get_args(),true),'info');
    	$returnResult = array(
    			'code'=>0,'info'=>'','data'=>array()
    	);
    	 
    	for ($i=0;$i<2;$i++){
    		$code = 0;
    		try {
    			$return =  $this->setWinCurl($url,$params,$method);
    			$return = json_decode($return['body'],true);
    			if($return['code']==200) $return['code'] = 0;
    			//code=200 返回成功
    			$returnResult['code'] = isset($return['code']) ? $return['code'] : 112211; // 112211 接口不通或特殊处理
    			$returnResult['info'] = $return['message'];
    			$returnResult['data'] = isset($return['data'])?$return['data']:array();
    			if(isset($return['lastId'])){
    				$returnResult['data']['lastId'] = $return['lastId'];
    			}
    			if(isset($return['path'])){
    				$returnResult['data']['path'] = $return['path'];
    			}
    			break;
    		} catch (Exception $e) {
    			$code++;
    			Yii::log('curl wincall api error, Msg:'.print_r($e->getMessage(),true).' ! Time:'.date('Y-m-d H:i:s'),'error');
    		}
    	}
    	if($code>0){
    		$returnResult['code'] = 1;
    		$returnResult['info'] = '请求第三方失败';
    	}
    	return $returnResult;
    }
    /**
     * 设置调取外呼接口
     * zlei
     */
    public function setWinCurl($url,$params=array(),$method='POST'){
    	Yii::log ( __FUNCTION__.print_r(func_get_args(),true),'info');
    	if( empty($url) || !is_array($params) ){
    		$body = array(
    				'code'=>1003,
    				'message'=>'外呼缺少必要参数'
    		);
    		$return['body'] = json_encode($body);
    		return $return;
    	}
    	$method = strtoupper($method);
    	$params = http_build_query($params);
    	$nonce = rand(100000,999999).'itz'; //随机数
    	$Created = time(); //当前时间的时间戳
    	$secret = $this->_vcc_pwd; //账号对应的密码
    	$username = $this->_vcc_code; //企业代码vcc_code
    	$PasswordDigest = base64_encode(sha1(base64_decode($nonce).$Created.$secret, true));
    	$wsse = 'UsernameToken Username="'.$username.'",PasswordDigest="'.$PasswordDigest.'", Nonce="'.$nonce.'", Created="'.$Created.'"';
    	$header = array("X-WSSE"=>$wsse);
    	return $this->request($url, $params, $method, $header);
    }
    /**
     * 调取第三方外呼接口 -
     * @param unknown $url
     * @param unknown $params
     * @param unknown $method
     * @param unknown $my_header
     * @return boolean|multitype:unknown multitype:
     */
    public function request($url, $params, $method, $my_header){
    	Yii::log ( __FUNCTION__.print_r(func_get_args(),true),'info');
    	/* 开始一个新会话 */
    	$curl_session = curl_init();
    
    	/* 基本设置 */
    	curl_setopt($curl_session, CURLOPT_FORBID_REUSE, true); // 处理完后，关闭连接，释放资源
    	curl_setopt($curl_session, CURLOPT_HEADER, true);//结果中包含头部信息
    	curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, true);//把结果返回，而非直接输出
    	curl_setopt($curl_session, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);//采用1.0版的HTTP协议
    	$url_parts = $this->parse_raw_url($url); //处理URL
    	$header = array();
    	/* 设置主机 */
    	$header[] = 'Host: ' . $url_parts['host'];
    	/* 格式化自定义头部信息 */
    	if ($my_header && is_array($my_header)){
    		foreach ($my_header as $key => $value){
    			$header[] = $key . ': ' . $value;
    		}
    	}
    	if ($method === 'GET'){
    		curl_setopt($curl_session, CURLOPT_HTTPGET, true);
    		$url .= $params ? '?' . $params : '';
    	}else{
    		curl_setopt($curl_session, CURLOPT_POST, true);
    		$header[] = 'Content-Type: application/x-www-form-urlencoded';
    		$header[] = 'Content-Length: ' . strlen($params);
    		curl_setopt($curl_session, CURLOPT_POSTFIELDS, $params);
    	}
    	
    	/* 设置请求地址 */
    	curl_setopt($curl_session, CURLOPT_URL, $url);
    	/* 设置头部信息 */
    	curl_setopt($curl_session, CURLOPT_HTTPHEADER, $header);
    	/* 发送请求 */
    	$http_response = curl_exec($curl_session);
    		
    	if (curl_errno($curl_session) != 0){
    		Yii::log ( __FUNCTION__.'--curl 请求失败','error');
    		return false;
    	}
    	
    	$separator = '/\r\n\r\n|\n\n|\r\r/';
    	list($http_header, $http_body) = preg_split($separator, $http_response, 2);
    	
    	$http_response = array(
    			'header' => $http_header,//肯定有值
    			'body'   => $http_body //可能为空
    	); 
    	curl_close($curl_session);
    	Yii::log ( __FUNCTION__.'-WinCall CURL Result：-'.print_r($http_response,true),'info');
    	return $http_response;
    }
    private function parse_raw_url($raw_url){
    	$retval   = array();
    	$raw_url  = (string) $raw_url;
    	if (strpos($raw_url, '://') === false){
    		$raw_url = 'http://' . $raw_url;
    	}
    	$retval = parse_url($raw_url);
    	if (!isset($retval['path'])){
    		$retval['path'] = '/';
    	}
    	if (!isset($retval['port'])){
    		$retval['port'] = '80';
    	}
    	return $retval;
    }
    /* =========================调取外呼接口相关======================== */
    /**
     * 获取通话时间格式
     * @param number $start
     * @param number $end
     * @return number|string
     */
    public function getTimeDiff($diff=0){
    	$diff = intval($diff);
    	if($diff<=0){
    		return '0秒';
    	}
    	if ($diff >= 3600){
    		$string = gmstrftime('%H时%M分%S秒',$diff);
    		$string = ltrim($string,"0");
    	}else{
    		if ($diff >= 60){
    			$string = gmstrftime('%M分%S秒',$diff);
    			$string = ltrim($string,"0");
    		}else{
    			$string = gmstrftime('%S秒',$diff);
    			$string = ltrim($string,"0");
    		}
    	}
    	//    	$hour=floor($diff%86400/3600);
    	//    	$minute=floor($diff%86400/60);
    	//    	$second=floor($diff%86400%60);
    	//    	$string = '';
    	//    	$string.=$hour>0?$hour."时":'';
    	//    	$string.=$minute>0?$minute."分":'';
    	//    	$string.=$second>0?$second."秒":'';
    	return $string;
    } 
    
}


