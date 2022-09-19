<?php
class UserInfoForCcsService extends ItzInstanceService {
	
	public function __construct()
    {
    	parent::__construct();
    } 
    
    
    /**
     * 获取列表
     * @param array $data
     */
	public function getSpecialCardUserList($data = array(), $limit = 10, $page = 1){
		
		Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
		$returnResult = array(
				'code' => '', 'info' => '', 'data' => array('listTotal' => 0, 'listInfo' => array())
		);
		
		$conditions = " (birthday='' or birthday is null or sex='' or sex is null ) and real_status=1 and card_type in(2,3,4,5,6)"; 
		$order = " ";
		//条件筛选
		if (count($data) > 0) {

			//依据项目名称搜索
			if (isset($data['realname']) && $data['realname'] != '') {
				$conditions .= ' and realname like  ' . '"%' . htmlspecialchars(addslashes(trim($data['realname']))) . '%"';
			}
			
			//依据用户ID
			if (isset($data['user_id']) && $data['user_id'] != '') {
				$conditions .= ' and user_id = '.intval($data['user_id']);
			}
			
			//依据用户type
			if (isset($data['type']) && $data['type'] != '') {
				$info= Yii::app()->ecshopdb->createCommand($data['type'])->queryAll();
				print_r($info);die;
				$conditions .= ' and user_id = '.intval($data['user_id']);
			}
			
			
			//分页条数设置
			$limit = in_array($data['limit'], array(10, 20, 30, 40, 50)) ? intval($data['limit']) : $limit;
			//请求页数
			$page = (isset($data['page']) && $data['page'] != '') ? intval($data['page']) : $page;
		}
		
		$sql = " select count(*) num from dw_user where " . $conditions;
		$count = Yii::app()->dwdb->createCommand($sql)->queryRow();
		$listTotal = intval($count['num']);
		if ($listTotal == 0) {
			$returnResult['code'] = 0;
			$returnResult['info'] = '暂无数据！';
			return $returnResult;
		}
		$returnResult['data']['listTotal'] = $listTotal;
		
		$sql = "select user_id,realname,real_status,card_type,invest_times,sex,birthday,card_pic1,card_pic2 from dw_user where". $conditions;
		$sql .= $order;
		$offsets = ($page - 1) * $limit;
		$sql .= " LIMIT $offsets,$limit";
		$list = Yii::app()->dwdb->createCommand($sql)->queryAll();
		
		foreach ($list as $key=>$value){
			$listInfo[] = $this->listResTrans($value);
		}
		$returnResult['code'] = 0;
		$returnResult['info'] = '获取列表成功';
		$returnResult['data']['listInfo'] = $listInfo;
		return $returnResult;
	}
	
	/**
	 * 查看详情
	 */
	public function getSpecialCardUserInfo($data=array()){
	
		Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
		$returnResult = array(
				'code' => '1', 'info' => 'error', 'data' => array("listInfo"=>array())
		);
		$userID = isset($data['user_id']) ? intval($data['user_id']) : 0;
		if (empty($userID)){
			$returnResult['info'] = "用户id不存在";
			return $returnResult;
		}
		$sql = "select user_id,realname,real_status,card_type,invest_times,sex,birthday,card_pic1,card_pic2 from dw_user where user_id = ".$userID;
		$info = Yii::app()->dwdb->createCommand($sql)->queryRow();
	
		$info = $this->listResTrans($info);
	
		if (empty($info)){
			$returnResult['info'] = "用户信息不存在";
		}else{
			$returnResult['code'] = 0;
			$returnResult['info'] = "success";
			$returnResult['data']['listInfo'] = $info;
		}
		return $returnResult;
	}
	
	/**
	 * 编辑
	 */
	public function editUserInfo($data=array()){
	
		Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
		$returnResult = array(
				'code' => '1', 'info' => 'error', 'data' => array()
		);
	
		$userInfo = array();
		$userID = isset($data['user_id']) ? intval($data['user_id']) : 0;
		if (empty($userID)){
			$returnResult['info'] = "缺少参数";
			return $returnResult;
		}
		
		if(isset($data['sex'])){
			$userInfo['sex'] = intval($data['sex']);
		}
		
		if(isset($data['card_type'])){
			$userInfo['card_type'] = intval($data['card_type']);
		}
		
		if(isset($data['birthday'])){
			$userInfo['birthday'] =  addslashes(trim($data['birthday']));
			//$userInfo['birthday'] =  strtotime(addslashes(trim($data['birthday'])));
		}
		$userInfo['uptime'] = time();
		
		 
		$updateRes = Yii::app()->dwdb->createCommand()->update('dw_user', $userInfo, 'user_id=:user_id', array(':user_id' => $userID));
		if (!$updateRes){
			Yii::log(__FUNCTION__ . " update dw_user fail user_id=".$userID, CLogger::LEVEL_ERROR);
			$returnResult['info'] = "更新失败";
		}
		$returnResult['code'] = '0';
		$returnResult['info'] = '更新成功';
	
		return $returnResult;
	}
	
	
	/**
	 * 结果转化
	 */
	public function listResTrans($data=array()){
		Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');

    	$card_type = array(1=>'身份证',2=>'军官证',3=>'港澳台通行证',4=>'护照',5=>'营业执照',6=>'外国人永久居留证');
    	$user_sex = array(1=>'男',2=>'女',3=>'未知');

    	$data['card_type_tips'] = $data['card_type'] ? $card_type[$data['card_type']] : '--';
    	$data['sex_tips'] = $data['sex'] ? $user_sex[$data['sex']] : '--';
    	$data['birthday_tips'] = $data['birthday'] ? date('Y-m-d',$data['birthday']) : '--';
    	return $data;
	}
	
	

    
 
}


