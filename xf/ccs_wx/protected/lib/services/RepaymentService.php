<?php

class RepaymentService extends ItzInstanceService
{

    public function __construct()
    {
        parent::__construct();
    }
    
	/**
	 * 列表
	 */
    public function getRepaymentList($data = array(), $limit = 10, $page = 1)
    {
    	Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
    	$returnResult = array(
    			'code' => '', 'info' => '', 'data' => array('listTotal' => 0, 'listInfo' => array())
    	);
    	
    	$now_time = time();
    	$conditions = ' 1 = 1 ';
    	$order = ' order by id desc ';
    
    	if (count($data) > 0) {
    		
    		//状态
    		if(isset($data['status']) && $data['status']!=''){
    			$conditions .= ' and status = '.intval($data['status']);
    		}
    		
    		/* //名称
    		if (isset($data['name']) && $data['name'] != '') {
    			$conditions .= ' and name like  ' . '"%' . htmlspecialchars(addslashes(trim($data['name']))) . '%"';
    		} */
    			
    		//类型
    		if (isset($data['type']) && $data['type'] != '') {
    			$conditions .= ' and type = '.intval($data['type']);
    		}
    		
    		//时间范围
    		if( (isset($data['begin_time']) && $data['begin_time']!='') && (isset($data['end_time']) && $data['end_time']!='') ){
    			$conditions .= " and plan_time >= ".intval($data['begin_time'])." and plan_time <=".intval($data['end_time']);
    		}
    			
    		//分页条数设置          
    		$limit = in_array($data['limit'], array(10, 20, 30, 40, 50)) ? intval($data['limit']) : $limit;
    		//请求页数
    		$page = (isset($data['page']) && $data['page'] != '') ? intval($data['page']) : $page;
    	}
    	
    	$sql = " select count(*) num from itz_repayment_plan where " . $conditions;
    	$count = Yii::app()->dwdb->createCommand($sql)->queryRow();
    	$listTotal = intval($count['num']);
    	if ($listTotal == 0) {
    		$returnResult['code'] = 0;
    		$returnResult['info'] = '暂无数据！';
    		return $returnResult;
    	}
    	$returnResult['data']['listTotal'] = $listTotal;
    	$sql = "select CASE WHEN t.type='12' THEN 1 ELSE 2 END flag,t.* from itz_repayment_plan t where ".$conditions." order by flag asc,id desc";
    	
    	$offsets = ($page - 1) * $limit;
    	$sql .= " LIMIT $offsets,$limit";
    	$list = Yii::app()->dwdb->createCommand($sql)->queryAll();
    	$listInfo = array();
    	foreach ($list as $key=>$value){
    		$listInfo[] = $this->activityResTrans($value);
    	}
    	
    	$returnResult['code'] = 0;
    	$returnResult['info'] = 'success';
    	$returnResult['data']['listInfo'] = $listInfo;
    	return $returnResult;
    }
    
    
	/**
	 * 添加还款计划
	 * @param array $data
	 * @return array
	 */
	public function addRepaymentPlan($data=array()){
		
		Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
		$returnResult = array(
			'code' => '1', 'info' => 'error', 'data' => array()
		);
		
 		$now_time = time();
		//数据
		$planInfo['type'] = isset($data['type']) ? intval($data['type']) : 0;
		$planInfo['normal_time'] = isset($data['normal_time']) ? intval($data['normal_time']) : 0;
		$planInfo['borrow_list'] = isset($data['borrow_list']) ? addslashes(trim($data['borrow_list'])) : 0;
		$planInfo['wise_list'] = isset($data['wise_list']) ? addslashes(trim($data['wise_list'])) : 0;
		$planInfo['repayment_percent'] = isset($data['repayment_percent']) ? $data['repayment_percent'] : 0;
		$planInfo['repayment_total'] = isset($data['repayment_total']) ? $data['repayment_total'] : 0;
		$planInfo['plan_time'] = isset($data['plan_time']) ? intval($data['plan_time']) : 0;
		$planInfo['remark'] = isset($data['remark']) ? $data['remark']: '';
		$planInfo['status'] = 1;
		$planInfo['admin_id'] = Yii::app()->user->id;
		$planInfo['admin_name'] = Yii::app()->user->name;
		$planInfo['addtime'] = $now_time;
		
		//智选计划暂停还款时间
		if($data['type'] == 8 || $data['type'] == 9){
			$planInfo['wise_list'] = $planInfo['borrow_list'];
		}
		
		//智选计划暂停还款时间
		if($data['type'] == 12){
			$planInfo['wise_list'] = $planInfo['borrow_list'];
			$planInfo['plan_time'] = $planInfo['normal_time'] = time();
			$planInfo['remark'] = '智选计划暂停还款';
		}
		
		$checkPlanResult = $this->checkAddPlan($planInfo);
		if ($checkPlanResult['code']){
			return $checkPlanResult;
		}
		
		$addInfos = $checkPlanResult['data'];
		
		$addPlanRes = $this->addRepaymentPlanSystem($addInfos);
    	if ($addPlanRes) {
    		$returnResult['data'] = $addPlanRes;
    		$returnResult['code'] = 0;
    		$returnResult['info'] = '添加成功';
    	} else {
    		Yii::log(__FUNCTION__ . " add itz_repayment_plan fail borrow_list=".json_decode($planInfo['borrow_list']), CLogger::LEVEL_ERROR);
    		$returnResult['info'] = '添加失败';
    	}
		return $returnResult;
	}
	
	public function editRepaymentPlan($data){
		Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
		$returnResult = array(
			'code' => '1', 'info' => 'error', 'data' => array()
		);
		
		$now_time = time();
		$planInfoId = isset($data['id']) ? intval($data['id']) : 0;
		if (empty($planInfoId)){
			$returnResult['info'] = "参数错误";
			return $returnResult;
		}
		//type 1直接修改  2申请修改 
		$planInfo['type'] = isset($data['type']) ? intval($data['type']) : 0;
		$planInfo['normal_time'] = isset($data['normal_time']) ? intval($data['normal_time']) : 0;
		$planInfo['borrow_list'] = isset($data['borrow_list']) ? addslashes(trim($data['borrow_list'])) : 0;
		$planInfo['wise_list'] = isset($data['wise_list']) ? addslashes(trim($data['wise_list'])) : 0;
		$planInfo['repayment_percent'] = isset($data['repayment_percent']) ? $data['repayment_percent'] : 0;
		$planInfo['repayment_total'] = isset($data['repayment_total']) ? $data['repayment_total'] : 0;
		$planInfo['plan_time'] = isset($data['plan_time']) ? intval($data['plan_time']) : 0;
		$planInfo['remark'] = isset($data['remark']) ? $data['remark']: '';
		$planInfo['status'] = 1;
		$planInfo['admin_id'] = Yii::app()->user->id;
		$planInfo['admin_name'] = Yii::app()->user->name;
		$planInfo['addtime'] = $now_time;
		
		//智选计划暂停还款时间
		if($data['type'] == 8 || $data['type'] == 9){
			$planInfo['wise_list'] = $planInfo['borrow_list'];
		}
		
		//智选计划暂停还款时间
		if($data['type'] == 12){
			$planInfo['wise_list'] = $planInfo['borrow_list'];
			$planInfo['plan_time'] = $planInfo['normal_time'] = time();
			$planInfo['remark'] = '智选计划暂停还款';
		}

		$checkPlanResult = $this->checkAddPlan($planInfo);
		if ($checkPlanResult['code']){
			return $checkPlanResult;
		}
		$planInfo = $checkPlanResult['data'];
		$updateRes = Yii::app()->dwdb->createCommand()->update('itz_repayment_plan', $planInfo, 'id=:id', array(':id' => $planInfoId));
		if (!$updateRes){
			Yii::log(__FUNCTION__ . " update itz_repayment_plan fail id=$planInfoId".",admin_id=".$planInfo['admin_id'], CLogger::LEVEL_ERROR);
			$returnResult['info'] = "编辑失败";
		}
		$returnResult['code'] = '0';
		$returnResult['info'] = '编辑成功';
		
		return $returnResult;
	}
	
	/*
	 * 查看还款计划
	 */
	public function getRepaymentPlanInfo($data = array())
	{
		Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
		$returnResult = array(
				'code' => '1', 'info' => 'error', 'data' => array('listInfo' => array())
		);
		$planInfoId = isset($data['id']) ? intval($data['id']) : '';
		if (empty($planInfoId)) {
			$returnResult['info'] = '缺少id';
			return $returnResult;
		}
		$sql = " select * from itz_repayment_plan where id = " . $planInfoId;
		$planInfo = Yii::app()->dwdb->createCommand($sql)->queryRow();
		if ($planInfo) {
			$returnResult['code'] = 0;
			$returnResult['info'] = '获取成功';
			$returnResult['data']['listInfo'] = $planInfo;
		} else {
			$returnResult['info'] = '数据不存在';
		}
		return $returnResult;
	}
	
	
	public function checkAddPlan($data=array()){
		Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
		$returnResult = array(
				'code' => '1', 'info' => 'error', 'data' => array()
		);
		
		//验证正整数和百分比的正则
		$int_pattern = "/^[1-9]\d*$/";
		$credit_pattern = "/^[0-9]+(.[0-9]{1,2})?$/";
		$sum_total = 0;
		$borrow_list = explode(',',$data['borrow_list']);
		//正常还款的时间范围
		$normal_time= $data['normal_time'];
		$plan_time = $data['plan_time'];
		$normal_time_condition = " and repay_time ={$normal_time}";
		
		
		//验证
		if (!isset($data['type']) || $data['type'] < 0 || $data['type'] > 12){
			$returnResult['info'] = '还款类型选择错误';
			return $returnResult;
		}
	
		//开始时间不可为空白
		if (!isset($data['normal_time'])){
			$returnResult['info'] = '正常还款时间不可为空';
			return $returnResult;
		}
		
		//结束时间不可为空白
		if (!isset($data['plan_time'])){
			$returnResult['info'] = '计划还款时间不可为空';
			return $returnResult;
		}
	
		/* if (mb_strlen($data['remark']) < 1 || mb_strlen($data['remark']) > 500){
			$returnResult['info'] = '备注1~500个字之间';
			return $returnResult;
		} */
	
		//百分比还款需要验证百分比
		if($data['type'] == 5){
			if($data['repayment_percent'] < 0 || $data['repayment_percent'] > 99 ||!preg_match($credit_pattern,$data['repayment_percent'])){
				$returnResult['info'] = '百分比需小于100，且保留两位小数';
				return $returnResult;
			}
		}
		
		//智选计划延期还本只能有一个小标
		if($data['type'] == 9){
			if(count($borrow_list) > 1 ){
				$returnResult['info'] = '智选计划延期还本只能有一个小标';
				return $returnResult;
			}
		}
		
		
		if($data['type'] == 12){
			foreach ($borrow_list as $k =>$v){
				$sql = "SELECT borrow_id FROM itz_stat_repay where  wise_borrow_id ='{$v}'";
				$res = Yii::app()->dwdb->createCommand($sql)->queryRow();
			}
			
			if(empty($res)){
				$returnResult['info'] = '不存在项目'.$v.'的还款数据';
				return $returnResult;
			}
			
			$returnResult['code'] = 0;
			$returnResult['info'] = 'success';
			$returnResult['data'] = $data;
			
			return $returnResult;
		}else if($data['type'] == 11){
			if(empty($data['wise_list'])){
				$returnResult['info'] = '请填写智选集合小项目ID';
				return $returnResult;
			}
			if(count($borrow_list)>1){
				$returnResult['info'] = '此类型只允许填写一个大项目ID';
				return $returnResult;
			}
			$wise_list = explode(',',$data['wise_list']);
			$table_name = FunctionUtil::getWiseCollectionName($data['normal_time']);
			foreach($wise_list as $k =>$v){
				$table_name_sql = "SELECT formal_time FROM itz_wise_borrow where wise_borrow_id='{$v}'";
				$table_name_res = Yii::app()->dwdb->createCommand($table_name_sql)->queryRow();
				$table_name = FunctionUtil::getWiseCollectionName($table_name_res['formal_time']);
				$sql = "SELECT wise_borrow_id,repay_time,sum(interest+capital) total FROM {$table_name} where status=0 and wise_borrow_id='{$v}'".$normal_time_condition;
				$res = Yii::app()->rwisedb->createCommand($sql)->queryRow();
				if(empty($res)){
					$returnResult['info'] = '当天不存在项目wise_borrow_id为'.$v.'的还款数据';
					return $returnResult;
				}
				$sum_total += $res['total'];
			}
			
		}else if($data['type'] == 10){ //智选集合延期还款
			if(count($borrow_list)>1){
				$returnResult['info'] = '此类型只允许填写一个大项目ID';
				return $returnResult;
			}
			if(empty($data['wise_list'])){ //不填写wise_list则查询borrow_id所有wise_borrow_id
				$sql = "SELECT wise_borrow_id FROM itz_wise_borrow where borrow_id=".$data['borrow_list'];
				$res = Yii::app()->dwdb->createCommand($sql)->queryAll();
				//$wise_list = array_column($res, 'wise_borrow_id');
				foreach($res as $k=>$v){
					$wise_list[] = $v['wise_borrow_id'];
				}
				$data['wise_list']=implode(',',$wise_list);
			}else{
				$wise_list = explode(',',$data['wise_list']);
			}
			foreach($wise_list as $k =>$v){
				$table_name_sql = "SELECT formal_time FROM itz_wise_borrow where wise_borrow_id='{$v}'";
				$table_name_res = Yii::app()->dwdb->createCommand($table_name_sql)->queryRow();
				$table_name = FunctionUtil::getWiseCollectionName($table_name_res['formal_time']);
				$sql = "SELECT id,repay_time,sum(interest+capital) total FROM {$table_name} where status=0 and wise_borrow_id ='{$v}'".$normal_time_condition;
				$res = Yii::app()->rwisedb->createCommand($sql)->queryRow();
				if(empty($res)){
					$returnResult['info'] = '当天不存在项目wise_borrow_id为'.$v.'的还款数据';
					return $returnResult;
				}
				$sum_total += $res['total'];
			}
			
		}else{
			foreach ($borrow_list as $k =>$v){
				switch ($data['type']){
						case 1:	//省心计划正常还本息
							$sql = "SELECT borrow_id,repay_time,sum(interest+capital) total FROM dw_borrow_collection WHERE status in (0,16) and type!=5 and borrow_id=".$v.$normal_time_condition;
							$res = Yii::app()->dwdb->createCommand($sql)->queryRow();
							break;
						case 6: //省心计划正常还款只还息
							$sql = "SELECT borrow_id,repay_time,sum(interest) total  FROM dw_borrow_collection WHERE status in (0,16) and type != 5 and borrow_id=".$v.$normal_time_condition;
							$res = Yii::app()->dwdb->createCommand($sql)->queryRow();
							break;
						case 7: //省心计划正常还款只还本
							$sql = "SELECT borrow_id,repay_time,repay_account,sum(capital) total FROM dw_borrow_collection WHERE status in (0,16) and borrow_id=".$v.$normal_time_condition;
							$res = Yii::app()->dwdb->createCommand($sql)->queryRow();
							break;
						case 5:	//省心计划延期按百分比还本
							$sql = "SELECT id,repayment_time,sum(account_yes) total_original FROM dw_borrow WHERE status=3 and id={$v} and repayment_time = {$normal_time}";
							$res = Yii::app()->dwdb->createCommand($sql)->queryRow();
							$res['total'] = round($res['total_original'] * $data['repayment_percent']/100);
							break;
						case 8: //智选计划还息
							$sql = "SELECT borrow_id,repay_time,sum(interest) total FROM itz_stat_repay where  wise_borrow_id ='{$v}'".$normal_time_condition;
							$res = Yii::app()->dwdb->createCommand($sql)->queryRow();
							break;
						case 9: //智选计划还本
							$sql = "SELECT borrow_id,repay_time,sum(capital) total FROM itz_stat_repay where  wise_borrow_id ='{$v}'".$normal_time_condition;
							$res = Yii::app()->dwdb->createCommand($sql)->queryRow();
							break;
						case 2: //省心计划延期还息
							$sql = "SELECT borrow_id,repay_time,sum(interest) total  FROM dw_borrow_collection WHERE status in (0,16) and type != 5 and borrow_id={$v} and repay_time between {$normal_time} and {$plan_time}";
							$res = Yii::app()->dwdb->createCommand($sql)->queryRow();
							break;
						case 4: //延期还本息
							$sql = "SELECT borrow_id,repay_time,sum(interest+capital) total FROM dw_borrow_collection WHERE status in (0,16) and type!=5 and borrow_id={$v} and repay_time between {$normal_time} and {$plan_time}";
							$res = Yii::app()->dwdb->createCommand($sql)->queryRow();
							break;
						case 3: //省心计划延期还本
							$sql = "SELECT borrow_id,repay_time,repay_account,sum(capital) total FROM dw_borrow_collection WHERE status in (0,16) and borrow_id={$v} and repay_time between {$normal_time} and {$plan_time}";
							$res = Yii::app()->dwdb->createCommand($sql)->queryRow();
							break;
						/* case 10: //智选集合延期还款（不区分本息）
							$table_name_sql = "SELECT formal_time FROM itz_wise_borrow where wise_borrow_id='{$v}'";
							$table_name_res = Yii::app()->dwdb->createCommand($table_name_sql)->queryRow();
							$table_name = FunctionUtil::getWiseCollectionName($table_name_res['formal_time']);
							$sql = "SELECT id,repay_time,sum(interest+capital) total FROM {$table_name} where status=0 and borrow_id=".$v.$normal_time_condition;
							$res = Yii::app()->rwisedb->createCommand($sql)->queryRow();
							break; */
				}
				
				if(empty($res['borrow_id']) && empty($res['id']) ){
					$returnResult['info'] = '当天不存在项目'.$v.'的还款数据';
					return $returnResult;
				}
				$sum_total += $res['total'];
				
			}
		}
		
		//容错 还款额误差在2元内
		if(!($data['repayment_total']-2 <= $sum_total && $data['repayment_total']+2 >=$sum_total)){
			$returnResult['info'] = '还款总额和输入的项目还款总额度不一致(误差较大)';
			return $returnResult;
		}
		
		/* $repay_time=getdate($res['repay_time']);
		$normal_repay_time=getdate($data['normal_time']);
		
		if(!(($repay_time['year']===$normal_repay_time['year']) && ($normal_repay_time['yday']===$repay_time['yday']))){
			$returnResult['info'] = 'borrow_id='.$v.'正常还款时间与库中数据不符';
			return $returnResult;
		} */
	
		
		$returnResult['code'] = 0;
		$returnResult['info'] = 'success';
		$returnResult['data'] = $data;
	
		return $returnResult;
	}
	
	/**
	 * 添加RepaymentPlan表
	 * @param $data
	 * @return bool
	 */
	public function addRepaymentPlanSystem($data)
	{
		Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
		$RepaymentPlan_model = new ItzRepaymentPlan();
		foreach ($data as $key => $value) {
			$RepaymentPlan_model->$key = $value;
		}
		if ($RepaymentPlan_model->save() == false) {
			Yii::log("ItzRepaymentPlan_model error: " . print_r($RepaymentPlan_model->getErrors(), true), "error");
			return false;
		} else {
			return $RepaymentPlan_model->attributes['id'];
		}
	}
	
	
	
	/**
	 * 启用
	 * @param array $data
	 * @return array
	 */
	public function editRepaymentPlanStatus($data=array()){
		Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
		$returnResult = array(
				'code' => '1', 'info' => 'error', 'data' => array()
		);
		$now_time = time();
		$planID = isset($data['id']) ? intval($data['id']) : 0;
		$planInfo['status'] = 2;
		if (empty($planID) || empty($planInfo['status'])){
			$returnResult['info'] = "参数错误,请重试!";
			return $returnResult;
		}
		
		$startRes = Yii::app()->dwdb->createCommand()->update('itz_repayment_plan', $planInfo, 'id=:id', array(':id' => $planID));
		if (!$startRes){
			Yii::log(__FUNCTION__ . " start itz_repayment_plan fail id=$planID", CLogger::LEVEL_ERROR);
			$returnResult['info'] = "启动失败";
			return $returnResult;
		}
		$returnResult['code'] = 0;
		$returnResult['info'] = "启动成功";
		
		return $returnResult;
	}
	
	/**
	 * 结果转化
	 */
	public function activityResTrans($data=array()){
		Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
	
		$now_time = time();
		$status_tips = array(1=>'未启动',2=>'已启动',3=>'新网端还款中',4=>'全部成功',5=>'执行失败',6=>'爱投资端还款中',7=>'还款记录创建中');
		$type_tips = array(
			1=>'省心计划正常还本息',
			2=>'省心计划延期还息',
			3=>'省心计划延期还本',
			4=>'省心计划延期还本息',
			5=>'省心计划延期按百分比还本',
			6=>'省心计划正常还款只还息',
			7=>'省心计划正常还款只还本',
			8=>'智选计划延期还息',
			9=>'智选计划延期还本',
			10=>'智选集合延期还款（不区分息）',
			11=>'智选集合正常还款之部分还款',
			12=>'智选计划暂停还款',
			13=>'省心计划展期项目创建还款记录');
	
		$data['status_tips'] = $status_tips[$data['status']];
		$data['type_tips'] = $type_tips[$data['type']];
		$data['normal_time_tips'] = isset($data['normal_time']) ? date('Y-m-d',$data['normal_time']) : '--';
		$data['plan_time_tips'] = isset($data['plan_time']) ? date('Y-m-d',$data['plan_time']) : '--';
		$data['task_start_time_tips'] = isset($data['task_start_time']) ? date('Y-m-d H:i:s',$data['task_start_time']) : '--';
		$data['task_end_time_tips'] = isset($data['task_end_time']) ? date('Y-m-d H:i:s',$data['task_end_time']) : '--';
		$data['addtime_tips'] = isset($data['addtime']) ? date('Y-m-d H:i:s',$data['addtime']) : '--';
		$data['wise_list'] = empty($data['wise_list']) ?  '--' : $data['wise_list'];
		$data['repayment_percent'] = $data['type'] !=5 ? '--': $data['repayment_percent']."%";
		if($data['type'] ==12){
			$data['normal_time_tips'] = '--';
			$data['plan_time_tips'] =  '--';
			$data['repayment_total']= '--';
			$data['borrow_list']= '--';
		}

		if($data['status'] < 3){
			$data['task_start_time_tips']= '--';
			$data['task_end_time_tips']= '--';
			$data['task_remark']= '--';
		}else{
			 $result[0]= "执行时间：{$data['task_start_time_tips']}";
			 $result[1]= "执行结束时间：{$data['task_end_time_tips']}";
			 $result[2]= "执行备注：{$data['task_remark']}";
			 $data['result']=$result;
		}
		
		return $data;
	}
	
	
	
	

}