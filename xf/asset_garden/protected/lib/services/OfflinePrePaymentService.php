<?php
class OfflinePrePaymentService extends ItzInstanceService
{

	/**待还列表数据校验
	 * @param $plan_id
	 * @param $type 1尊享 2普惠
	 * @return array
	 */
	public function checkRepaymentPlan($plan_id, $type=1){
		$return_result = array(
			'code'=>0, 'info'=>'', 'data'=>array('conditions'=>'')
		);

		//基础参数校验
		if(empty($plan_id) || !is_numeric($plan_id) || !in_array($type, [1,2])){
			$return_result['info'] = '参数有误';
			$return_result['code'] = 2000;
			return $return_result;
		}

		//表名
		$deal_m = $type==1 ? "Deal" : "PHDeal";
		$db_name = $type==1 ? "db" : "phdb";
		$wx_stat_repay_m = $type==1 ? "WxStatRepay" : "PHWxStatRepay";
		$wx_repayment_plan_m = $type==1 ? "WxRepaymentPlan" : "PHWxRepaymentPlan";

		//还款详情
		$plan_info = $wx_repayment_plan_m::model()->findbyPk($plan_id);
		if(!$plan_info){
			$return_result['info'] = '还款详情数据异常';
			$return_result['code'] = 2001;
			return $return_result;
		}
		//必须为线下还款
		if($plan_info->repayment_form != 0){
			$return_result['info'] = "repayment_form=$plan_info->repayment_form, 目前仅支持线下还款";
			$return_result['code'] = 2002;
			return $return_result;
		}
		//目前只支持常规还款与特殊还款
		if(!in_array($plan_info->repay_type, [1, 2])){
			$return_result['info'] = "repay_type=$plan_info->repay_type not in (1,2), 目前只支持常规还款与特殊还款";
			$return_result['code'] = 2003;
			return $return_result;
		}
		//资金类型 1-本金 2-利息 3本息全回
		if(!in_array($plan_info->loan_repay_type, [1, 2, 3])){
			$return_result['info'] = "loan_repay_type=$plan_info->loan_repay_type not in (1,2,3), 资金类型异常";
			$return_result['code'] = 2004;
			return $return_result;
		}
		//校验任务状态
		if($plan_info->status != 1){
			$return_result['info'] = "status=$plan_info->status, 非审核通过状态";
			$return_result['code'] = 2005;
			return $return_result;
		}
		//凭证信息
		if(empty($plan_info->evidence_pic)){
			$return_result['info'] = "evidence_pic=$plan_info->evidence_pic, 凭证信息不可以为空";
			$return_result['code'] = 2006;
			return $return_result;
		}
		//校验项目信息
		if(empty($plan_info->deal_id) || $plan_info->deal_id<0 ){
			$return_result['info'] = "deal_id=$plan_info->deal_id, 借款编号数据有误";
			$return_result['code'] = 2007;
			return $return_result;
		}
		//任务完成时间数据错误
		if($plan_info->task_success_time != 0){
			$return_result['info'] = "task_success_time=$plan_info->task_success_time, 任务完成时间数据错误";
			$return_result['code'] = 2008;
			return $return_result;
		}
		//添加人启动人不可为空
		if(empty($plan_info->start_admin_id) || empty($plan_info->add_admin_id) ){
			$return_result['info'] = "添加人[$plan_info->add_admin_id]或审核人数据[$plan_info->start_admin_id]异常";
			$return_result['code'] = 2009;
			return $return_result;
		}
		//校验计划还款时间
		$today_midnight = strtotime("midnight");
		if($plan_info->plan_time != $today_midnight){
			$return_result['info'] = "plan_time=$plan_info->plan_time !=$today_midnight, 计划还款时间数据有误";
			$return_result['code'] = 2010;
			return $return_result;
		}

		//校验正常还款时间
		/*
		if($plan_info->normal_time > $today_midnight){
			$return_result['info'] = "normal_time=$plan_info->normal_time > $today_midnight, 目前不支持提前还款";
			$return_result['code'] = 2011;
			return $return_result;
		}*/

		//项目信息校验
		$deal_info = $deal_m::model()->findByPk($plan_info->deal_id);
		if(!$deal_info || $deal_info->deal_status!=4 || $deal_info->name != $plan_info->deal_name
			|| $deal_info->jys_record_number != $plan_info->jys_record_number
			|| $deal_info->advisory_id != $plan_info->deal_advisory_id
			|| $deal_info->user_id != $plan_info->deal_user_id
		){
			$return_result['info'] = "deal_id=$plan_info->deal_id 借款编号其他信息检验失败";
			$return_result['code'] = 2012;
			return $return_result;
		}

		//还款数据查询条件
		$conditions = " deal_id=$plan_info->deal_id and status=0 and `time`=$plan_info->normal_time ";

		//常规还款是，还款列表ID非空
		if($plan_info->repay_type == 1){
			if(empty($plan_info->repay_id)){
				$return_result['info'] = "repay_id=$plan_info->repay_id 还款列表ID为空";
				$return_result['code'] = 2013;
				return $return_result;
			}
			//还款列表
			$stat_repay = $wx_stat_repay_m::model()->findByPk($plan_info->repay_id);
			if(!$stat_repay){
				$return_result['info'] = "repay_id=$plan_info->repay_id 还款列表数据不存在";
				$return_result['code'] = 2014;
				return $return_result;
			}
			//待还本金校验
			$wait_capital = bcsub($stat_repay->repay_amount, $stat_repay->repaid_amount, 2);
			if(!FunctionUtil::float_equal($wait_capital, $plan_info->repayment_total, 3)){
				$return_result['info'] = "repayment_total[$plan_info->repayment_total]!=wait_capital[$wait_capital] 待还金额不一致";
				$return_result['code'] = 2015;
				return $return_result;
			}
			//正常还款时间常规还款项校验
			if ($plan_info->normal_time != $stat_repay->loan_repay_time) {
				$return_result['info'] = "stat_repay.time[$stat_repay->loan_repay_time]!=plan_info.time[$plan_info->normal_time] 还款时间不一致";
				$return_result['code'] = 2016;
				return $return_result;
			}
			//待还状态
			if($stat_repay->repay_status != 0){
				$return_result['info'] = " stat_repay.repay_status[$stat_repay->repay_status]!=0 还款统计表状态已还";
				$return_result['code'] = 2027;
				return $return_result;
			}
		}
		//特殊还款
		elseif($plan_info->repay_type == 2) {
			//出借人ID与投资记录ID二选一必填
			if (empty($plan_info->loan_user_id) && empty($plan_info->deal_loan_id)){
				$return_result['info'] = "出借人ID与投资记录ID二选一必填";
				$return_result['code'] = 2017;
				return $return_result;
			}

			//特殊还款出借人ID
			if (!empty($plan_info->loan_user_id)) {
				$conditions .= " and loan_user_id in ($plan_info->loan_user_id) ";
			}
			//特殊还款投资记录ID
			if (!empty($plan_info->deal_loan_id)) {
				$conditions .= " and deal_loan_id in ($plan_info->deal_loan_id) ";
			}
		}

		//资金类型条件限制
		switch ($plan_info->loan_repay_type) {
			case 1: $conditions .= ' and type=1'; break;
			case 2: $conditions .= ' and ( type=2 or (type=1 and money=0)) '; break;
			case 3: $conditions .= ' and type in (1, 2)'; break;
		}

		//校验还款总额，不允许有误差
		$plan_sql = "select sum(money) from offline_deal_loan_repay where $conditions";
		$plan_total = Yii::app()->$db_name->createCommand($plan_sql)->queryScalar();
		if(!$plan_total || !FunctionUtil::float_equal($plan_total, $plan_info->repayment_total, 3)){
			$return_result['info'] = "还款总额校验有误，实际应还总额：$plan_total";
			$return_result['code'] = 2019;
			return $return_result;
		}

		//校验成功
		$return_result['data']['conditions'] = $conditions;
		$return_result['info'] = '校验成功';
		return $return_result;
	}

	/**部分还本待还列表数据校验
	 * @param $plan_id
	 * @return array
	 */
	public function checkPartRepayment($plan_id){
		$return_result = array(
			'code'=>0, 'info'=>'', 'data'=>array('conditions'=>'')
		);

		//基础参数校验， 暂时去掉type=2.普惠
		if(empty($plan_id) || !is_numeric($plan_id)){
			$return_result['info'] = '参数有误';
			$return_result['code'] = 2000;
			return $return_result;
		}

		//表名
		$deal_m = "OfflineDeal";
		$db_name = "offlinedb";
		$part_repay_m = "OfflinePartialRepay";
		$part_repay_detail_m = "OfflinePartialRepayDetail";
		//还款详情
		$plan_info = $part_repay_m::model()->findbyPk($plan_id);
		if(!$plan_info){
			$return_result['info'] = '还款详情数据异常';
			$return_result['code'] = 2001;
			return $return_result;
		}
		//必须为线下还款
		if($plan_info->success_number <= 0){
			$return_result['info'] = "success_number=$plan_info->success_number <= 0 ";
			$return_result['code'] = 2002;
			return $return_result;
		}
		//成功金额必须大于0
		if(FunctionUtil::float_bigger_equal(0, $plan_info->total_successful_amount, 2)){
			$return_result['info'] = "total_successful_amount=$plan_info->total_successful_amount <= 0 ";
			$return_result['code'] = 2003;
			return $return_result;
		}
		//校验任务状态
		if($plan_info->status != 2){
			$return_result['info'] = "status=$plan_info->status, 非审核通过状态";
			$return_result['code'] = 2005;
			return $return_result;
		}
		//凭证信息
		if(empty($plan_info->proof_url)){
			$return_result['info'] = "proof_url=$plan_info->proof_url, 凭证信息不可以为空";
			$return_result['code'] = 2006;
			return $return_result;
		}
		//任务完成时间数据错误
		if($plan_info->task_success_time != 0){
			$return_result['info'] = "task_success_time=$plan_info->task_success_time, 任务完成时间数据错误";
			$return_result['code'] = 2008;
			return $return_result;
		}
		//添加人启动人不可为空
		if(empty($plan_info->admin_user_id) || empty($plan_info->examine_user_id) ){
			$return_result['info'] = "添加人[$plan_info->admin_user_id]或审核人数据[$plan_info->examine_user_id]异常";
			$return_result['code'] = 2009;
			return $return_result;
		}
		//校验计划还款时间
		$today_midnight = strtotime("midnight");
		if($plan_info->pay_plan_time != $today_midnight){
			$return_result['info'] = "pay_plan_time=$plan_info->pay_plan_time !=$today_midnight, 计划还款时间数据有误";
			$return_result['code'] = 2010;
			return $return_result;
		}

		//校验正常还款时间
		/*
		if($plan_info->normal_time > $today_midnight){
			$return_result['info'] = "normal_time=$plan_info->normal_time > $today_midnight, 目前不支持提前还款";
			$return_result['code'] = 2011;
			return $return_result;
		}*/

		//借款明细校验
		$part_detail = $part_repay_detail_m::model()->findAll("partial_repay_id = $plan_info->id and status=1");
		if(!$part_detail){
			$return_result['info'] = " offline_partial_repay_detail error ";
			$return_result['code'] = 2011;
			return $return_result;
		}

		//逐一校验
		$success_money = $success_num = 0;
		$deal_load_data = array();
		foreach ($part_detail as $value){
			if(empty($value->deal_loan_id) || $value->repay_money<=0){
				$return_result['info'] = " id:$value->id  deal_loan_id:$value->deal_loan_id or repay_money:$value->repay_money error ";
				$return_result['code'] = 2012;
				return $return_result;
			}
			$success_num++;
			$success_money = bcadd($success_money, $value->repay_money, 2);
			$deal_load_data[$value->deal_loan_id] = $value->repay_money;
		}

		//有效数据校验
		if($success_num != $plan_info->success_number || !FunctionUtil::float_equal($success_money, $plan_info->total_successful_amount, 3)){
			$return_result['info'] = " id:$value->id success_num:$success_num or success_money:$success_money error  ";
			$return_result['code'] = 2013;
			return $return_result;
		}

		//投资记录详情
		$deal_loan_ids = array_keys($deal_load_data);
		$load_sql = "select * from offline_deal_load where id in (".implode(',', $deal_loan_ids).") ";
		$loan_repay_list = Yii::app()->$db_name->createCommand($load_sql)->queryAll();
		if(!$loan_repay_list){
			$return_result['info'] = " offline_deal_load error ";
			$return_result['code'] = 2014;
			return $return_result;
		}

		//逐一校验投资记录
		foreach ($loan_repay_list as $d_value){
			if(FunctionUtil::float_bigger($deal_load_data[$d_value['id']], $d_value['wait_capital'], 2)){
				$return_result['info'] = "id:$value->id repay_money[{$deal_load_data[$d_value['id']]}]>wait_capital[{$d_value['wait_capital']}] ";
				$return_result['code'] = 2015;
				return $return_result;
			}
		}

		//校验成功
		//$return_result['data'] = $deal_load_data;
		$return_result['info'] = '校验成功';
		return $return_result;
	}
}
