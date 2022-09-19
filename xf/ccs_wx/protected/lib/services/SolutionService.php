<?php

class SolutionService extends ItzInstanceService
{

    public function __construct()
    {
        parent::__construct();
    }
    
	/**
	 * 列表
	 */
	public function getRenewBorrowList($data = array(), $limit = 10, $page = 1)
	{
	
		Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
		$returnResult = array(
			'code' => '', 'info' => '', 'data' => array('listTotal' => 0, 'listInfo' => array())
		);
		
		$now_time = time();
		$conditions = ' 1 = 1 ';
		$order = ' order by id desc';
		//条件筛选
		if (count($data) > 0) {
			
			//状态搜索
			if (isset($data['status']) && $data['status'] != '') {
				$conditions .= ' and b.status = '.intval($data['status']);
			}
			
			//项目名称搜索
			if (isset($data['borrow_name']) && $data['borrow_name'] != '') {
				$conditions .= ' and s.pre_borrow_list like  ' . '"%' . htmlspecialchars(addslashes(trim($data['borrow_name']))) . '%"';
			}
			
			//内容搜索
			if (isset($data['company_name']) && $data['company_name'] != '') {
				$conditions .= ' and s.company_name like  ' . '"%' . htmlspecialchars(addslashes(trim($data['company_name']))) . '%"';
			}
			
			//分页条数设置
			$limit = in_array($data['limit'], array(10, 20, 30, 40, 50)) ? intval($data['limit']) : $limit;
			//请求页数
			$page = (isset($data['page']) && $data['page'] != '') ? intval($data['page']) : $page;
		}

		$sql = " select count(distinct(s.id)) num from itz_renew_borrow b left join itz_deferred_solution s on s.id=b.s_id WHERE s.type = 1 and " . $conditions;
		$count = Yii::app()->dwdb->createCommand($sql)->queryRow();
		$listTotal = intval($count['num']);
		if ($listTotal == 0) {
			$returnResult['code'] = 0;
			$returnResult['info'] = '暂无数据！';
			return $returnResult;
		}
		$returnResult['data']['listTotal'] = $listTotal;

		$sql = " select b.id renew_id,b.apr,b.style,b.cycle,b.status,s.status solu_status,s.id id,s.company_name from itz_deferred_solution s 
				left join itz_renew_borrow b on s.id=b.s_id WHERE s.type = 1 and " .$conditions. " group by s.id";
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
		$ExcelData  = $this->getDataFromExcel();
		$importInfo = $ExcelData['excel_data'];
		$excel_url = $ExcelData['excel_url'];
		
		//判断展期条件
		if(empty($importInfo)){
			$returnResult['info'] = 'excel文件读取失败';
			Yii::log($returnResult['info'],'error',__FUNCTION__);
			return $returnResult;
		}
		if(count($importInfo)>3000){
		$returnResult['info'] = '单次上传上限为3000条记录';
				Yii::log($returnResult['info'],'error',__FUNCTION__);
				return $returnResult;
		}
		
		foreach ($importInfo as $k=>$val) {
			if(count($val) != 8){
				$returnResult['info'] = '导入数据格式错误，请参考模板';
				Yii::log($returnResult['info'],'error',__FUNCTION__);
				return $returnResult;
			}	
			if($val[4] == 5 && $val[6]<1 ){
				$returnResult['info'] = '不支持此类型，请扩增';
				Yii::log($returnResult['info'],'error',__FUNCTION__);
				return $returnResult;
			}
			
			if($importInfo[0][0] != $val[0]){
				$returnResult['info'] = '请核对EXCEL第一列的借款企业是否一致:'.$val[0];
				Yii::log($returnResult['info'],'error',__FUNCTION__);
				return $returnResult;
			}
			
			if($importInfo[0][2] != $val[2] || $importInfo[0][3] != $val[3] || $importInfo[0][4] != $val[4]){
				$returnResult['info'] = '请核对excel第'.($k+2).'行的[利率],[展期期限],[还款类型]是否与其他行一致';
				Yii::log($returnResult['info'],'error',__FUNCTION__);
				return $returnResult;
			}
			
			$tmp[] = trim($val[1]);
			$pre_borrow_list = implode('、',$tmp);
			$checkResult = $this->checkBorrowList($val);
			if ($checkResult['code']){
				return $checkResult;
			}else{
				$importInfo[$k][8]=$checkResult['data']['borrow_id'];
			}
		}
		
		$now = time();
		//处理数据
		Yii::app()->dwdb->beginTransaction();
		try {
			$solution = array(
					'company_name' => $checkResult['data']['company_name'],
					'type' => 1,
					'num' => $checkResult['data']['num'], //修改二次
					'excel_url' => $excel_url,
					'pre_borrow_list' => $pre_borrow_list,
					'addtime' =>$now
			);
			
			$res1 = $this->addSolutionSystem($solution);
			if (!$res1){
				Yii::log(__FUNCTION__ . " add itz_deferred_solution fail company_name=".$val[0], CLogger::LEVEL_ERROR);
				Yii::app()->dwdb->rollback();
				continue;
			}
			
			//信息披露表入库信息
			$disclosure = array(
					's_id' => $res1,
					'content' => $checkResult['data']['content'],
					'type' => 1,
					'addtime' => $now
			);
			
			$res2 = $this->addDisclosureSystem($disclosure);
			if (!$res2){
				Yii::log(__FUNCTION__ . " add itz_deferred_disclosure fail s_id=".$res1, CLogger::LEVEL_ERROR);
				Yii::app()->dwdb->rollback();
				continue;
			}
			
			foreach ($importInfo as $val) {
				
				//展期表入库信息
				$borrow_ids = $val['borrow_id'];
				$renew = array(
					's_id' => $res1,
					'borrow_id' => $val[8],
					'wise_borrow_id' => $val[8],
					'type' => $this->getInvestInfo($val[8])['type'],
					'style' => $val[4]==55999 ? '0' : $val[4],
					'apr' => $val[2],
					'cycle' => $val[3],
					'status' => 6,
					'value_date' => $val[6]==55999 ? '0' : strtotime($val[6]),
					'repay_time' => $val[7]==55999 ? '0' : strtotime($val[7]),
					'addtime' => strtotime(date('Y-m-d',$now)),
					'start_time' => strtotime(date('Y-m-d',$now))
				);
				
				$res3 = $this->addRenewSystem($renew);
				if (!$res3){
					Yii::log(__FUNCTION__ . " add renew_borrow fail borrow_name=".$val[1], CLogger::LEVEL_ERROR);
					Yii::app()->dwdb->rollback();
					continue;
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
	 * 启动
	 */
	public function startRenewSurvey($data=array()){
		Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
		$returnResult = array(
				'code' => '1', 'info' => 'error', 'data' => array()
		);
		
		
		$id = isset($data['id']) ? intval($data['id']) : 0;
		if (empty($id)){
			$returnResult['info'] = "参数错误";
			return $returnResult;
		}
		
		$sql6 = "select * from itz_renew_borrow where s_id={$id}";
		$info = Yii::app()->dwdb->createCommand($sql6)->queryAll();
		
		$now = time();
		$now_day = strtotime(date('Y-m-d',$now));
		ini_set("memory_limit","-1");
		ini_set('max_execution_time','1000');
		Yii::app()->dwdb->beginTransaction();
		try {
			
			//将solution表状态置为启动
			$sql7 = "update itz_deferred_solution set status = 1 where id ={$id} ";
			$res7 = Yii::app()->dwdb->createCommand($sql7)->execute();
			if(!$res7){
				Yii::log('update itz_deferred_solution status = 1 failed id = '.$id,'error',__FUNCTION__);
				Yii::app()->dwdb->rollback();
				$returnResult['info'] = "启动发生错误";
				return $returnResult;
			}
			
			$sql8 = "update itz_deferred_disclosure set addtime = {$now_day} where s_id ={$id} ";
			$res8 = Yii::app()->dwdb->createCommand($sql8)->execute();
			if(!$res8){
				Yii::log('update itz_deferred_disclosure  addtime = '.$now_day.' failed s_id = '.$id,'error',__FUNCTION__);
				Yii::app()->dwdb->rollback();
				$returnResult['info'] = "启动发生错误";
				return $returnResult;
			}
			
			foreach ($info as $v){
				//获取已授权用户
				$sql = "select t.id ,t.user_id ,t.wait_interest,t.wait_account  from dw_borrow_tender as t 
						left join itz_xw_auth as a on t.user_id = a.user_id 
						where t.borrow_id = {$v['borrow_id']} and t.renew_id = 0 and t.deal_status = 0 and t.status = 1 and  a.auth_code='RENEW' and a.status = 1 and a.fail_time> {$now}";
				$res = Yii::app()->dwdb->createCommand($sql)->queryAll();
				if($res){
					//对授权用户发送站内信
					$total = 0;
					$tender_ids = [];
					foreach ($res as $re) {
						$remind=[];
						$remind['sent_user'] = 0;
						$remind['data']['hbfx_xmmc'] = $this->getInvestInfo($v['borrow_id'])['name'];
						$remind['data']['hbfx_htwh'] =  $re['id'];
						$remind['receive_user'] = $re['user_id'];
						$remind['mtype'] = 'sx_zdtyzq';
						$result = NewRemindService::getInstance()->SendToUser($remind,true,false,false);
						if(!$result){
							Yii::log("solutionService one  send to user message error ".print_r($re,TRUE), 'error',  __FUNCTION__);
							Yii::app()->dwdb->rollback();
							continue;
						}
						$tender_ids[]= $re['id'];
						$total = bcadd($total,bcsub($re['wait_account'],$re['wait_interest'],2),2);
					}
				
					//更改授权用户的renew_id和状态
					$tender_ids = implode(',',$tender_ids);
					$sql3 = "update dw_borrow_tender set renew_id = {$v['id']} ,deal_status = 3 where id in ($tender_ids) ";
					$res3 = Yii::app()->dwdb->createCommand($sql3)->execute();
					if(!$res3){
						Yii::log('update dw_borrow_tender renew_id failed borrow_id in '.$tender_ids,'error',__FUNCTION__);
						Yii::app()->dwdb->rollback();
						continue;
					}
					
					//授权用户本金添加到itz_renew_borrow表展期本金
					$sql4 = "update itz_renew_borrow set renew_capital = renew_capital + {$total} where id = {$v['id']}  ";
					$res4 = Yii::app()->dwdb->createCommand($sql4)->execute();
					if(!$res4){
						Yii::log('update itz_renew_borrow renew_capital failed borrow_id = '.$v['borrow_id'].' and  total = '.$total,'error',__FUNCTION__);
						Yii::app()->dwdb->rollback();
						continue;
					}
					
				}
				
				//获取未授权用户
				$sql2 = "select id,user_id from dw_borrow_tender where borrow_id={$v['borrow_id']} and renew_id = 0 and deal_status = 0 and status = 1";
				$res2 = Yii::app()->dwdb->createCommand($sql2)->queryAll();
				if($res2){
					//对未授权用户发送站内信
					$wait_tender_ids = [];
					foreach ($res2 as $re2) {
						$remind=[];
						$remind['sent_user'] = 0;
						$remind['data']['hbfx_xmmc'] = $this->getInvestInfo($v['borrow_id'])['name'];
						$remind['data']['hbfx_htwh'] =  $re2['id'];
						$remind['receive_user'] = $re2['user_id'];
						$remind['mtype'] = 'sx_xzzq';
						$result = NewRemindService::getInstance()->SendToUser($remind,true,false,false);
						if(!$result){
							Yii::log("solutionService two  send to user message error ".print_r($re,TRUE), 'error',  __FUNCTION__);
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
				
				//将renew表状态置为调研中
				$sql9 = "update itz_renew_borrow set status = 3 ,addtime = {$now_day} where id ={$v['id']} ";
				$res9 = Yii::app()->dwdb->createCommand($sql9)->execute();
				if(!$res8){
					Yii::log('updateitz_renew_borrow status = 3 failed id = '.$v['id'],'error',__FUNCTION__);
					Yii::app()->dwdb->rollback();
					continue;
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
	public function getCompanyRenew($data=array()){
		Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
		$returnResult = array(
				'code' => '1', 'info' => 'error', 'data' => array("listInfo"=>array())
		);
		
		$s_id = isset($data['id']) ? intval($data['id']) : 0;
		if (empty($s_id)){
			$returnResult['info'] = "s_id不存在";
			return $returnResult;
		}
		
		$style_tips = array(0=>'按月付息 到期还本',1=>'按日计息 到期还本息',2=>'月底付息 到期还本息',3=>'季度付息 到期还本',4=>'等额本金 按月付款',5=>'等额本息 按月付款');
		
		//项目信息
		$sql = "select * from itz_deferred_solution where id = ".$s_id;
		$info = Yii::app()->dwdb->createCommand($sql)->queryRow();
		
		//展期金额获取
		$sql1 = "select count(*) num,sum(renew_capital) all_capital from itz_renew_borrow where s_id = ".$s_id;
		$res1 = Yii::app()->dwdb->createCommand($sql1)->queryRow();
	
		//信息披露获取
		$sql2 = "select *,FROM_unixtime(addtime,'%Y-%m-%d %H:%i:%s') addtime from itz_deferred_disclosure where s_id = ".$s_id." order by addtime desc";
		$res2 = Yii::app()->dwdb->createCommand($sql2)->queryAll();
	
		
		//取其中一条数据获取利率时间等
		$sql3 = "select * from itz_renew_borrow where s_id = ".$s_id;
		$res3 = Yii::app()->dwdb->createCommand($sql3)->queryAll();
		foreach($res3 as  $v){
			$account += $this->getInvestInfo($v['borrow_id'])['account_yes'];
			$tmp[] = $v['borrow_id'];
		}
		$borrow_ids = implode(",", $tmp);
		
		//选择了展期的人数
		$sql4 = "select count(distinct(user_id)) num from dw_borrow_tender where deal_status in (1,3) and borrow_id in ({$borrow_ids})";
		$res4 = Yii::app()->dwdb->createCommand($sql4)->queryRow();
		
		//人数
		$sql5 = "select count(distinct(user_id)) num from dw_borrow_tender where borrow_id in ({$borrow_ids})";
		$res5 = Yii::app()->dwdb->createCommand($sql5)->queryRow();
		
		
		$info['renew_all_capital'] = $account;
		$info['borrow_num'] = $res1['num'];
		$info['borrow_name'] = $info['pre_borrow_list'];
		$info['contract_number_list'] = $info['loan_contract_number'];
		$info['content_list'] = $res2;
		$info['style_tips'] = $style_tips[$res3[0]['style']];
		$info['apr'] = $res3[0]['apr'];
		$info['cycle'] = $res3[0]['cycle'];
		$info['percent'] = sprintf('%.2f', $res1['all_capital']/$account*100)."%";
		$info['agree_all_user_num'] = $res4['num'];
		$info['borrow_all_user_num'] = $res5['num'];
		$info['status'] = $res3[0]['status'];
		
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
	 * 披露信息状态修改
	 * @param array $data
	 * @return array
	 */
	public function editRenewDisclosure($data=array()){
		Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
		$returnResult = array(
				'code' => '1', 'info' => 'error', 'data' => array()
		);
		
		$id = isset($data['id']) ? intval($data['id']) : 0;
		if (empty($id) || empty($data['type'])){
			$returnResult['info'] = "参数错误";
			return $returnResult;
		}
	
		if($data['type'] == 1){
			$disclosure['status'] = 1;
		}else if($data['type'] == 2){
			if(mb_strlen($data['content']) >=20000){
				$returnResult['info'] = '内容限20000字以内';
				Yii::log($returnResult['info'],'error',__FUNCTION__);
				return $returnResult;
			}
			$disclosure['content'] = $data['content'];
		}
		
		$disclosure['updatetime'] = time();
	
		$updateRes = Yii::app()->dwdb->createCommand()->update('itz_deferred_disclosure', $disclosure, 'id=:id', array(':id' => $id));
		if (!$updateRes){
			Yii::log(__FUNCTION__ . " update itz_deferred_disclosure fail id=$id", CLogger::LEVEL_ERROR);
			$returnResult['info'] = "编辑失败";
			return $returnResult;
		}
		$returnResult['code'] = 0;
		$returnResult['info'] = "success";
		return $returnResult;
	}
	
	
	
	/**
	 * 查看详情
	 * @param array $data
	 * @return array
	 */
	public function getDisclosureInfo($data=array()){
		Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
		$returnResult = array(
				'code' => '1', 'info' => 'error', 'data' => array("listInfo"=>array())
		);
		$id = isset($data['id']) ? intval($data['id']) : 0;
		if (empty($id)){
			$returnResult['info'] = "id不存在";
			return $returnResult;
		}
		//项目信息
		$sql = "select * from itz_deferred_disclosure where id = ".$id;
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
	 * 新增披露信息
	 * @param array $data
	 * @return array
	 */
	public function addRenewDisclosure($data=array()){
	
		Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
		$returnResult = array(
				'code' => '1', 'info' => 'error', 'data' => array()
		);
		
		$s_id = isset($data['s_id']) ? intval($data['s_id']) : 0;
		if (empty($s_id)){
			$returnResult['info'] = "参数错误";
			return $returnResult;
		}
	
		if(mb_strlen($data['content']) >=20000){
			$returnResult['info'] = '内容限20000字以内';
			Yii::log($returnResult['info'],'error',__FUNCTION__);
			return $returnResult;
		}
	
		$disclosure['type'] = 1;
		$disclosure['s_id'] = intval($data['s_id']);
		$disclosure['content'] = $data['content'];
		$disclosure['updatetime'] = $disclosure['addtime'] = time();
		Yii::app()->dwdb->beginTransaction();
		try {
			$addRes = $this-> addDisclosureSystem($disclosure);
			if (!$addRes){
				Yii::app()->dwdb->rollback();
				Yii::log(__FUNCTION__ . " add itz_deferred_disclosure fail s_id=$s_id", CLogger::LEVEL_ERROR);
				$returnResult['info'] = "新增失败";
				return $returnResult;
			}
				
			
			$sql = "select borrow_id from itz_renew_borrow where s_id= {$data['s_id']}";
			$res = Yii::app()->dwdb->createCommand($sql)->queryAll();
			//查询相关的项目
			foreach($res as $val){
				$sql1 = "select DISTINCT(user_id) from dw_borrow_tender where status=1 and borrow_id = {$val['borrow_id']}";
				$res1 =  Yii::app()->dwdb->createCommand($sql1)->queryAll();
				foreach($res1 as $v){
					//查询项目相关的用户
					$remind=[];
					$remind['sent_user'] = 0;
					$remind['data']['hbfx_xmmc'] = $this->getInvestInfo($val['borrow_id'])['name'];
					$remind['receive_user'] = $v['user_id'];
					$remind['mtype'] = 'zq_qyhkgx';
					$result = NewRemindService::getInstance()->SendToUser($remind,true,false,false);
					if(!$result){
						Yii::log("solutionService zq_qyhkgx send to user message error ".print_r($v,TRUE), 'error',  __FUNCTION__);
						Yii::app()->dwdb->rollback();
						$returnResult['info'] = '站内信发送失败';
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
		$borrow_name = trim($data[1]);
		$solution['company_name']= trim($data[0]);
		$solution['content'] = $data[5];
		
		
		
		

		//$borrow_res = Borrow::model()->findBySql("select id,repayment_time,type from dw_borrow where name=:name", array(':name'=>$borrow_name));
		$borrow_res = Borrow::model()->findBySql("select id,repayment_time,type from dw_borrow where renew_status in (2,4) and name=:name", array(':name'=>$borrow_name));
		if(empty($borrow_res)){
			$returnResult['info'] = '请联系相关开发,导入数据中有非延期状态的项目:'.$borrow_name;
			Yii::log($returnResult['info'],'error',__FUNCTION__);
			return $returnResult;
		}
		
		if($borrow_res['style'] == 5){
			$returnResult['info'] = '等额本息的项目不支持处置,项目名称:'.$borrow_name;
			Yii::log($returnResult['info'],'error',__FUNCTION__);
			return $returnResult;
		}
		
		if(empty($data[6]) || empty($data[7])){
			$returnResult['info'] = '时间字段格式错误，请使用文本格式';
			Yii::log($returnResult['info'],'error',__FUNCTION__);
			return $returnResult;
		}
		
		if(mb_strlen($solution['content']) >=20000){
			$returnResult['info'] = '内容限20000字以内';
			Yii::log($returnResult['info'],'error',__FUNCTION__);
			return $returnResult;
		}
		
		$count_sql = "select count(*) num from itz_renew_borrow where borrow_id ={$borrow_res['id']}";
		$count = Yii::app()->dwdb->createCommand($count_sql)->queryRow();
		if($count['num']){
			Yii::log('项目:'.$info['name']."已经存在于展期数据中 ",'error',__FUNCTION__);
			$returnResult['info'] = "项目{$info['name']}已经存在于展期数据中";
			return $returnResult;
		}
		
		$num_sql = "select max(num) num from itz_deferred_solution where  type=1 and company_name='". $solution['company_name']."'";
		$num_res = Yii::app()->dwdb->createCommand($num_sql)->queryRow();
		if(!$num_res){
			$solution['num'] = 1;
		}else{
			$solution['num'] =$num_res['num']+1;
		}
		
		
		$solution['borrow_id'] = $borrow_res['id'];
		$returnResult['code'] = 0;
		$returnResult['info'] = "success";
		$returnResult['data'] = $solution;
		
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
		
		
		$ExcelData['excel_data'] = $allData;
		$ExcelData['excel_url'] = $res['file_src'];
		
		return $ExcelData;
	}
	
	
	/**
	 * 结果转化
	 */
	public function listResTrans($data=array()){
		Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
		
		//展期总金额
		$sql1 = "select sum(renew_capital) renew_all_capital,count(*) num from itz_renew_borrow where s_id ={$data['id']}";
		$res1 = Yii::app()->dwdb->createCommand($sql1)->queryRow();
	
		//获取项目名称和总本金
		$sql2 = "select borrow_id from itz_renew_borrow where s_id ={$data['id']}";
		$res2 = Yii::app()->dwdb->createCommand($sql2)->queryAll();
	
		foreach($res2 as  $v){
			$tmp[] = $this->getInvestInfo($v['borrow_id'])['name'];
			$account += $this->getInvestInfo($v['borrow_id'])['account_yes'];
		}
		
		
		$now_time = time();
		$style_tips = array(0=>'按月付息 到期还本',1=>'按日计息 到期还本息',2=>'月底付息 到期还本息',3=>'季度付息 到期还本',4=>'等额本金 按月付款',5=>'等额本息 按月付款');
		$status_tips = array(0=>'待处理',1=>'处理中',2=>'处理完成',3=>'调研中',4=>'已还本',5=>'已作废',6=>'待启动');

		$data['style_tips'] = $style_tips[$data['style']];
		$data['status_tips'] = $status_tips[$data['status']];
		$data['pre_borrow_list'] = implode('、', $tmp);
		$data['borrow_num'] = $res1['num'];
		$data['renew_all_capital'] = $account;
		$data['percent'] = sprintf('%.2f', $res1['renew_all_capital']/$account*100)."%";
		
		return $data;
	}
	
	

	
	
	/**
	 * 更新为展期
	 */
	public function startCompanyRenew($data = array())
	{
		Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
		$returnResult = array(
				'code' => '1', 'info' => '', 'data' => array()
		);
		
		$id = isset($data['id']) ? intval($data['id']) : 0;
		if (empty($id)){
			$returnResult['info'] = "参数错误";
			return $returnResult;
		}
		
		$idArr = $data['id'];
		$idArr = isset($idArr) ? $idArr : '';
		
		
		
		if (is_array($idArr)){
			if (count($idArr) == 1){
				$str = $idArr[0];
			}else{
				$str = implode(",", $idArr);
			}
		}else{
			$str = $idArr;
		}
		
		$sql = "select id from itz_renew_borrow where s_id = ".$id;
		$res = Yii::app()->dwdb->createCommand($sql)->queryAll();
		foreach ($res as $v){
			$tmp[] = $v['id'];
		}
		
		$str = implode(",", $tmp);
		$condition = " r.id in (".$str.")";
		$now_day = strtotime(date('Y-m-d',time()));
		ini_set("memory_limit","-1");
		ini_set('max_execution_time','1000');
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
							Yii::log("solutionService sx_zqts  send to user message error ".print_r($re2,TRUE), 'error',  __FUNCTION__);
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
							Yii::log("solutionService sx_tszq  send to user message error ".print_r($re4,TRUE), 'error',  __FUNCTION__);
							Yii::app()->dwdb->rollback();
							$returnResult['info'] = '站内信发送失败';
						}
						$tender_displode_ids[]= $re4['id'];
						$total = bcadd($total,bcsub($re4['wait_account'],$re4['wait_interest'],2),2);
					}
					
					
					//新增一条处置数据
					$dispose['type'] = $this->getInvestInfo($v['borrow_id'])['type'];
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
			
			$sql7 = "update itz_deferred_disclosure set addtime = {$now_day} where s_id ={$id} ";
			$res7 = Yii::app()->dwdb->createCommand($sql7)->execute();
			if(!$res7){
				Yii::log('update itz_deferred_disclosure  addtime = '.$now_day.' failed s_id = '.$id,'error',__FUNCTION__);
				Yii::app()->dwdb->rollback();
				$returnResult['info'] = "批量操作发生错误";
				return $returnResult;
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
				"id"    =>   $borrow_id,
				"status" => 3
		);
		$BorrowResult =$BorrowModel->findByAttributes($attributes,$criteria);
		return $BorrowResult;
	
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
	
	/**
	 * 添加solution
	 * @param $data
	 * @return bool
	 */
	public function addSolutionSystem($data)
	{
		Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
		$Solution_model = new ItzDeferredSolution();
		foreach ($data as $key => $value) {
			$Solution_model->$key = $value;
		}
	
		if ($Solution_model->save() == false) {
			Yii::log("ItzDeferredSolution_model error: " . print_r($Solution_model->getErrors(), true), "error");
			return false;
		} else {
			return $Solution_model->attributes['id'];
				
		}
	}
	
	
	/**
	 * 添加Disclosure
	 * @param $data
	 * @return bool
	 */
	public function addDisclosureSystem($data)
	{
		Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
		$Disclosure_model = new ItzDeferredDisclosure();
		foreach ($data as $key => $value) {
			$Disclosure_model->$key = $value;
		}
	
		if ($Disclosure_model->save() == false) {
			Yii::log("ItzDeferredDisclosure_model error: " . print_r($Disclosure_model->getErrors(), true), "error");
			return false;
		} else {
			return $Disclosure_model->attributes['id'];
		}
	}

}