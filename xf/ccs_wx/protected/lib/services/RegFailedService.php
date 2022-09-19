<?php
/**
 * 注册失败回访
 **/
class RegFailedService extends ItzInstanceService {
	
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
    	$order = ' order by action_time desc';
    	//条件筛选
    	if (count($data) > 0) {
    			
    		//IP搜索
    		if (isset($data['client_ip']) && $data['client_ip'] != '') {
    			$conditions .= ' and action_client_ip like  ' . '"%' . ip2long(htmlspecialchars(addslashes(trim($data['client_ip'])))) . '%"';
    		}
    		
    		//手机号搜索
    		if (isset($data['user_phone']) && $data['user_phone'] != '') {
    			$conditions .= ' and user_phone =  ' . "'" . trim($data['user_phone']) . "'";
    		}
    		
    		//依据行为发生时间搜索
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
	
    	$sql = " select count(*) num from ccs_reg_fail WHERE " . $conditions;
    	
    	$count = Yii::app()->ccsdb->createCommand($sql)->queryRow();
    	$listTotal = intval($count['num']);
    	if ($listTotal == 0) {
    		$returnResult['code'] = 0;
    		$returnResult['info'] = '暂无数据！';
    		return $returnResult;
    	}
    	$returnResult['data']['listTotal'] = $listTotal;
    	
    	$sql = " select * from ccs_reg_fail WHERE". $conditions;
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
    	
    	$call_status = array(1=>'未呼叫',2=>'已呼叫');
    	//判断脚本生成后是否有新注册成功的平台用户
    	$userInfo = BehaviorSearchService::getInstance()->getUserIdByPhone($data['user_phone']);	
    	
    	$data['is_user_tips'] = $userInfo ? '已注册成功' : '仍注册失败';
    	$data['call_status_tips'] = $call_status[$data['call_status']];
    	$data['action_client_ip_tips'] = long2ip($data['action_client_ip']);
    	$data['action_time_tips'] = date('Y-m-d H:i:s',$data['action_time']);
    	$data['user_phone_tips'] = substr_replace($data['user_phone'],'****',3,4);
    	return $data;
    }
    
    
 
}


