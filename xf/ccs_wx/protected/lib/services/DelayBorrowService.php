<?php

class DelayBorrowService extends ItzInstanceService
{

    public function __construct()
    {
        parent::__construct();
    }
    
	/**
	 * 列表
	 */
	public function getBorrowList($data = array(), $limit = 10, $page = 1)
	{
	
		Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
		$returnResult = array(
			'code' => '', 'info' => '', 'data' => array('listTotal' => 0, 'listInfo' => array())
		);
		$now_time = time();
		$conditions = ' type in (202,402) ';
		$order = ' order by id desc';
		//条件筛选
		if (count($data) > 0) {
			
			//状态搜索
			if (isset($data['status']) && $data['status'] != '') {
				$conditions .= ' and status = '.intval($data['status']);
			}
			
			//项目名称搜索
			if (isset($data['borrow_name']) && $data['borrow_name'] != '') {
				$serach_id = $this->getBorrowByName($data['borrow_name']);
				$conditions .= ' and borrow_id = '.intval($serach_id);
			}
			
			//内容搜索
			if (isset($data['content']) && $data['content'] != '') {
				$conditions .= ' and content like  ' . '"%' . htmlspecialchars(addslashes(trim($data['content']))) . '%"';
			}
			
			//分页条数设置
			$limit = in_array($data['limit'], array(10, 20, 30, 40, 50)) ? intval($data['limit']) : $limit;
			//请求页数
			$page = (isset($data['page']) && $data['page'] != '') ? intval($data['page']) : $page;
		}

		$sql = " select count(*) num from itz_renew_borrow WHERE " . $conditions;
		$count = Yii::app()->dwdb->createCommand($sql)->queryRow();
		$listTotal = intval($count['num']);
		if ($listTotal == 0) {
			$returnResult['code'] = 0;
			$returnResult['info'] = '暂无数据！';
			return $returnResult;
		}
		$returnResult['data']['listTotal'] = $listTotal;

		$sql = " select * from itz_renew_borrow WHERE $conditions";
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
	 * 添加项目数据
	 */
	public function addBorrow($data = array()){
		$returnResult=['data'=>[],'code'=>100,'info'=>'error',];
		
		//数据读取
		$fileType = ["application/vnd.ms-excel","application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"];
		
		if (!in_array($_FILES["file"]["type"],$fileType)){
			$returnResult['info'] = '导入失败 , 表格格式有误！只支持导入xls、xlsx格式文件!';
			Yii::log($returnResult['info'],'error',__FUNCTION__);
			return false;
		}
		if ($_FILES["file"]["error"] > 0){
			$returnResult['info'] = $_FILES["phone_list"]["error"];
			Yii::log($returnResult['info'],'error',__FUNCTION__);
			return false;
		}
		$importInfo = $this->getDataFromExcel();
		
		
		//判断展期条件
		if(empty($importInfo)){
			$returnResult['info'] = 'excel文件读取失败';
			Yii::log($returnResult['info'],'error',__FUNCTION__);
			return $returnDate;
		}
		if(count($importInfo)>3000){
		$returnResult['info'] = '单次上传上限为3000条记录';
				Yii::log($returnResult['info'],'error',__FUNCTION__);
				return $returnResult;
		}
		
		foreach ($importInfo as $k=>$val) {
			if(count($val) != 7){
				$returnResult['info'] = '导入数据格式错误，请参考模板';
				Yii::log($returnResult['info'],'error',__FUNCTION__);
				return $returnResult;
			}	
			if($val[3] == 5 && $val[5]<1 ){
				$returnResult['info'] = '不支持此类型，请扩增';
				Yii::log($returnResult['info'],'error',__FUNCTION__);
				return $returnResult;
			}
			$checkResult = $this->checkBorrowList($val);
			if ($checkResult['code']){
				return $checkResult;
			}else{
				$importInfo[$k][7]=$checkResult['data']['borrow_id'];
			}
		}
		
		
		
		$now = time();
		//处理数据
		Yii::app()->dwdb->beginTransaction();
		try {
			foreach ($importInfo as $val) {
				$borrow_ids = $val['borrow_id'];
				$add['type'] = 202; //省心计划
				$add['borrow_id'] = $add['wise_borrow_id'] = $val[7] ;
				$add['style'] = $val[3]==55999 ? '0' : $val[3];
				$add['apr'] = $val[1];
				$add['value_date'] =  $val[5]==55999 ? '0' : strtotime($val[5]) ;	//起息时间
				$add['repay_time'] = $val[6]==55999 ? '0' : strtotime($val[6]) ;	//还款时间
				$add['status'] = 3; //客服后台添加的数据默认为调研中
				$add['cycle'] = $val[2];
				$add['content'] = $val[4];
				$add['addtime'] = $add['start_time'] = strtotime(date('Y-m-d',$now));
				$addRes = $this->addRenewSystem($add);
				
				if (!$addRes){
					Yii::log(__FUNCTION__ . " add renew_borrow fail borrow_name=".$val[0], CLogger::LEVEL_ERROR);
					Yii::app()->dwdb->rollback();
					continue;
				}
				
				//获取已授权用户
				$sql = "select t.id ,t.user_id ,t.wait_interest,t.wait_account  from dw_borrow_tender as t left join itz_xw_auth as a on t.user_id = a.user_id where t.borrow_id = {$val[7]} and t.renew_id = 0 and t.deal_status = 0 and t.status = 1 and  a.auth_code='RENEW' and a.status = 1 and a.fail_time> {$now}";
				$res = Yii::app()->dwdb->createCommand($sql)->queryAll();
				if($res){
					//对授权用户发送站内信
					$total = 0;
					$tender_ids = [];
					foreach ($res as $re) {
						$remind=[];
						$remind['sent_user'] = 0;
						$remind['data']['hbfx_xmmc'] = $val[0];
						$remind['data']['hbfx_htwh'] =  $re['id'];
						$remind['receive_user'] = $re['user_id'];
						$remind['mtype'] = 'sx_zdtyzq';
						$result = NewRemindService::getInstance()->SendToUser($remind,true,false,false);
						if(!$result){
							Yii::log("DelayBorrow one  send to user message error ".print_r($re,TRUE), 'error',  __FUNCTION__);
							Yii::app()->dwdb->rollback();
							continue;
						}
						$tender_ids[]= $re['id'];
						$total = bcadd($total,bcsub($re['wait_account'],$re['wait_interest'],2));
					}
						
					//更改授权用户的renew_id和状态
					$tender_ids = implode(',',$tender_ids);
					$sql3 = "update dw_borrow_tender set renew_id = {$addRes} ,deal_status = 3 where id in ($tender_ids) ";
					$res3 = Yii::app()->dwdb->createCommand($sql3)->execute();
					if(!$res3){
						
						Yii::log('update dw_borrow_tender renew_id failed borrow_id in '.$tender_ids,'error',__FUNCTION__);
						Yii::app()->dwdb->rollback();
						continue;
					}
						
					//授权用户本金添加到itz_renew_borrow表展期本金
					$sql4 = "update itz_renew_borrow set renew_capital = renew_capital + {$total} where id = {$addRes}  ";
					$res4 = Yii::app()->dwdb->createCommand($sql4)->execute();
					if(!$res4){
						Yii::log('update itz_renew_borrow renew_capital failed id = '.$val[7].' and  total = '.$total,'error',__FUNCTION__);
						Yii::app()->dwdb->rollback();
						continue;
					}
				}
				//获取未授权用户
				$sql2 = "select id,user_id from dw_borrow_tender where borrow_id={$val[7]} and renew_id = 0 and deal_status = 0 and status = 1";
				$res2 = Yii::app()->dwdb->createCommand($sql2)->queryAll();
				if($res2){
					//对未授权用户发送站内信
					$wait_tender_ids = [];
					foreach ($res2 as $re2) {
						$remind=[];
						$remind['sent_user'] = 0;
						$remind['data']['hbfx_xmmc'] = $val[0];
						$remind['data']['hbfx_htwh'] =  $re2['id'];
						$remind['receive_user'] = $re2['user_id'];
						$remind['mtype'] = 'sx_xzzq';
						$result = NewRemindService::getInstance()->SendToUser($remind,true,false,false);
						if(!$result){
							Yii::log("DelayBorrow two  send to user message error ".print_r($re,TRUE), 'error',  __FUNCTION__);
							Yii::app()->dwdb->rollback();
							continue;
						}
						$wait_tender_ids[]= $re2['id'];
							
					}
					
					//修改未授权用户的数据
					$wait_tender_ids = implode(',',$wait_tender_ids);
					$sql5 = "update dw_borrow_tender set deal_status = 9 where id in ($wait_tender_ids) ";
					$res5 = Yii::app()->dwdb->createCommand($sql5)->execute();
					if(!$res5){
						Yii::log('update dw_borrow_tender renew_id failed borrow_id in '.$tender_ids,'error',__FUNCTION__);
						Yii::app()->dwdb->rollback();
						continue;
					}
				}
				
			}
			
			//提交
			Yii::app()->dwdb->commit();
			
		} catch (Exception $e) {
			Yii::app()->dwdb->rollback();
			$returnResult['info'] = "Exception error,msg:".print_r($e->getMessage(),true);
			Yii::log($returnResult['info'],'error',__FUNCTION__);
			return $returnResult;
		}
		
		
		$returnResult['code'] = 0;
		$returnResult['info'] = 'success';
		return $returnResult;
	}
	
	/**
	 * 查看详情
	 * @param array $data
	 * @return array
	 */
	public function getBorrowInfo($data=array()){
	
		Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
		$returnResult = array(
				'code' => '1', 'info' => 'error', 'data' => array("listInfo"=>array())
		);
		$renew_id = isset($data['id']) ? intval($data['id']) : 0;
		if (empty($renew_id)){
			$returnResult['info'] = "renew_id不存在";
			return $returnResult;
		}
		$sql = "select * from itz_renew_borrow where id = ".$renew_id;
		$info = Yii::app()->dwdb->createCommand($sql)->queryRow();
		if (empty($info)){
			$returnResult['info'] = "数据不存在";
		}else{
			$returnResult['code'] = 0;
			$returnResult['info'] = "success";
			$returnResult['data']['listInfo'] = $info;
		}
		return $returnResult;
	}
	
	/**
	 * 编辑内容
	 * @param array $data
	 * @return array
	 */
	public function editBorrow($data=array(),$type=1){
		Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
		$returnResult = array(
				'code' => '1', 'info' => 'error', 'data' => array()
		);
	
		$renew_id = isset($data['id']) ? intval($data['id']) : 0;
		if (empty($renew_id)){
			$returnResult['info'] = "参数错误";
			return $returnResult;
		}
		
		if(mb_strlen($data['content']) >=3000){
			$returnResult['info'] = '内容限3000字以内';
			Yii::log($returnResult['info'],'error',__FUNCTION__);
			return $returnResult;
		}
		if($type ==1){
			$renewInfo['content'] = $data['content'];
		}
		if($type ==2){
			$renewInfo['status'] = 5;
		}
		
		$updateRes = Yii::app()->dwdb->createCommand()->update('itz_renew_borrow', $renewInfo, 'id=:id', array(':id' => $renew_id));
		if (!$updateRes){
			Yii::log(__FUNCTION__ . " update itz_renew_borrow fail id=$renew_id", CLogger::LEVEL_ERROR);
			$returnResult['info'] = "编辑失败";
			return $returnResult;
		}
		$returnResult['code'] = 0;
		$returnResult['info'] = "success";
		return $returnResult;
	}
	
	/**
	 * 入库数据校验
	 */
	public function checkBorrowList($data=array()){
		Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
		$returnResult = array(
				'code' => '1', 'info' => 'error', 'data' => array()
		);
		
		$info['name'] = trim($data[0]);
		$info['apr'] = abs($data[1]);
		$info['cycle'] = abs($data[2]);
		$info['style'] = $data[3]==55999 ? '0' : abs($data[3]);//特殊处理
		$info['content'] = $data[4];
		$info['value_date'] = $data[5] == 55999 ? '0' : strtotime($data[5]);
		$info['repay_time'] = $data[6] == 55999 ? '0' : strtotime($data[6]);
		$borrow_res = Borrow::model()->findBySql("select id,repayment_time,type from dw_borrow where renew_status in (2,4) and name=:name", array(':name'=>$info['name']));
		if(empty($borrow_res)){
			$returnResult['info'] = '请联系相关开发,导入数据中有非延期状态的项目:'.$info['name'];
			Yii::log($returnResult['info'],'error',__FUNCTION__);
			return $returnResult;
		}
		if(empty($data[5]) || empty($data[6])  || empty($info['repay_time']) || empty($info['value_date']) ){
			$returnResult['info'] = '时间字段格式错误，请使用文本格式';
			Yii::log($returnResult['info'],'error',__FUNCTION__);
			return $returnResult;
		}
		if(mb_strlen($info['content']) >=3000){
			$returnResult['info'] = '内容限3000字以内';
			Yii::log($returnResult['info'],'error',__FUNCTION__);
			return $returnResult;
		}
		
		$count_sql = "select count(*) num from itz_renew_borrow where status=3 and  borrow_id ={$borrow_res['id']}";
		$count = Yii::app()->dwdb->createCommand($count_sql)->queryRow();
		if($count['num']){
			Yii::log('未处理的项目:'.$info['name']."已经存在 ",'error',__FUNCTION__);
			$returnResult['info'] = "未处理的项目{$info['name']}已经存在";
			return $returnResult;
		}
			
		
		$info['borrow_id'] = $borrow_res['id'];
		$returnResult['code'] = 0;
		$returnResult['info'] = "success";
		$returnResult['data'] = $info;
		return $returnResult;
	}
	
	/**
	 * 获取excel数据
	 */
	private function getDataFromExcel(){
		Yii::import("itzlib.plugins.phpexcel.*");
		Yii::import("itzlib.plugins.upyun.ItzUpload");
		$allData = [];
		$filePath = $_FILES["file"]["tmp_name"];
		/**默认用excel2007读取excel，若格式不对，则用之前的版本进行读取*/
		$PHPReader = new PHPExcel();
		$PHPReader = new PHPExcel_Reader_Excel2007();
		if (!$PHPReader->canRead($filePath)) {
			$PHPReader = new PHPExcel_Reader_Excel5();
			if (!$PHPReader->canRead($filePath)) {
				Yii::log('read excel fail','error');
				return $allData;
			}
		}
		$PHPExcel = $PHPReader->load($filePath);
		$currentSheet = $PHPExcel->getSheet(0);
		$allColumn = $currentSheet->getHighestColumn();
		$allRow = $currentSheet->getHighestRow();           //行数
		for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
			$data = array();
			for ($currentColumn = 'A'; $currentColumn <= $allColumn; $currentColumn++) {
				// ord()将字符转为十进制数
				$val = $currentSheet->getCellByColumnAndRow(ord($currentColumn) - 65, $currentRow)->getValue();
				if($val=='0') $val='55999'; //特殊处理下
				if(!empty($val)){
					$data[] = addslashes(trim($val));
				}
				 
			}
			if(!empty($data)){
				$allData[] = $data;
			}
		}
		$ItzUpload = new ItzUpload(); //默认空间为itzstatic
		$par = "/[\x80-\xff]/";
		$name_info=str_replace(" ","",preg_replace($par,"",$_FILES['file']['name']));
		$file_name = "reward_".time().$name_info;
		$save_file_path_and_name =  "/data/upfiles/reward/".$file_name;   //保存在upyun上的文件路径
		$res = $ItzUpload->ItzWriteFileEasy($filePath,$save_file_path_and_name);
		
		return $allData;
	}
	
	
	/**
	 * 结果转化
	 */
	public function listResTrans($data=array()){
		Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
	
		$now_time = time();
		$style_tips = array(0=>'按月付息 到期还本',1=>'按日计息 到期还本息',2=>'月底付息 到期还本息',3=>'季度付息 到期还本',4=>'等额本金 按月付款',5=>'等额本息 按月付款');
		$status_tips = array(0=>'待处理',1=>'处理中',2=>'处理完成',3=>'调研中',4=>'已还本',5=>'已作废');

		$data['style_tips'] = $style_tips[$data['style']];
		$data['status_tips'] = $status_tips[$data['status']];
		$data['borrow_name'] = $this->getInvestInfo($data['borrow_id']);
		$data['start_time_tips'] = $data['start_time'] > 0 ? date('Y-m-d H:i:s',$data['start_time']) : "--";
		$data['value_date_tips'] = $data['value_date'] > 0 ? date('Y-m-d H:i:s',$data['value_date']) : "--";
		$data['repay_time_tips'] = $data['repay_time'] > 0 ? date('Y-m-d H:i:s',$data['repay_time']) : "--";
		return $data;
	}
	
	

	
	
	/**
	 * 批量
	 */
	public function editAllBorrowList($data = array())
	{
		Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
		$returnResult = array(
				'code' => '1', 'info' => '', 'data' => array()
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
		$condition = " r.id in (".$str.")";
		
		Yii::app()->dwdb->beginTransaction();		//开启事务
		try{
			
			$sql = "select r.id,r.status,r.style,r.borrow_id,r.apr,r.cycle,b.name,b.repayment_time,r.value_date,r.repay_time from itz_renew_borrow r 
					left join dw_borrow b on r.borrow_id=b.id where". $condition;
			$res = Yii::app()->dwdb->createCommand($sql)->queryAll();
			
			foreach ($res as $k=>$v){
				if( $v['status'] != 3){
					$returnResult['code'] = 1;
					$returnResult['info'] = "请检查所选项目ID:{$v['borrow_id']}状态是否为调研中";
					return $returnResult;
				}
				//依据borrow_id查询用户在投信息等
				$sql2 = "select tender_id ,user_id,borrow_id,sum(capital) capital from dw_borrow_collection
						where borrow_id = {$v['borrow_id']}  and status in(0,16) and type=1 and
						tender_id in(select id from dw_borrow_tender where borrow_id = {$v['borrow_id']}  and renew_id !=0 and deal_status=3 and status=1)
						group by tender_id  order by user_id desc";
				$res2 = Yii::app()->dwdb->createCommand($sql2)->queryAll();
				//对选择了展期用户发送站内信
				if($res2){
					$tender_ids = [];
					foreach ($res2 as $re2) {
						$remind=[];
						$remind['sent_user'] = 0;
						$remind['data']['hbfx_xmmc'] = $v['name'];
						$remind['data']['hbfx_htwh'] = $re2['tender_id'];
						$remind['data']['xm_xmhb'] = $re2['capital'];
						$remind['data']['xm_nh'] =  $v['apr'];
						$remind['data']['xm_qx'] = $v['cycle']/30;
						$remind['receive_user'] = $re2['user_id'];
						$remind['mtype'] = 'sx_zqts';
						$result = NewRemindService::getInstance()->SendToUser($remind,true,false,false);
						if(!$result){
							Yii::log("DelayBorrow sx_zqts  send to user message error ".print_r($re2,TRUE), 'error',  __FUNCTION__);
							Yii::app()->dwdb->rollback();
							$returnResult['info'] = '站内信发送失败';
						}
						$tender_ids[]= $re2['tender_id'];
					}
					
					//授权用户展期状态3更改为1
					$tender_ids = implode(',',$tender_ids);
					$sql3 = "update dw_borrow_tender set deal_status = 1 where id in ($tender_ids) ";
					$res3 = Yii::app()->dwdb->createCommand($sql3)->execute();
					if(!$res3){
						Yii::app()->dwdb->rollback();
						Yii::log(__FUNCTION__ . " update dw_borrow_tender deal_status failed id in".$tender_ids , CLogger::LEVEL_ERROR);
						$returnResult['info'] = '批量操作失败，更新出错';
					}
				}
				
				
				
				
				//查询状态为9的用户 即什么都没选的用户
				$sql4 = "select * from dw_borrow_tender where deal_status=9 and status=1 and borrow_id={$v['borrow_id']}";
				$res4 = Yii::app()->dwdb->createCommand($sql4)->queryAll();
				//发送站内信  计算处置金额
				if($res4){
					$tender_displode_ids = [];
					foreach ($res4 as $re4) {
						$remind=[];
						$remind['sent_user'] = 0;
						$remind['data']['hbfx_xmmc'] = $v['name'];
						$remind['data']['hbfx_htwh'] = $re4['id'];
						$remind['receive_user'] = $re4['user_id'];
						$remind['mtype'] = 'sx_tszq';
						$result = NewRemindService::getInstance()->SendToUser($remind,true,false,false);
						if(!$result){
							Yii::log("DelayBorrow sx_tszq  send to user message error ".print_r($re4,TRUE), 'error',  __FUNCTION__);
							Yii::app()->dwdb->rollback();
							$returnResult['info'] = '站内信发送失败';
						}
						$tender_displode_ids[]= $re4['id'];
						$total = bcadd($total,bcsub($re4['wait_account'],$re4['wait_interest'],2));
					}
					
					
					//新增一条处置数据
					$dispose['type'] = 202;
					$dispose['dispose_type'] = 1;//展期处置
					$dispose['borrow_id'] = $v['borrow_id'];
					$dispose['style'] = $v['style'];
					$dispose['apr'] = $v['apr'];
					$dispose['dispose_capital'] = $total;
					$dispose['status'] = 2;
					$dispose['addtime'] = $dispose['process_time'] = time();
					$dispose['cycle'] = $v['cycle'];
					$dispose['content'] = '';
					$res5 = $this->addDispose($dispose);
					if(!$res5){
						Yii::log(__FUNCTION__ . " insert itz_dispose failed" , CLogger::LEVEL_ERROR);
						Yii::app()->dwdb->rollback();
						$returnResult['info'] = '批量操作失败，新增处置失败';
					}
					
					//更新用户的状态为2处置中 关联新增的处置id
					$tender_displode_ids = implode(',',$tender_displode_ids);
					$sql6 = "update dw_borrow_tender set deal_status = 2,renew_id={$res5} where id in ($tender_displode_ids) ";
					$res6 = Yii::app()->dwdb->createCommand($sql6)->execute();
					if(!$res6){
						Yii::app()->dwdb->rollback();
						Yii::log(__FUNCTION__ . " update dw_borrow_tender deal_status,renew_id={$res5} failed", CLogger::LEVEL_ERROR);
						$returnResult['info'] = '批量操作失败，更新出错';
					}
					
				}
				
				
					
				
				
				$changeInfo['status'] = 2;
				$changeInfo['renew_time'] = time();
				if($v['value_date'] == 0){
					$changeInfo['value_date'] = $v['repayment_time'];
				}
				if($v['repay_time']== 0) {
					$changeInfo['repay_time'] =  $v['repayment_time']+86400*$v['cycle'];
				}
				$changeInfo['borrower_value_date']  = $v['repayment_time'];
				$updateRes = Yii::app()->dwdb->createCommand()->update('itz_renew_borrow', $changeInfo, 'id=:id', array(':id' => $v['id']));
				if ($updateRes) {
					Yii::log(__FUNCTION__ . " update itz_renew_borrow success id = ".$str." ,updatetime=" . time(), CLogger::LEVEL_INFO);
					$returnResult['code'] = 0;
					$returnResult['info'] = "success";
				} else {
					Yii::app()->dwdb->rollback();
					Yii::log(__FUNCTION__ . " update itz_renew_borrow fail id =".$str." ,updatetime=" . time(), CLogger::LEVEL_ERROR);
					$returnResult['info'] = '批量操作失败，更新出错';
				}
				unset($changeInfo);
				
				
				
			}
			//提交
			Yii::app()->dwdb->commit();
		}catch (Exception $e){
			Yii::app()->dwdb->rollback();
			$returnResult['info'] = '批量操作失败';
			Yii::log( __FUNCTION__.' edit error '.print_r($e->getMessage(),true) );
		}
		return $returnResult;
		
		
	}
	
	
	/**
	 * 添加renew_borrow
	 * @param $data
	 * @return bool
	 */
	public function addRenewSystem($data)
	{
		Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
		$Renew_model = new ItzRenewBorrow();
		foreach ($data as $key => $value) {
			$Renew_model->$key = $value;
		}
	
		if ($Renew_model->save() == false) {
			Yii::log("ItzRenewBorrow_model error: " . print_r($Renew_model->getErrors(), true), "error");
			return false;
		} else {
			return $Renew_model->attributes['id'];
		}
	}
	
	
	/**
	 * 获取项目信息
	 */
	public function getInvestInfo($borrow_id){
		$BorrowModel = new Borrow();
		$criteria = new CDbCriteria;
		$attributes = array(
				"id"    =>   $borrow_id
		);
		$BorrowResult =$BorrowModel->findByAttributes($attributes,$criteria);
		return $BorrowResult['name'];
	
	}
	
	/**
	 * 获取项目信息
	 */
	public function getBorrowByName($borrow_name){
		$BorrowModel = new Borrow();
		$criteria = new CDbCriteria;
		$attributes = array(
				"name"    =>   $borrow_name
		);
		$BorrowResult =$BorrowModel->findByAttributes($attributes,$criteria);
		return $BorrowResult['id'];
	
	}
	
	/**
	 * 添加处置
	 */
	public function addDispose($data)
	{
		Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
		$dispose_model = new ItzDisposeBorrow();
		foreach ($data as $key => $value) {
			$dispose_model->$key = $value;
		}
		if ($dispose_model->save() == false) {
			Yii::log("dispose_model error: " . print_r($dispose_model->getErrors(), true), "error");
			return false;
		} else {
			return $dispose_model->attributes['id'];
		}
	}
	

}