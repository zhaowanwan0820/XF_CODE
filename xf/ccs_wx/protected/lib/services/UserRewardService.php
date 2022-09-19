<?php
/**
 * 用户获取奖励
 **/
class UserRewardService extends ItzInstanceService {
	
	public function __construct()
    {
    	parent::__construct();
    } 
    
    
    /**
     * 获取列表
     * @param array $data
     */
	public function getRewardList($data = array(), $limit = 10, $page = 1){
		
		Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
		$returnResult = array(
				'code' => '', 'info' => '', 'data' => array('listTotal' => 0, 'listInfo' => array())
		);
		
		//默认展示三个月内数据
		$time_start = time()-180*86400;
		$conditions = ' borrow_tender_time > '.$time_start; 
		$order = ' order by borrow_tender_time desc';
		//条件筛选
		if (count($data) > 0) {

			//依据项目名称搜索
			if (isset($data['name']) && $data['name'] != '') {
				$conditions .= ' and b.name like  ' . '"%' . htmlspecialchars(addslashes(trim($data['name']))) . '%"';
			}
			
			//依据项目ID
			if (isset($data['borrow_id']) && $data['borrow_id'] != '') {
				$conditions .= ' and r.borrow_id = '.intval($data['borrow_id']);
			}
			
			//依据用户ID
			if (isset($data['user_id']) && $data['user_id'] != '') {
				$conditions .= ' and r.user_id = '.intval($data['user_id']);
			}
			
			//分页条数设置
			$limit = in_array($data['limit'], array(10, 20, 30, 40, 50)) ? intval($data['limit']) : $limit;
			//请求页数
			$page = (isset($data['page']) && $data['page'] != '') ? intval($data['page']) : $page;
		}
		
		$sql = " select count(*) num from itz_reward_user r left join dw_borrow b on r.borrow_id = b.id where " . $conditions;
		$count = Yii::app()->dwdb->createCommand($sql)->queryRow();
		$listTotal = intval($count['num']);
		if ($listTotal == 0) {
			$returnResult['code'] = 0;
			$returnResult['info'] = '暂无数据！';
			return $returnResult;
		}
		$returnResult['data']['listTotal'] = $listTotal;
		
		$sql = "select r.is_pay,r.re_id,r.borrow_id,r.user_id,r.borrow_tender_money,r.borrow_tender_time,b.name FROM itz_reward_user r 
				left join dw_borrow b on r.borrow_id = b.id where". $conditions;
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
	 * 结果转化
	 */
	public function listResTrans($data=array()){
		Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
		
		//获取奖励类型
		$sql = "select re_id,re_cname from itz_reward";
		$srcinfo = Yii::app()->dwdb->createCommand($sql)->queryAll();
		foreach ($srcinfo as $key=>$val){
			$src_k[]=$val['re_id'];
			$src_v[]=$val['re_cname'];
		}
		$src_tips = array_combine($src_k, $src_v);
		$userInfo = BehaviorSearchService::getInstance()->getUserInfoById($data);
		 
		$data['real_name_tips'] = $userInfo['realname'] ? substr_replace($userInfo['realname'],'**',3) : '--' ;
		$data['sex_tips'] = $userInfo['sex'];
		$data['re_cname_tips'] = $src_tips[$data['re_id']];
		$data['borrow_tender_time'] = date('Y-m-d H:i:s',$data['borrow_tender_time']);
		return $data;
	}

    
 
}


