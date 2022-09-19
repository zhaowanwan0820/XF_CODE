<?php
/**
 * 充值失败回访
 **/
class RechargeFailedService extends ItzInstanceService {
	
	public function __construct()
    {
    	parent::__construct();
    } 
    
    
    /**
     * 获取列表
     * @param array $data
     */
    public function getFailedList($data=array(),$page=1,$limit=10){
    	
    	Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
    	$returnResult = array(
    			'code' => '', 'info' => '', 'data' => array('listTotal' => 0, 'listInfo' => array())
    	);
    	
    	$conditions = ' 1 = 1 ';
    	$order = ' order by action_time desc,trade_no desc';
    	//条件筛选
    	if (count($data) > 0) {
    			
    		//用户ID搜索
    		if (isset($data['user_id']) && $data['user_id'] != '') {
    			$conditions .= ' and user_id = '.intval($data['user_id']);
    		}
    		
    		//用户名搜索
    		if (isset($data['user_name']) && $data['user_name'] != '') {
    			$conditions .= ' and user_name like  ' . '"%' . htmlspecialchars(addslashes(trim($data['user_name']))) . '%"';
    		}
    		
    		//手机号搜索
    		if (isset($data['user_phone']) && $data['user_phone'] != '') {
    			$conditions .= ' and user_phone =  ' . "'" . trim($data['user_phone']) . "'";
    		}
    		
    		//订单状态搜索
    		if (isset($data['action_status']) && $data['action_status'] != '') {
    			$conditions .= ' and action_status = '.intval($data['action_status']);
    		}
    		
    		//用户类型搜索
    		if (isset($data['user_type']) && $data['user_type'] != '') {
    			$conditions .= ' and user_type = '.intval($data['user_type']);
    		}
    		
    		//充值绑卡步骤
    		if (isset($data['action_detail']) && $data['action_detail'] != '') {
    			if(in_array($data['action_detail'], array(5,6))){ //绑卡行为明细转化
    				if($data['action_detail'] == 5) {
    					$action_detail = 1;
    				}else if($data['action_detail'] == 6){
    					$action_detail = 2;
    				}
    				$conditions .= ' and action_type = 2 and action_detail = '.intval($action_detail);
    			} else {
    				$conditions .= ' and action_type = 1 and action_detail = '.intval($data['action_detail']);
    			}
    			
    		}
    		
    		//过期时间搜索
    		if( (isset($data['begin_addtime']) && $data['begin_addtime']!='') && (isset($data['end_addtime']) && $data['end_addtime']!='') ){
    			$conditions .= " and action_time between ".intval($data['begin_addtime'])." and ".intval($data['end_addtime']);
    		}else{
    			if(isset($data['begin_addtime']) && $data['begin_addtime']!=''){
    				$conditions .= " and action_time >= ".intval($data['begin_addtime']);
    			}elseif (isset($data['end_addtime']) && $data['end_addtime']!=''){
    				$conditions .= " and action_time <= ".intval($data['end_addtime']);
    			}elseif (!isset($data['begin_addtime']) || isset($data['begin_addtime'])){
    				//设置默认时间,范围是前一天的17:50至当前时间
    				$work_yesterday_endtime = strtotime(date('Y-m-d'))-22200;
    				$conditions .= " and action_time between ".$work_yesterday_endtime." and ".time();
    			}
    		}
    		
    		//分页条数设置
    		$limit = in_array($data['limit'], array(10, 20, 30, 40, 50)) ? intval($data['limit']) : $limit;
    		//请求页数
    		$page = (isset($data['page']) && $data['page'] != '') ? intval($data['page']) : $page;
    	}
	
    	$sql = " select count(*) num from ccs_user_action WHERE " . $conditions;
    	
    	$count = Yii::app()->ccsdb->createCommand($sql)->queryRow();
    	$listTotal = intval($count['num']);
    	if ($listTotal == 0) {
    		$returnResult['code'] = 0;
    		$returnResult['info'] = '暂无数据！';
    		return $returnResult;
    	}
    	$returnResult['data']['listTotal'] = $listTotal;
    	
    	$sql = " select * from ccs_user_action WHERE". $conditions;
    	$sql .= $order;
    	$offsets = ($page - 1) * $limit;
    	$sql .= " LIMIT $offsets,$limit";
    	$list = Yii::app()->ccsdb->createCommand($sql)->queryAll();
    	
    	foreach ($list as $key=>$value){
    		$listInfo[] = $this->listResTrans($value);
    	}
    	$returnResult['code'] = 0;
    	$returnResult['info'] = '获取列表成功';
    	$returnResult['data']['listInfo'] = $listInfo;
    	return $returnResult;
    }

    //数据转化
    public function listResTrans($data=array()){
    	Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
    
    	$action_type = array(1=>'充值',2=>'绑卡');
    	$action_detail = array(1=>'第一步',2=>'第二步',3=>'第三步',4=>'网银跳转');
    	$action_system = array(1=>'Pc',2=>'Andriod',3=>'ios',4=>'Wap');
    	$action_status = array(1=>'未成功',2=>'成功');
    	$call_status = array(1=>'未呼叫',2=>'已呼叫');
    	$user_type = array(1=>'老用户',2=>'新用户');
    	$user_sex = array(0=>'未知',1=>'男',2=>'女');
    	
    	$data['action_type_tips'] = $action_type[$data['action_type']];
    	$data['action_detail_tips'] = $action_detail[$data['action_detail']];
    	$data['action_system_tips'] = $action_system[$data['action_system']];
    	$data['action_status_tips'] = $action_status[$data['action_status']];
    	$data['call_status_tips'] = $call_status[$data['call_status']];
    	$data['user_type_tips'] = $user_type[$data['user_type']];
    	$data['user_sex_tips'] = $user_sex[$data['user_sex']];
    	$data['action_time_tips'] = date('Y-m-d H:i:s',$data['action_time']);
    	$data['user_name'] = mb_substr($data['user_name'],0,3);
    	return $data;
    }
    
 
}


