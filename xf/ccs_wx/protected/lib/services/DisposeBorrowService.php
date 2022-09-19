<?php

class DisposeBorrowService extends ItzInstanceService
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
		$conditions = ' type = 2';
		$order = ' order by id desc';
		//条件筛选
		if (count($data) > 0) {
			
			//borrow_name搜索
			if (isset($data['borrow_name']) && $data['borrow_name'] != '') {
				$conditions .= ' and pre_borrow_list like  ' . '"%' . htmlspecialchars(addslashes(trim($data['borrow_name']))) . '%"';
			}
			
			//company_name搜索
			if (isset($data['company_name']) && $data['company_name'] != '') {
				$conditions .= ' and company_name like  ' . '"%' . htmlspecialchars(addslashes(trim($data['company_name']))) . '%"';
			}
			
			//分页条数设置
			$limit = in_array($data['limit'], array(10, 20, 30, 40, 50)) ? intval($data['limit']) : $limit;
			//请求页数
			$page = (isset($data['page']) && $data['page'] != '') ? intval($data['page']) : $page;
		}

		$sql = " select count(*) num from itz_deferred_solution where " . $conditions;
		$count = Yii::app()->dwdb->createCommand($sql)->queryRow();
		$listTotal = intval($count['num']);
		if ($listTotal == 0) {
			$returnResult['code'] = 0;
			$returnResult['info'] = '暂无数据！';
			return $returnResult;
		}
		$returnResult['data']['listTotal'] = $listTotal;

		$sql = " select * from  itz_deferred_solution where ". $conditions ;
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
		$ExcelData = $this->getDataFromExcel();
		$importInfo = $ExcelData['excel_data'];
		$excel_url = $ExcelData['excel_url'];
		
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
			/* if(count($val) != 5){
				$returnResult['info'] = '导入数据格式错误，请参考模板';
				Yii::log($returnResult['info'],'error',__FUNCTION__);
				return $returnResult;
			} */	
			
			$checkResult = $this->checkBorrowList($val);
			if ($checkResult['code']){
				return $checkResult;
			}
		}

		
		$now = time();
		//处理数据
		ini_set("memory_limit","-1");
		ini_set('max_execution_time','1000');
		Yii::app()->dwdb->beginTransaction();
		try {
			foreach ($importInfo as $val) {
				//方案表入库信息
				$solution = array(
					'company_name' => $val[0],
					'type' => 2,
					'num' => $checkResult['data']['num'],
					'status' => 1,
					'excel_url' => $excel_url,
					'pre_borrow_list' => $val[3],
					'loan_contract_number' => $val[1],
					'addtime' =>$now
				);
				$res1 = $this->addSolutionSystem($solution);
				if (!$res1){
					Yii::app()->dwdb->rollback();
					Yii::log(__FUNCTION__ . " add itz_deferred_solution fail company_name=".$val[0], CLogger::LEVEL_ERROR);
					continue;
				}
				
				//信息披露表入库信息
				$disclosure = array(
						's_id' => $res1,
						'content' => $val[4],
						'type' => 2,
						'addtime' => $now
				);
				$res2 = $this->addDisclosureSystem($disclosure);
				if (!$res2){
					Yii::app()->dwdb->rollback();
					Yii::log(__FUNCTION__ . " add itz_deferred_disclosure fail s_id=".$res1, CLogger::LEVEL_ERROR);
					continue;
				}
				
				$borrow_ids = explode('、', $val[2]);
				
				foreach($borrow_ids as $borrow_id){

					/* //已经在处置状态的项目 update s_id
					$count_sql = "select count(*) num from itz_dispose_borrow where status=2 and borrow_id ={$borrow_id}";
					$count = Yii::app()->dwdb->createCommand($count_sql)->queryRow();
					if($count['num']){
						Yii::log('项目id:'.$borrow_id."已经处于处置状态 ",'error',__FUNCTION__);
						$returnResult['info'] = "项目id：{$borrow_id}已经处于处置状态";
						return $returnResult;
					} */
					//项目信息
					$borrow_info = Borrow::model()->findByPk($borrow_id);
					
					//处置表入库信息
					$dispose = array(
						'dispose_type' => 2,
						'type' => $this->getInvestType($borrow_id),
						'status' => 2,
						'borrow_id' => $borrow_id,
						'wise_borrow_id' => $borrow_id,
						'addtime' => $now,
						'loan_contract_number' => $val[1],
						's_id' => $res1,
						'process_time'=>strtotime(date('Y-m-d',$now))
					);

					//侣行项目处置后仍需要生成企业还款明细
					if($borrow_info->lx_tag == 1){
						//兼容曾经展期过的项目进处置
						$borrow_repay_time = $borrow_info->repayment_time;
						$history_renew_info = ItzRenewBorrow::model()->findBySql("select * from itz_renew_borrow where borrow_id={$borrow_id} and status=2 order by repay_time desc");
						if($history_renew_info){
							$borrow_repay_time = $history_renew_info->repay_time;//多次展期的还本时间以上次展期还本到期日为准
						}
						//等额本息的需要期数
						if($val[8] == 5 && $val[9]<1) {
							Yii::log(__FUNCTION__ . " add itz_dispose_borrow borrow_id[$borrow_id], 还款类型为等额本息时,请填写项目分期数", CLogger::LEVEL_ERROR);
							Yii::app()->dwdb->rollback();
							continue;
						}

						$time_limit = $val[8]!=5 ? '0' :abs($val[9]);
						$value_date = strtotime($val[6]);
						$repay_time = strtotime($val[7]);
						$dispose['apr'] = abs($val[5]);//处置利率
						$dispose['value_date'] = $value_date;//处置起息时间
						$dispose['repay_time'] = $repay_time;//处置还款时间
						$dispose['style'] = $val[8];//处置还款方式
						$dispose['time_limit'] = $time_limit;//处置还款方式

						//起息日必须大于等于还款日
						if(empty($repay_time) ||  empty($value_date)){
							Yii::log(__FUNCTION__ . " borrow_id[$borrow_id], 时间字段格式错误，请使用文本格式", CLogger::LEVEL_ERROR);
							Yii::app()->dwdb->rollback();
							continue;
						}
						//起息时间校验
						if($value_date < $borrow_repay_time){
							Yii::log(__FUNCTION__ . " borrow_id[$borrow_id], 处置起息时间不能小于企业预计还款日期", CLogger::LEVEL_ERROR);
							Yii::app()->dwdb->rollback();
							continue;
						}
					}



					$res3 = $this->addDispose($dispose);
					if (!$res3){
						Yii::log(__FUNCTION__ . " add itz_dispose_borrow fail borrow_id=".$borrow_id, CLogger::LEVEL_ERROR);
						Yii::app()->dwdb->rollback();
						continue;
					}
					
					//更改项目borrow表状态
					$sql4 = "update dw_borrow set renew_status = 3 where id = {$borrow_id} ";
					$res4 = Yii::app()->dwdb->createCommand($sql4)->execute();
					if(!$res4){
						Yii::log('update dw_borrow renew_status = 3 failed id= '.$borrow_id,'error',__FUNCTION__);
						Yii::app()->dwdb->rollback();
						continue;
					}
					
					//查询tender表用户 
					$sql5 = "select * from dw_borrow_tender where status=1 and borrow_id={$borrow_id}";
					$res5 = Yii::app()->dwdb->createCommand($sql5)->queryAll();
					if($res5){
						$tender_displode_ids = [];
						foreach ($res5 as $re5) {
							$remind=[];
							$remind['sent_user'] = 0;
							$remind['data']['hbfx_xmmc'] = $this->getInvestInfo($borrow_id);
							$remind['data']['hbfx_htwh'] = $re5['id'];
							$remind['receive_user'] = $re5['user_id'];
							$remind['mtype'] = 'sx_cz';
							$result = NewRemindService::getInstance()->SendToUser($remind,true,false,false);
							if(!$result){
								Yii::log("DisposeBorrow sx_cz send to user message error ".print_r($re5,TRUE), 'error',  __FUNCTION__);
								//Yii::app()->dwdb->rollback();
								$returnResult['info'] = '站内信发送失败';
							}
							$tender_displode_ids[]= $re5['id'];
							$total = bcadd($total,bcsub($re5['wait_account'],$re5['wait_interest'],2));
						}
							
						//更新tender表用户的状态为2处置中 关联新增的处置id
						$tender_displode_ids = implode(',',$tender_displode_ids);
						$sql6 = "update dw_borrow_tender set deal_status = 2,renew_id={$res3} where id in ($tender_displode_ids) ";
						$res6 = Yii::app()->dwdb->createCommand($sql6)->execute();
						if(!$res6){
							Yii::app()->dwdb->rollback();
							Yii::log(__FUNCTION__ . " update dw_borrow_tender deal_status,renew_id={$res3} failed", CLogger::LEVEL_ERROR);
							$returnResult['info'] = '批量操作失败，更新出错';
							continue;
						}
							
					}

					//侣行项目进入处置，不作废加息不作废待收利息
					if($borrow_info->lx_tag != 1){
						//更改collection表状态
						$sql7 = "update dw_borrow_collection set type = 13 where borrow_id = {$borrow_id} and repay_time > {$now} and capital = 0 and interest > 0  and type<>5";
						$res7 = Yii::app()->dwdb->createCommand($sql7)->execute();
						if($res7 ===FALSE){
							Yii::log('update dw_borrow_collection type = 13 where borrow_id ='.$borrow_id,'error',__FUNCTION__);
							Yii::app()->dwdb->rollback();
							continue;
						}

						//更改collection表状态
						$sql9 = "update dw_borrow_collection set status=21 where status = 0 and type=5 and borrow_id = {$borrow_id} ";
						$res9 = Yii::app()->dwdb->createCommand($sql9)->execute();
						if($res9 ===FALSE){
							Yii::log('update dw_borrow_collection status = 21 where borrow_id ='.$borrow_id,'error',__FUNCTION__);
							Yii::app()->dwdb->rollback();
							continue;
						}
					}else{
						$addStatRet = $this->updateItzStatRepay($dispose, $total);
						if($addStatRet == false){
							Yii::log(" borrow_id:$borrow_id updateItzStatRepay return false",'error',__FUNCTION__);
							Yii::app()->dwdb->rollback();
							continue;
						}
					}

					//本金添加到dispose表
					$sql8 = "update itz_dispose_borrow set dispose_capital = {$total} where id = {$res3}  ";
					$res8 = Yii::app()->dwdb->createCommand($sql8)->execute();
					if($res8===false){
						Yii::log('update itz_dispose_borrow dispose_capital failed id = '.$res3,'error',__FUNCTION__);
						Yii::app()->dwdb->rollback();
						continue;
					}
					
					$total=0;
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
	 * itz_stat_repay表大项目还款计划更新, 侣行处置的项目重新生成还款计划
	 */
	private function updateItzStatRepay($dispose_info, $total){
		Yii::log("updateItzStatRepay Start, borrow_id:".$dispose_info['borrow_id']);
		if (empty($dispose_info) || $total<=0 || !is_numeric($total)){
			Yii::log("updateItzStatRepay: dispose_info is empty");
			return false;
		}

		try{
			//作废本金，但是利息要重新生成
			$stat_repay_info = ItzStatRepay::model()->findBySql("select * from itz_stat_repay where borrow_id={$dispose_info['borrow_id']} and repay_status=0 and capital>0");
			if(!$stat_repay_info){
				Yii::log("updateItzStatRepay: itz_stat_repay is empty");
				return false;
			}
			$last_tender_time = $stat_repay_info->last_tender_time;
			$stat_repay_info->repay_status = 21;
			if(false == $stat_repay_info->save(true,array('repay_status'))){
				Yii::log("updateItzStatRepay: update itz_stat_repay error ".print_r($stat_repay_info->getErrors(), true));
				return false;
			}
			if($stat_repay_info->interest>0){
				// stat_repay新插入一条数据
				$new_stat_repay = $stat_repay_info->getAttributes();
				unset($new_stat_repay['id']);
				$new_stat_repay['repay_money'] = bcsub($new_stat_repay['repay_money'], $new_stat_repay['capital'], 2);
				$new_stat_repay['capital'] = 0;
				$new_stat_repay['repay_status'] = 0;
				$new_stat_repay['addtime'] = time();
				$insert_result = BaseCrudService::getInstance()->add('ItzStatRepay', $new_stat_repay);
				if (false == $insert_result){
					Yii::log("updateItzStatRepay: insert itz_stat_repay error data:".print_r($new_stat_repay, true));
					return false;
				}
			}

			//待还利息和本金生成新的还款计划
			$eq = array(
				'account'=> $total,
				'year_apr'=> $dispose_info['apr'],
				'repayment_time'=> $dispose_info['repay_time'],
				'borrow_style'=> $dispose_info['style'],
				'borrow_time'=> $dispose_info['value_date'],
			);

			//等额本息
			if($dispose_info['style'] == 5) {
				$eq['repay_months'] = $dispose_info['time_limit'];
			}

			$interest_list = InterestPayUtil::EqualInterest($eq);
			if (empty($interest_list)) {
				Yii::log("createInterest: interest_list is empty".print_r($eq,true), "error");
				return false;
			}


			//增加利息
			$pre_repayment_time = $dispose_info['value_date'];
			foreach ($interest_list as $key => $value) {
				$new_repay['borrow_id'] = $dispose_info['borrow_id'];
				$new_repay['value_time'] = $pre_repayment_time;
				$new_repay['repay_time'] = $value['repayment_time'];
				$new_repay['company_repay_time'] = $value['repayment_time'];
				$new_repay['repay_money'] = bcadd($value['interest'], $value['capital'], 2);
				$new_repay['interest'] = sprintf('%.2f', $value['interest']);
				$new_repay['capital'] = sprintf('%.2f', $value['capital']);
				$new_repay['addtime'] = time();
				$new_repay['finance_amount'] = $total;
				$new_repay['last_tender_time'] = $last_tender_time;
				$new_repay['repay_status'] = 0;
				$insert_result = BaseCrudService::getInstance()->add('ItzStatRepay', $new_repay);
				if (false === $insert_result){
					Yii::log("updateItzStatRepay: insert itz_stat_repay error data:".print_r($new_repay, true));
					return false;
				}
				$pre_repayment_time = $value['repayment_time'];
			}

			Yii::log("updateItzStatRepay: borrow_id[{$dispose_info['borrow_id']}] end;");
			return true;

		}catch(Exception $ee){
			Yii::log("updateItzStatRepay Error: ".print_r($ee->getMessage(),true), "error");
		}

		return true;
	}
	
	/**
	 * 查看详情
	 * @param array $data
	 * @return array
	 */
	public function getCompanyDispose($data=array()){
		Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
		$returnResult = array(
				'code' => '1', 'info' => 'error', 'data' => array("listInfo"=>array())
		);
		
		$s_id = isset($data['id']) ? intval($data['id']) : 0;
		if (empty($s_id)){
			$returnResult['info'] = "s_id不存在";
			return $returnResult;
		}
		//项目信息
		$sql = "select * from itz_deferred_solution where id = ".$s_id;
		$info = Yii::app()->dwdb->createCommand($sql)->queryRow();
		
		//总本金获取
		$sql1 = "select count(*) num,sum(dispose_capital) all_capital from itz_dispose_borrow where s_id = ".$s_id;
		$res1 = Yii::app()->dwdb->createCommand($sql1)->queryRow();
		
		//信息披露获取
		$sql2 = "select *,FROM_unixtime(addtime,'%Y-%m-%d %H:%i:%s') addtime from itz_deferred_disclosure where s_id = ".$s_id." order by addtime desc";
		$res2 = Yii::app()->dwdb->createCommand($sql2)->queryAll();
		
		$info['renew_all_capital'] = $res1['all_capital'];
		$info['borrow_num'] = $res1['num'];
		$info['borrow_name'] = $info['pre_borrow_list'];
		$info['contract_number_list'] = $info['loan_contract_number'];
		$info['content_list'] = $res2;
		
		
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
	public function editDisposeDisclosure($data=array()){
		Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
		$returnResult = array(
				'code' => '1', 'info' => 'error', 'data' => array()
		);
	
		$id = isset($data['id']) ? intval($data['id']) : 0;
		if (empty($id)){
			$returnResult['info'] = "参数错误";
			return $returnResult;
		}
		
		if(mb_strlen($data['content']) >=20000){
			$returnResult['info'] = '内容限20000字以内';
			Yii::log($returnResult['info'],'error',__FUNCTION__);
			return $returnResult;
		}
		
		$disclosure['content'] = $data['content'];
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
	public function addDisposeDisclosure($data=array()){
		
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
		
		$disclosure['type'] = 2;
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
			
			
			$sql = "select borrow_id from itz_dispose_borrow where s_id= {$data['s_id']}";
			$res = Yii::app()->dwdb->createCommand($sql)->queryAll();
			
			//查询相关的项目
			foreach($res as $val){
				$sql1 = "select DISTINCT(user_id) from dw_borrow_tender where status=1 and borrow_id = {$val['borrow_id']}";
				$res1 =  Yii::app()->dwdb->createCommand($sql1)->queryAll();
				foreach($res1 as $v){
					//查询项目相关的用户
					$remind=[];
					$remind['sent_user'] = 0;
					$remind['data']['hbfx_xmmc'] = $this->getInvestInfo($val['borrow_id']);
					$remind['receive_user'] = $v['user_id'];
					$remind['mtype'] = 'sxjh_czxxgx';
					$result = NewRemindService::getInstance()->SendToUser($remind,true,false,false);
					if(!$result){
						Yii::log("DisposeBorrow sxjh_czxxgx send to user message error ".print_r($v,TRUE), 'error',  __FUNCTION__);
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
		
		$info['company_name'] = trim($data[0]);
		$info['content'] = $data[4];

		/*$sql = "select * from itz_company where  name='".$info['company_name']."'";
		$company_res = Yii::app()->dwdb->createCommand($sql)->queryRow();
		if(empty($company_res)){
			$returnResult['info'] = '未查询到此借款企业:'.$info['name'];
			Yii::log($returnResult['info'],'error',__FUNCTION__);
			return $returnResult;
		}*/
		
		if(mb_strlen($info['content']) >=20000){
			$returnResult['info'] = '信息披露限20000字以内';
			Yii::log($returnResult['info'],'error',__FUNCTION__);
			return $returnResult;
		}
		
		$borrow_ids = explode('、', $data[2]);
		foreach ($borrow_ids as $borrow_id){
			$count_sql = "select count(*) num from itz_dispose_borrow where status=2 and borrow_id ={$borrow_id}";
			$count = Yii::app()->dwdb->createCommand($count_sql)->queryRow();
			if($count['num']){
				Yii::log('项目id:'.$borrow_id."已经处于处置状态 ",'error',__FUNCTION__);
				$returnResult['info'] = "项目id：{$borrow_id}已经处于处置状态";
				return $returnResult;
			}
			
			$res = Borrow::model()->findBySql("select * from dw_borrow where id =:id", array(':id'=>$borrow_id));
			if(empty($res)){
				$returnResult['info'] = '未查询到此项目,项目ID:'.$borrow_id;
				Yii::log($returnResult['info'],'error',__FUNCTION__);
				return $returnResult;
			}
			if($res['style'] == 5){
				$returnResult['info'] = '等额本息的项目不支持处置,项目ID:'.$borrow_id;
				Yii::log($returnResult['info'],'error',__FUNCTION__);
				return $returnResult;
			}
		}
		
		$num_sql = "select max(num) num from itz_deferred_solution where  type=2 and company_name='".$info['company_name']."'";
		$num_res = Yii::app()->dwdb->createCommand($num_sql)->queryRow();
		if(!$num_res){
			$info['num'] = 1;
		}else{
			$info['num'] =$num_res['num']+1;
		}
		
		
		
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
	
		$data['borrow_num'] = count(explode(',', $data['pre_borrow_list']));
		
		//总本金获取
		$sql1 = "select count(*) num,sum(dispose_capital) all_capital from itz_dispose_borrow where s_id = ".$data['id'];
		$res1 = Yii::app()->dwdb->createCommand($sql1)->queryRow();
		
		$data['dispose_all_capital'] = $res1['all_capital'];
		$data['contract_number_list'] = $info['loan_contract_number'];
		return $data;
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
	public function getInvestType($borrow_id){
		$BorrowModel = new Borrow();
		$criteria = new CDbCriteria;
		$attributes = array(
				"id"    =>   $borrow_id
		);
		$BorrowResult =$BorrowModel->findByAttributes($attributes,$criteria);
		return $BorrowResult['type'];
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