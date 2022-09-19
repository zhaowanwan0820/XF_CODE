<?php

class ShopService extends ItzInstanceService
{

    public function __construct()
    {
        parent::__construct();
    }
    
	/**
	 * 列表
	 * hl
	 */
    public function getAuditList($data = array(), $limit = 10, $page = 1)
    {
    	Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
    	$returnResult = array(
    			'code' => '', 'info' => '', 'data' => array('listTotal' => 0, 'listInfo' => array())
    	);
    	$now_time = time();
    	$conditions = ' where auth_status <> 0 ';
    	$order = ' order by FIELD(`auth_status`,1,3,2) asc,auth_time desc,id desc';
    	//条件筛选
    	if (count($data) > 0) {
 
    		//ID搜索
    		if (isset($data['user_id']) && $data['user_id'] != '') {
    			$conditions .= ' and user_id = '.intval($data['user_id']);
    		}
    		
    		//用户名称搜索
    		if (isset($data['realname']) && $data['realname'] != '') {
    			$user_id = $this->getAllUserIdByName($data['realname']);
    			$conditions .= ' and user_id in ('.$user_id.')';
    		}
    		
    		//手机号搜索
    		if (isset($data['phone']) && $data['phone'] != '') {
    			$user_id = $this->getAllUserIdByPhone($data['phone']);
    			$conditions .= ' and user_id in ('.$user_id.')';
    		}
    		
    		//类型搜索
    		if (isset($data['auth_status']) && $data['auth_status'] != '') {
    			$conditions .= ' and auth_status = '.intval($data['auth_status']);
    		}
    		
    		
    		
    		//分页条数设置
    		$limit = in_array($data['limit'], array(10, 20, 30, 40, 50)) ? intval($data['limit']) : $limit;
    		//请求页数
    		$page = (isset($data['page']) && $data['page'] != '') ? intval($data['page']) : $page;
    	}
    
    	$sql = " select count(*) num from itz_auth_debt_exchange_log".$conditions;
    	$count = Yii::app()->dwdb->createCommand($sql)->queryRow();
    	$listTotal = intval($count['num']);
    	if ($listTotal == 0) {
    		$returnResult['code'] = 0;
    		$returnResult['info'] = '暂无数据！';
    		return $returnResult;
    	}
    	$returnResult['data']['listTotal'] = $listTotal;
   
    	$sql = "select * from itz_auth_debt_exchange_log".$conditions;
    	$sql .= $order;
    	$offsets = ($page - 1) * $limit;
    	$sql .= " LIMIT $offsets,$limit";
    	$list = Yii::app()->dwdb->createCommand($sql)->queryAll();

     	foreach ($list as $key=>$value){
    		$listInfo[] = $this->resTrans($value);
    		
    	}
    	$returnResult['code'] = 0;
    	$returnResult['info'] = '获取列表成功';
    	$returnResult['data']['listInfo'] = $listInfo;
    	return $returnResult;
    }

    /**
     * 字段转化
     */
    public function resTrans($data=array()){
    	Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
    	
    	
    	$sql = "select phone,realname from dw_user where user_id=".intval($data['user_id']);
    	$srcinfo = Yii::app()->dwdb->createCommand($sql)->queryRow();
    	
    	$status_tips = array(0=>'未授权',1=>'已授权待审核',2=>'审核通过',3=>'审核未通过'); 
    	
    	$data['phone'] = $srcinfo['phone'];
    	$data['realname'] = $srcinfo['realname'];
    	$data['auth_time'] = $data['auth_status'] >1 ? date('Y-m-d H:i:s',$data['auth_time']) : '--';
    	$data['addtime'] = date('Y-m-d H:i:s',$data['addtime']);
    	$data['auth_status_tips'] = $status_tips[$data['auth_status']];
    	return $data;
    }
    

	/**
	 * 详情
	 */
	public function getAuditInfo($data=array()){
		Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
		$returnResult = array(
				'code' => '1', 'info' => 'error', 'data' => array("listInfo"=>array())
		);
		$id = isset($data['id']) ? intval($data['id']) : 0;
		if (empty($id)){
			$returnResult['info'] = "id不存在";
			return $returnResult;
		}
		
		$sql = "select l.*,u.realname from itz_auth_debt_exchange_log l left join dw_user u on l.user_id=u.user_id where l.id = ".$id;
		$info = Yii::app()->dwdb->createCommand($sql)->queryRow();
	
		
		try {
			$oss = Yii::app()->oss;
			$info['card1'] = $oss->signUrl($oss->bucket_attachment, $info['card1'],300);
			// 没有异常就算成功
		} catch (Exception $e) {
			return $this->result(100,$e->getMessage());
		}
		
		
		$status_tips = array(0=>'未授权',1=>'已授权待审核',2=>'审核通过',3=>'审核未通过');
		$info['auth_time'] = date('Y-m-d H:i:s',$info['auth_time']);
		$info['addtime'] = date('Y-m-d H:i:s',$info['addtime']);
		$info['auth_status_tips'] = $status_tips[$info['auth_status']];
		if (empty($info)){
			$returnResult['info'] = "数据不存在";
		}else{
			$returnResult['code'] = 0;
			$returnResult['info'] = "success";
			$returnResult['data']['listInfo'] = $info;
		}
		return $returnResult;
		
	}
	
	
	public function getAuditStat($data=array()){
		Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
		$returnResult = array(
				'code' => '1', 'info' => 'error', 'data' => array("listInfo"=>array())
		);
		
	
		$sql = "select count(*) num,
				sum(case when auth_status=1 then 1 else 0 end) wait_status,
				sum(case when auth_status=2 then 1 else 0 end) succ_status,
				sum(case when auth_status=3 then 1 else 0 end) error_status from  itz_auth_debt_exchange_log ";
		$info = Yii::app()->dwdb->createCommand($sql)->queryRow();

		
		$returnResult['code'] = 0;
		$returnResult['info'] = "success";
		$returnResult['data']['listInfo'] = $info;
		return $returnResult;
	
	}
	
	

	/**
	 * 审核
	 */
	public function EditAudit($data=array()){
		Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
		$returnResult = array(
				'code' => '1', 'info' => 'error', 'data' => array()
		);
		
		$auditInfo = array();
		$now_time = time();
		$auditID = isset($data['coupon_id']) ? intval($data['coupon_id']) : 0;
		$auditInfo['id'] = isset($data['id']) ? intval($data['id']) : 0;
		$auditInfo['auth_status'] = isset($data['auth_status']) ? intval($data['auth_status']) : 0;
		//$auditInfo['auth_info'] =  $data['type'] ? $data['type'] : '';
		$auditInfo['auth_time'] = $now_time;
		$user_id = isset($data['user_id']) ? intval($data['user_id']) : 0;
		if($data['type'] == 3){
			if (mb_strlen($data['remark'],'UTF-8') > 20){
				$returnResult['info'] = '不通过原因不能多于20个字';
				return $returnResult;
			}
			$auditInfo['auth_info'] =  $data['remark'];
		}else{
			$auditInfo['auth_info'] =  $data['type'] ? $data['type'] : '';
		}
		
		
		Yii::app()->dwdb->beginTransaction();
		try {
			$sql= "update itz_auth_debt_exchange_log set auth_status={$auditInfo['auth_status']} ,auth_info = '{$auditInfo['auth_info']}',auth_time={$now_time}  where id={$auditID}";
			$updateRes = Yii::app()->dwdb->createCommand($sql)->execute();
			//$updateRes = Yii::app()->dwdb->createCommand()->update('itz_auth_debt_exchange_log', $auditInfo, 'id=:id', array(':id' => $auditID));
			if (!$updateRes){
				Yii::app()->dwdb->rollback();
				Yii::log(__FUNCTION__ . " itz_auth_debt_exchange_log fail id=$auditID", CLogger::LEVEL_ERROR);
				$returnResult['info'] = "审核失败";
				return $returnResult;
			}
		
				
			$sql= "update itz_auth_debt_exchange set auth_status={$auditInfo['auth_status']} ,updatetime={$now_time}  where user_id={$user_id}";
			$updateRes = Yii::app()->dwdb->createCommand($sql)->execute();
			//$updateRes = Yii::app()->dwdb->createCommand()->update('itz_auth_debt_exchange_log', $auditInfo, 'id=:id', array(':id' => $auditID));
			if (!$updateRes){
				Yii::app()->dwdb->rollback();
				Yii::log(__FUNCTION__ . " itz_auth_debt_exchange fail id=$auditID", CLogger::LEVEL_ERROR);
				$returnResult['info'] = "审核失败";
				return $returnResult;
			}
		
			//提交
			Yii::app()->dwdb->commit();
		} catch (Exception $e) {
			Yii::app()->dwdb->rollback();
			$returnResult['info'] = "Exception error,msg:".print_r($e->getMessage(),true);
			Yii::log($returnResult['info'],'error',__FUNCTION__);
			return $returnResult;
		}
		
		
	
		
		$returnResult['code'] = '0';
		$returnResult['info'] = '审核成功';
	
		return $returnResult;
	}

	
	
	/**
	 * 兑换记录列表
	 */
	public function getOrderList($data = array(), $limit = 10, $page = 1)
	{
		Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
		$returnResult = array(
				'code' => '', 'info' => '', 'data' => array('listTotal' => 0, 'listInfo' => array())
		);
		$now_time = time();
		$conditions = ' where 1=1 ';
		$order = ' order by createtime desc';
		
		//条件筛选
		if (count($data) > 0) {
			//ID搜索
			if (isset($data['order_id']) && $data['order_id'] != '') {
				$conditions .= ' and order_id in ('.$data['order_id'].')';
			}
			
			//ID搜索
			if (isset($data['user_id']) && $data['user_id'] != '') {
				$hash_id = $this->getHashIdByUserId($data['user_id']);
				$user_id = $this->getEcUserIdByHash($hash_id);
				$conditions .= ' and user_id = '.intval($user_id);
			}
			
			//hash
			if (isset($data['hashid']) && $data['hashid'] != '') {
				$user_id = $this->getEcUserIdByHash($data['hashid']);
				$conditions .= ' and user_id = '.intval($user_id);
			}
			
			//手机号搜索
			if (isset($data['mobile_phone']) && $data['mobile_phone'] != '') {
				$user_id = $this->getEcUserIdByPhone($data['mobile_phone']);
				$conditions .= ' and user_id = '.intval($user_id);
			}
			
			//状态搜索
			if (isset($data['status']) && $data['status'] != '') {
				$conditions .= ' and status = '.intval($data['status']);
			}
			
			if( (isset($data['begin_createtime']) && $data['begin_createtime']!='') && (isset($data['end_createtime']) && $data['end_createtime']!='') ){
				$conditions .= " and createtime between ".intval($data['begin_createtime'])." and ".intval($data['end_createtime']);
			}
	
			//分页条数设置
			$limit = in_array($data['limit'], array(10, 20, 30, 40, 50)) ? intval($data['limit']) : $limit;
			//请求页数
			$page = (isset($data['page']) && $data['page'] != '') ? intval($data['page']) : $page;
		}
	
		$sql = " select count(*) num from itz_debt_order".$conditions;
		$count = Yii::app()->ecshopdb->createCommand($sql)->queryRow();
		$listTotal = intval($count['num']);
		if ($listTotal == 0) {
			$returnResult['code'] = 0;
			$returnResult['info'] = '暂无数据！';
			return $returnResult;
		}
		$returnResult['data']['listTotal'] = $listTotal;
		 
		$sql = "select * from itz_debt_order".$conditions;
		$sql .= $order;
		$offsets = ($page - 1) * $limit;
		$sql .= " LIMIT $offsets,$limit";
		$list = Yii::app()->ecshopdb->createCommand($sql)->queryAll();
	
		foreach ($list as $key=>$value){
			$listInfo[] = $this->resTransOrder($value);
	
		}
		$returnResult['code'] = 0;
		$returnResult['info'] = '获取列表成功';
		$returnResult['data']['listInfo'] = $listInfo;
		return $returnResult;
	}
	
	
	/**
	 * 字段转化
	 */
	public function resTransOrder($data=array()){
		Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
		//获取用户的hashid
		$sql = " select user_name,mobile_phone,hashid from itz_users where user_id={$data['user_id']}";
		$info = Yii::app()->ecshopdb->createCommand($sql)->queryRow();
		
		$sql2 = " select user_id,realname from dw_user where hash_id='{$info['hashid']}'";
		$itz_user = Yii::app()->dwdb->createCommand($sql2)->queryRow();
		
		//状态
		$status_tips = array(0=>'订单发起',1=>'兑换中',2=>'兑换成功',3=>'兑换失败');
		
		$data['hash_id'] = $info['hashid'];
		$data['user_id'] = $itz_user['user_id'];
		$data['user_name'] = $info['user_name'];
		$data['realname'] = $itz_user['realname'];
		//$data['mobile_phone'] = substr_replace($info['mobile_phone'],'****',3,4);
		$data['mobile_phone'] = $info['mobile_phone'];
		$data['status_tips'] = $status_tips[$data['status']];
		$data['createtime'] = date('Y-m-d H:i:s',$data['createtime']);
		$data['num_tips'] = $data['status'] > 0 ? count(json_decode($data['detail'], true)['detail']) : '--';
		$data['account_tips'] = json_decode($data['detail'], true)['account'];
		return $data;
	}
	
	public function getOrderStat($data=array()){
		Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
		$returnResult = array(
				'code' => '1', 'info' => 'error', 'data' => array("listInfo"=>array())
		);
	
	
		$sql = "select sum(debt_account) account from itz_debt_exchange_log where status in (1,2)";
		$debt = Yii::app()->dwdb->createCommand($sql)->queryRow();
		
		$sql1 = "select count(*) all_num,count(distinct(user_id)) people_num from itz_debt_order where status in (1,2)";
		$order = Yii::app()->ecshopdb->createCommand($sql1)->queryRow();
	
		$info = array(
			'account' => $debt['account'],
			'all_num' => $order['all_num'],
			'people_num' => $order['people_num']
		);
		
		$returnResult['code'] = 0;
		$returnResult['info'] = "success";
		$returnResult['data']['listInfo'] = $info;
		return $returnResult;
	
	}
	
	
	
	
	//列表导出
	public function exportOrder($data = array()){
		Yii::log ( __FUNCTION__.print_r(func_get_args(),true),'info');
		$returnResult = array(
				'code' => '', 'info' => '', 'data' => array()
		);
		//日志审计参数
		$logParams = array(
				'user_id' => Yii::app()->user->id, 'system' => 'admin',
				'action' => 'export', 'resource' => 'ccs', 'parameters' => '', 'status' => 'fail'
		);
		
		
		
		$idArr = $data['id'];
		$idArr = isset($idArr) ? $idArr : '';
		if (empty($idArr)) {
			$returnResult['code'] = 1;
			$returnResult['info'] = '缺少id';
			return $returnResult;
		}
		
		if (is_array($idArr)){
			if (count($idArr) == 1){
				$str = $idArr[0];
			}else{
				$str = implode(",", $idArr);
			}
		}else{
			$str = $idArr;
		}
		$condition['order_id'] = $str;
		
	
		//导出时间
		$parameters['export_time'] = time();
		$logParams['parameters'] = json_encode($parameters);
		ini_set("memory_limit", "-1");
		ini_set('ini_setmax_execution_time', '2000');
	
		$achievementResult = $this->getOrderList($condition,2000);
		$achievementList = $achievementResult['data']['listInfo'];
		
		
		$parameters['num'] = count($achievementList);
		$logParams['parameters'] = json_encode($parameters);
	
		//引入excel类
		Yii::import("itzlib.plugins.phpexcel.*");
		$PHPExcelObj = new PHPExcel();
	
		//设置导出的title
		$PHPExcelObj->getActiveSheet()->setTitle(date("Y-m-d") . '用户债权兑换记录');
		$PHPExcelObj->getActiveSheet()->setCellValue('A1', '序号');
		$PHPExcelObj->getActiveSheet()->setCellValue('B1', '用户ID');
		$PHPExcelObj->getActiveSheet()->setCellValue('C1', 'hash_id');
		$PHPExcelObj->getActiveSheet()->setCellValue('D1', '姓名');
		$PHPExcelObj->getActiveSheet()->setCellValue('E1', '注册手机号');
		$PHPExcelObj->getActiveSheet()->setCellValue('F1', '兑换数量');
		$PHPExcelObj->getActiveSheet()->setCellValue('G1', '认购债权金额');
		$PHPExcelObj->getActiveSheet()->setCellValue('H1', '兑换状态');
		$PHPExcelObj->getActiveSheet()->setCellValue('I1', '申请兑换时间');
	
		//设置列宽
		$PHPExcelObj->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
		$PHPExcelObj->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
		$PHPExcelObj->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
		$PHPExcelObj->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
		$PHPExcelObj->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
		$PHPExcelObj->getActiveSheet()->getColumnDimension('F')->setAutoSize(true);
		$PHPExcelObj->getActiveSheet()->getColumnDimension('G')->setAutoSize(true);
		$PHPExcelObj->getActiveSheet()->getColumnDimension('H')->setAutoSize(true);
		$PHPExcelObj->getActiveSheet()->getColumnDimension('I')->setAutoSize(true);
	
		$i = 2;
		foreach ($achievementList as $key => $outputData) {
	
			//填充数据
			$PHPExcelObj->getActiveSheet()->setCellValueExplicit('A' . $i, $outputData['order_id']);
			$PHPExcelObj->getActiveSheet()->setCellValueExplicit('B' . $i, $outputData['user_id']);
			$PHPExcelObj->getActiveSheet()->setCellValueExplicit('C' . $i, $outputData['hash_id']);
			$PHPExcelObj->getActiveSheet()->setCellValueExplicit('D' . $i, $outputData['user_name']);
			$PHPExcelObj->getActiveSheet()->setCellValueExplicit('E' . $i, $outputData['mobile_phone']);
			$PHPExcelObj->getActiveSheet()->setCellValueExplicit('F' . $i, $outputData['account_tips']);
			$PHPExcelObj->getActiveSheet()->setCellValueExplicit('G' . $i, $outputData['account_tips']);
			$PHPExcelObj->getActiveSheet()->setCellValueExplicit('H' . $i, $outputData['status_tips']);
			$PHPExcelObj->getActiveSheet()->setCellValueExplicit('I' . $i, $outputData['createtime']);
			$i++;
		}
		//审计日志
		$logParams['status'] = 'success';
		AuditLog::getInstance()->method('add', $logParams);
	
		$file_name = "用户债权兑换";
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
	
	/**
	 * 详情
	 */
	public function getOrderInfo($data=array()){
		Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
		$returnResult = array(
				'code' => '1', 'info' => 'error', 'data' => array("listInfo"=>array())
		);
		
		$id = isset($data['id']) ? intval($data['id']) : 0;
		if (empty($id)){
			$returnResult['info'] = "order_id不存在";
			return $returnResult;
		}
	
		$sql = "select * from itz_debt_order where order_id = ".$id;
		$res = Yii::app()->ecshopdb->createCommand($sql)->queryRow();
	
		$sql1 = " select user_name,insert(mobile_phone ,4,4,'***') mobile_phone,hashid from itz_users where user_id={$res['user_id']}";
		$user_res = Yii::app()->ecshopdb->createCommand($sql1)->queryRow();
		
		
		$sql2 = "select * from itz_debt_order where user_id = {$res['user_id']} order by createtime desc";
		$order_res = Yii::app()->ecshopdb->createCommand($sql2)->queryAll();
		
		$sql3 = " select user_id from dw_user where hash_id='{$user_res['hashid']}'";
		$itz_user = Yii::app()->dwdb->createCommand($sql3)->queryRow();
		
		$tmp = [];
		$status_tips = array(0=>'订单发起',1=>'兑换中',2=>'兑换成功',3=>'兑换失败');
		foreach ($order_res as $k=>$v){
			$tmp[$k]['account'] = json_decode($v['detail'], true)['account'];
			$tmp[$k]['createtime'] = date('Y-m-d H:i:s',$v['createtime']);
			$tmp[$k]['status'] =$status_tips[$v['status']];
			$tmp[$k]['order_id'] =$v['order_id'];
		 	if($v['status'] > 0){
				$name = json_decode($v['detail'], true)['detail'];
				foreach($name as $key=>$val){
					$val['account'] .='元';
					$first[$key] = implode(':', $val);
				}
				$seconde = implode('    ', $first);
				//unset($first);
			}else{
				$seconde = '--';
			}
			$tmp[$k]['detail'] = $seconde;
			unset($first);
		}
		$user_res['historyList'] = $tmp;
		$user_res['user_id'] = $itz_user['user_id'];
		
		if (empty($user_res)){
			$returnResult['info'] = "数据不存在";
		}else{
			$returnResult['code'] = 0;
			$returnResult['info'] = "success";
			$returnResult['data']['listInfo']= $user_res;
		}
		
		return $returnResult;
	
	}
	
	/**
	 * 获取用户ID
	 */
	public function getUserIdByName($name){
	
		$UserModel = new User();
		$criteria = new CDbCriteria;
		$attributes = array(
				"realname"    =>   $name,
		);
		$UserResult =$UserModel->findByAttributes($attributes,$criteria);
		return $UserResult['user_id'];
	}
	
	//获取多个用户id
	public function getAllUserIdByName($name){
		$sql = "select user_id from dw_user where realname = '{$name}'";
		$info= Yii::app()->dwdb->createCommand($sql)->queryAll();
		foreach ($info as $v){
			$tmp[] = $v['user_id'];
		}
		$user_ids = implode(',',$tmp);
		return $user_ids;
	}
	
	
	/**
	 * 获取用户ID
	 */
	public function getAllUserIdByPhone($phone){
	
		$sql = "select user_id from dw_user where phone = '{$phone}'";
		$info= Yii::app()->dwdb->createCommand($sql)->queryAll();
		foreach ($info as $v){
			$tmp[] = $v['user_id'];
		}
		$user_ids = implode(',',$tmp);
		return $user_ids;
	}
	
	/**
	 * 获取用户ID
	 */
	public function getUserIdByPhone($phone){
	
		$UserModel = new User();
		$criteria = new CDbCriteria;
		$attributes = array(
				"phone"    =>   $phone,
		);
		$UserResult =$UserModel->findByAttributes($attributes,$criteria);
		return $UserResult['user_id'];
	}
	
	/**
	 * 获取用户hashID
	 */
	public function getHashIdByUserId($user_id){
	
		$UserModel = new User();
		$criteria = new CDbCriteria;
		$attributes = array(
				"user_id"    =>   $user_id,
		);
		$UserResult =$UserModel->findByAttributes($attributes,$criteria);
		return $UserResult['hash_id'];
	}
	
	/**
	 * 获取ECSHOP用户ID
	 */
	public function getEcUserIdByPhone($mobile_phone){
		$sql = "select user_id from itz_users where mobile_phone='{$mobile_phone}'";
		$info= Yii::app()->ecshopdb->createCommand($sql)->queryRow();
		return $info['user_id'];
	}
	
	/**
	 * 获取ECSHOP用户ID
	 */
	public function getEcUserIdByHash($hash){
		$sql = "select user_id from itz_users where hashid='{$hash}'";
		$info= Yii::app()->ecshopdb->createCommand($sql)->queryRow();
		return $info['user_id'];
	}
	

}