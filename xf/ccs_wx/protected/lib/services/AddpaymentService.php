<?php

class AddpaymentService extends ItzInstanceService
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param $data [
     * deal_id 标的ID
     * type 必须为1  1尊享 2普惠
     * deal_name  借款标题
     * jys_record_number  交易所备案编号
     * deal_advisory_id  融资经办机构ID
     * deal_advisory_name  融资经办机构名称
     * deal_user_id  借款人ID
     * deal_user_real_name 借款人姓名
     * repayment_form  还款形式：0线下，1线上 目前必须为0
     * repay_type   还款类型 1常规还款2特殊还款'
     * loan_repay_type  资金类型 1-本金 2-利息 3本息全回(特殊还款可传3)
     * evidence_pic  还款或收款凭证图
     * normal_time 正常还款时间
     * plan_time 计划还款时间 可不填，填必须大于等于今日凌晨
     * repayment_total 还款金额
     * loan_user_id 投资人ID {特殊还款二选一必填}
     * deal_loan_id 投资记录ID {特殊还款二选一必填}
     * project_name 项目名称
     * project_product_class 产品大类
     * project_id
     * repay_id {常规还款必传}
     * ]
     * @return array
     */
    public function addRepaymentPlan($data = array())
    {
        Yii::log(__FUNCTION__ . print_r($data, true), 'debug');
        $returnResult = array(
            'code' => '1', 'info' => 'error', 'data' => array()
        );
        $data["plan_time"] = !empty($data['plan_time']) ? $data["plan_time"] : strtotime("midnight");
        $check_ret = $this->checkAddPlan($data);
        if ($check_ret['code'] != 0) {
            return $check_ret;
        }
        $db_name = $data['type'] == 1 ? "fdb" : "phdb";
        $data = $check_ret['data']['params'];
        if (!empty($data['id'])) {
            //删除无用数据
            $id = $data['id'];
            $data['status'] = 0;//编辑之后变为待审核状态
            $data['task_remark'] = '';//编辑之后清空备注
            unset($data['type'], $data['project_id'], $data['id']);
            $update_sql = ItzUtil::get_update_db_sql("ag_wx_repayment_plan",$data,"id = $id");
            $update_result = Yii::app()->$db_name->createCommand($update_sql)->execute();
            if ($update_result) {
                $returnResult['code'] = 0;
                $returnResult['info'] = '修改成功';
            } else {
                Yii::log(__FUNCTION__ . " update ag_wx_repayment_plan fail", CLogger::LEVEL_ERROR);
                $returnResult['info'] = '修改失败';
            }
            return $returnResult;
        }
        //删除无用数据
        unset($data['type'], $data['project_id']);
        //新增
        $data['add_admin_id'] = $userid = Yii::app()->user->id;
        $data['addtime'] = time();
        $data['addip'] = $_SERVER['REMOTE_ADDR'];
        $insert_sql = ItzUtil::get_insert_db_sql("ag_wx_repayment_plan",$data);
        $insert_result = Yii::app()->$db_name->createCommand($insert_sql)->execute();
        if ($insert_result) {
            $returnResult['data'] = $insert_result;
            $returnResult['code'] = 0;
            $returnResult['info'] = '添加成功';
        } else {
            Yii::log(__FUNCTION__ . " add ag_wx_repayment_plan fail", CLogger::LEVEL_ERROR);
            $returnResult['info'] = '添加失败';
        }
        return $returnResult;
    }
    /**
     * @param $data [
     * deal_id 标的ID
     * type 必须为1  1尊享 2普惠
     * deal_name  借款标题
     * jys_record_number  交易所备案编号
     * deal_advisory_id  融资经办机构ID
     * deal_advisory_name  融资经办机构名称
     * deal_user_id  借款人ID
     * deal_user_real_name 借款人姓名
     * repayment_form  还款形式：0线下，1线上 目前必须为0
     * repay_type   还款类型 1常规还款2特殊还款'
     * loan_repay_type  资金类型 1-本金 2-利息 3本息全回(特殊还款可传3)
     * evidence_pic  还款或收款凭证图
     * normal_time 正常还款时间
     * plan_time 计划还款时间 可不填，填必须大于等于今日凌晨
     * repayment_total 还款金额
     * loan_user_id 投资人ID {特殊还款二选一必填}
     * deal_loan_id 投资记录ID {特殊还款二选一必填}
     * project_name 项目名称
     * project_product_class 产品大类
     * project_id
     * repay_id {常规还款必传}
     * ]
     * @return array
     */
    public function checkAddPlan($data)
    {
        $return_result = array(
            'code' => 0, 'info' => '', 'data' => array('conditions' => '')
        );
        //基础参数校验， 暂时去掉type=2.普惠
        if (empty($data['deal_id']) || !is_numeric($data['deal_id']) || !in_array($data['type'], array(1 , 2))) {
            $return_result['info'] = '参数有误';
            $return_result['code'] = 2000;
            return $return_result;
        }
        if(isset($data['id']) && !is_numeric($data['id'])){
            $return_result['info'] = '参数ID有误';
            $return_result['code'] = 2001;
            return $return_result;
        }
        //表名
        $deal_id = intval($data['deal_id']);
        $db_name = $data['type'] == 1 ? "fdb" : "phdb";
        $wx_stat_repay_m = $data['type'] == 1 ? "WxStatRepay" : "PHWxStatRepay";
        //项目信息校验
        //项目信息获取+咨询方信息
        $deal_sql = "select d.id,d.user_id,d.deal_type,d.project_id,p.product_class,d.name,d.jys_record_number,p.name as project_name,
				  			 d.borrow_amount,d.rate,d.repay_time,d.loantype,d.repay_start_time,d.advisory_id,a.name as agency_name
							 from firstp2p_deal d
							 left join firstp2p_deal_project p on d.project_id=p.id
							 left join firstp2p_deal_agency a on a.id=d.advisory_id
							 where d.id=$deal_id and d.deal_status=4  ";
        $deal_info = Yii::app()->$db_name->createCommand($deal_sql)->queryRow();
        if (empty($deal_info)) {
            $return_result['info'] = "借款信息不存在";
            $return_result['code'] = 2012;
            return $return_result;
        }

        if ($deal_info['name'] != $data['deal_name']) {
            $return_result['info'] = "借款编号检验失败";
            $return_result['code'] = 2026;
            return $return_result;
        }

        //交易所备案编号
        if ($deal_info['jys_record_number'] != $data['jys_record_number']) {
            $return_result['info'] = "交易所备案编号校验失败";
            $return_result['code'] = 2022;
            return $return_result;
        }
        //融资经办机构ID
        if ($deal_info['advisory_id'] != $data['deal_advisory_id']) {
            $return_result['info'] = "融资经办机构ID校验失败";
            $return_result['code'] = 2023;
            return $return_result;
        }

        //借款人ID
        if ($deal_info['user_id'] != $data['deal_user_id']) {
            $return_result['info'] = "借款人ID校验失败";
            $return_result['code'] = 2025;
            return $return_result;
        }

        //项目信息校验
        if ($deal_info['project_name'] != $data['project_name']) {
            $return_result['info'] = "项目信息校验失败";
            $return_result['code'] = 2026;
            return $return_result;
        }

        //产品大类
        if ($deal_info['product_class'] != $data['project_product_class']) {
            $return_result['info'] = "产品大类校验失败";
            $return_result['code'] = 2022;
            return $return_result;
        }

        //必须为线下还款
        if ($data['repayment_form'] != 0) {
            $return_result['info'] = "目前仅支持线下还款";
            $return_result['code'] = 2002;
            return $return_result;
        }
        //目前只支持常规还款与特殊还款
        if (!in_array($data['repay_type'], [1, 2])) {
            $return_result['info'] = " 目前只支持常规还款与特殊还款";
            $return_result['code'] = 2003;
            return $return_result;
        }
        //资金类型 1-本金 2-利息 3本息全回
        if (!in_array($data['loan_repay_type'], [1, 2, 3])) {
            $return_result['info'] = "资金类型异常";
            $return_result['code'] = 2004;
            return $return_result;
        }
//        if (empty($data['evidence_pic'])) {
//            $return_result['info'] = "凭证信息不可以为空";
//            $return_result['code'] = 2006;
//            return $return_result;
//        }

        //校验计划还款时间
        $today_midnight = strtotime("midnight");
        if ($data['plan_time'] < $today_midnight) {
            $return_result['info'] = "计划还款时间必须大于等于今日凌晨";
            $return_result['code'] = 2010;
            return $return_result;
        }
        //录入人员校验（后台用户录入人账号真实姓名 == 融资经办机构名称 && 后台录入人员的账号类型必须是咨询方类型）
        $admin_user_id = Yii::app()->user->id;
        $adminUserInfo = (new iauth\models\User())->findByPk($admin_user_id)->attributes;
        if(empty($adminUserInfo)){
            $return_result['info'] = '后台用户不存在';
            $return_result['code'] = 2300;
            return $return_result;
        }
        //非超级管理员限制
        if($adminUserInfo['username'] != Yii::app()->iDbAuthManager->admin){
            if(empty($data['id'])){
                //录入
                if($adminUserInfo['user_type'] == 2){
                    if($adminUserInfo['realname'] != $deal_info['agency_name']){
                        $return_result['info'] = '后台用户真实姓名与咨担保机构名称不一致';
                        $return_result['code'] = 2302;
                        return $return_result;
                    }
                }
            }
            if(!empty($data['id'])){
                //编辑
                if($adminUserInfo['user_type'] != 1 ){
                    $return_result['info'] = '后台用户账号类型必须是普通类型才能编辑';
                    $return_result['code'] = 2303;
                    return $return_result;
                }
            }
        }
        //借款人信息校验
        if (empty($data['deal_user_real_name'])) {
            //借款人信息获取
            $user_sql = "select u.id,u.user_type,e.company_name,uc.name
							from firstp2p_user u
							left join firstp2p_enterprise e on e.user_id=u.id
							left join firstp2p_user_company uc on uc.user_id=u.id
							where u.id={$deal_info['user_id']}";
            $user_info = Yii::app()->fdb->createCommand($user_sql)->queryRow();
            if (empty($user_info)) {
                $return_result['info'] = "借款人信息异常";
                $return_result['code'] = 2020;
                return $return_result;
            }
            //借款企业名称
            $company_name = $user_info['user_type'] == 1 ? $user_info['company_name'] : $user_info['name'];
            if ($data['repay_type'] == 2) {
                $data['deal_advisory_name'] = $deal_info['agency_name'];
                $data['deal_user_real_name'] = $company_name;
            }
            if($company_name != $data['deal_user_real_name']){
                $return_result['info'] = "借款人信息错误";
                $return_result['code'] = 2021;
                return $return_result;
            }
        }

        //还款数据查询条件
        $conditions = " deal_id={$data['deal_id']} and status=0 and `time`={$data['normal_time']} AND last_part_repay_time = 0 ";
        //常规还款是，还款列表ID非空
        if ($data['repay_type'] == 1) {
            if (empty($data['repay_id'])) {
                $return_result['info'] = "还款列表ID为空";
                $return_result['code'] = 2013;
                return $return_result;
            }
            //融资经办机构名称
            if ($deal_info['agency_name'] != $data['deal_advisory_name']) {
                $return_result['info'] = "融资经办机构名称 校验失败";
                $return_result['code'] = 2024;
                return $return_result;
            }
            //常规还款校验
            //还款列表
            $stat_repay = $wx_stat_repay_m::model()->findByPk($data['repay_id']);
            if (!$stat_repay) {
                $return_result['info'] = "还款列表数据不存在";
                $return_result['code'] = 2014;
                return $return_result;
            }
            //正常还款时间常规还款项校验
            if ($data['normal_time'] != $stat_repay->loan_repay_time) {
                $return_result['info'] = "正常还款时间数据有误";
                $return_result['code'] = 2016;
                return $return_result;
            }
            //待还本金校验（统计表）
            $wait_capital = bcsub($stat_repay->repay_amount, $stat_repay->repaid_amount, 2);
            if (!FunctionUtil::float_equal($wait_capital, $data['repayment_total'], 3)) {
                $return_result['info'] = "还款金额校验失败";
                $return_result['code'] = 2015;
                return $return_result;
            }
            //待还状态
            if ($stat_repay->repay_status != 0) {
                $return_result['info'] = "还款统计表状态已还";
                $return_result['code'] = 2027;
                return $return_result;
            }
            $dealLoadConditions = '';
        } //特殊还款
        elseif ($data['repay_type'] == 2) {
            //出借人ID与投资记录ID二选一必填
            if (empty($data['loan_user_id']) && empty($data['deal_loan_id'])) {
                $return_result['info'] = "出借人ID与投资记录ID二选一必填";
                $return_result['code'] = 2017;
                return $return_result;
            }

            //特殊还款出借人ID
            if (!empty($data['loan_user_id'])) {
                $conditions .= " and loan_user_id in ({$data['loan_user_id']}) ";
                $dealLoadConditions = " and debt.user_id in ({$data['loan_user_id']})";
            }
            //特殊还款投资记录ID
            if (!empty($data['deal_loan_id'])) {
                $conditions .= " and deal_loan_id in ({$data['deal_loan_id']}) ";
                $dealLoadConditions = " and debt.tender_id in ({$data['deal_loan_id']})";
            }

        }
        //资金类型条件限制
        switch ($data['loan_repay_type']) {
            case 1:
                $conditions .= ' and type=1';
                break;
            case 2:
                $conditions .= ' and ( type=2 or (type=1 and money=0)) ';
                break;
            case 3:
                $conditions .= ' and type in (1, 2)';
                break;
        }

        //校验还款总额，不允许有误差
        $plan_sql = "select sum(money) from firstp2p_deal_loan_repay where $conditions";
        $plan_total = Yii::app()->$db_name->createCommand($plan_sql)->queryScalar();
        if(empty($plan_total)){
            $return_result['info'] = "没有匹配的还款计划";
            $return_result['code'] = 2018;
            return $return_result;
        }
        $plan_total = !empty($plan_total) ? $plan_total : 0;
        if (!$plan_total || !FunctionUtil::float_equal($plan_total, $data['repayment_total'], 3)) {
            $return_result['info'] = "还款总额校验有误，实际应还总额：$plan_total";
            $return_result['code'] = 2019;
            return $return_result;
        }

        if(!empty($data['id'])){
            // $plan_info = WxRepaymentPlan::model()->findByPk($data['id']);
            $plan_info = Yii::app()->$db_name->createCommand("select * from ag_wx_repayment_plan where id = {$data['id']}")->queryRow();
            if (empty($plan_info)) {
                $returnResult['code'] = 2020;
                $returnResult['info'] = '参数异常修改失败';
                return $returnResult;
            }
        }
        //编辑不需要进行重复添加验证
        if(empty($data['id'])){
            //是否重复添加校验
            if($data['loan_repay_type'] == 1){
                //本金校验loan_repay_type 1,3
                $wx_normal = "normal_time = '{$data["normal_time"]}' and  deal_id = {$data['deal_id']}  and status IN(0,1,2) and loan_repay_type IN(1,3)";
            }elseif($data['loan_repay_type'] == 2){
                //利息校验loan_repay_type 2,3
                $wx_normal = "normal_time = '{$data["normal_time"]}' and  deal_id = {$data['deal_id']}  and status IN(0,1,2) and loan_repay_type IN(2,3)";
            }else{
                //本息校验loan_repay_type 1,2,3
                $wx_normal = "normal_time = '{$data["normal_time"]}' and  deal_id = {$data['deal_id']}  and status IN(0,1,2) and loan_repay_type IN(1,2,3)";
            }
            $sql = "select * from ag_wx_repayment_plan WHERE  {$wx_normal}";
            $repayplan_info = Yii::app()->$db_name->createCommand($sql)->queryRow();
            if (!empty($repayplan_info)) {
                $returnResult['code'] = 2021;
                $returnResult['info'] = '不允许重复添加常规还款';
                return $returnResult;
            }
        }
        //校验是否有兑换中的债权
        $dataArr = ["type" => $data['type'],"repay_type" => $data['repay_type'], "user_id" => $data['loan_user_id'], "deal_id" => $data['deal_id'],"tender_id" => $data['deal_loan_id']];
        $checkDebtExLog = $this->checkDebtExchangeLog($dataArr);
        if($checkDebtExLog['code'] != 0){
            $returnResult['code'] = $checkDebtExLog['code'];
            $returnResult['info'] = $checkDebtExLog['info'];
            return $returnResult;
        }
        //查询是否有部分还款待审核和审核成功的还款记录
        $checkPartialRepayDetail = $this->checkPartialRepayDetail($dataArr);
        if($checkPartialRepayDetail['code'] != 0){
            $returnResult['code'] = $checkPartialRepayDetail['code'];
            $returnResult['info'] = $checkPartialRepayDetail['info'];
            return $returnResult;
        }
        //校验待付款与待收款确认状态(常规与特殊还款)
        $checkDebtStatus = $this->checkDebtStatus($deal_id, $data['type'], $dealLoadConditions);
        if($checkDebtStatus['code'] != 0){
            $returnResult['code'] = $checkDebtStatus['code'];
            $returnResult['info'] = $checkDebtStatus['info'];
            return $returnResult;
        }
        //校验成功
        $return_result['data']['conditions'] = $conditions;
        $return_result['data']['params'] = $data;
        $return_result['info'] = '校验成功';
        return $return_result;
    }

    /**
     * @param $deal_id
     * @param $type
     * @param $dealLoadConditions
     * @return array
     */
    public function checkDebtStatus($deal_id, $type, $dealLoadConditions = '')
    {
        $return_result = array(
            'code' => 0, 'info' => '', 'data' => array()
        );
        if(empty($type) || !in_array($type,[1,2]) || empty($deal_id) || !is_numeric($deal_id)){
            $return_result['info'] = '参数有误';
            $return_result['code'] = 2000;
            return $return_result;
        }
        $db_name = $type == 1 ? "fdb" : "phdb";
        $db_base_name = $type == 1 ? "尊享" : "普惠供应链";
        //根据项目id查询投资记录中有债转待付款与待收款确认状态
        $debtinfo = Yii::app()->$db_name->createCommand("select * from firstp2p_debt debt where debt.borrow_id = {$deal_id} and debt.status IN(5,6) {$dealLoadConditions}")->queryRow();
        if(!empty($debtinfo)){
            if($debtinfo['status'] == 5){
                $return_result['info'] = "有已承接待付款的债权，不能添加，{$db_base_name}中错误投资记录ID".$debtinfo['tender_id'];
                $return_result['code'] = 2522;
                return $return_result;
            }
            if($debtinfo['status'] == 6){
                $return_result['info'] = "有待收款确认的债权，不能添加，{$db_base_name}中错误投资记录ID".$debtinfo['tender_id'];
                $return_result['code'] = 2523;
                return $return_result;
            }
        }
        //根据项目id查询投资记录中有债转->取消
        $debtinfo = Yii::app()->$db_name->createCommand("select * from firstp2p_debt debt where borrow_id = {$deal_id} and status = 1 {$dealLoadConditions}")->queryAll();
        if(!empty($debtinfo)){
            $url = Yii::app()->c->wx_confirm_debt_api."/Launch/DebtGarden/CancelDebt";
            foreach($debtinfo as $key => $val){
                $params = array(
                    'products'     => $type,
                    'debt_id'      => $val['id'],
                    'checkuser'    => 2,
                );
                $result = $this->curlRequest($url,'POST',$params);
                if($result['code'] != 0){
                    $return_result['info'] = "债权取消失败，{$db_base_name}中错误债转记录ID".$val['id'];
                    $return_result['code'] = 2524;
                    return $return_result;
                }
            }
        }
        return $return_result;
    }
    /**
     * 查询是否有部分还款待审核和审核成功的还款记录
     * @param $data [
     * type 必须为1  1尊享 2普惠
     * repay_type   还款类型 1常规还款2特殊还款
     * user_id 用户ID {特殊还款二选一必填}
     * tender_id 投资记录id {特殊还款二选一必填}
     * deal_id 项目id 必填
     * ]
     * @return array
     */
    public function checkPartialRepayDetail($data)
    {
        $return_result = array(
            'code' => 0, 'info' => '', 'data' => array()
        );
        $type = $data['type'];
        $user_id = $data['user_id'];
        $tender_id = $data['tender_id'];
        $deal_id = $data['deal_id'];
        $repay_type = $data['repay_type'];
        if(empty($type) || !in_array($type,[1,2]) || empty($deal_id) || !is_numeric($deal_id) || !in_array($repay_type,[1,2]) || !is_numeric($repay_type)){
            $return_result['info'] = '参数有误';
            $return_result['code'] = 2000;
            return $return_result;
        }
        //特殊还款验证
        if($repay_type == 2){
            //出借人ID与投资记录ID二选一必填
            if (empty($user_id) && empty($tender_id)) {
                $return_result['info'] = "出借人ID与投资记录ID二选一必填";
                $return_result['code'] = 2017;
                return $return_result;
            }
        }
        $where = "awprd.deal_id = $deal_id and awpr.status IN(1,2)";
        if(!empty($user_id)){
            $where .= " and awprd.user_id in ($user_id)";
        }
        if(!empty($tender_id)){
            $where .= " and awprd.deal_loan_id in ($tender_id)";
        }
        $db_name = $type == 1 ? "fdb" : "phdb";
        $repayment = Yii::app()->$db_name->createCommand("select awpr.status from ag_wx_partial_repayment awpr left join ag_wx_partial_repay_detail awprd on awpr.id = awprd.partial_repay_id where $where")->queryRow();
        if(!empty($repayment) && $repayment['status'] == 1){
            $return_result['info'] = '您有部分还款待审核的还款记录，无法添加';
            $return_result['code'] = 2119;
            return $return_result;
        }
        if(!empty($repayment) && $repayment['status'] == 2){
            $return_result['info'] = '您有部分还款审核已通过的还款记录，无法添加';
            $return_result['code'] = 2120;
            return $return_result;
        }
        $return_result['info'] = '校验成功';
        return $return_result;
    }
    /**
     * 校验债权兑换日志表中status = 1的状态
     * @param $data [
     * type 必须为1  1尊享 2普惠
     * repay_type   还款类型 1常规还款2特殊还款
     * repay_status 1:验证常规特殊还款 2:直接验证（绕过常规特殊还款）
     * user_id 用户ID {特殊还款二选一必填}
     * tender_id 投资记录id {特殊还款二选一必填}
     * deal_id 项目id 必填
     * ]
     * @return array
     */
    public function checkDebtExchangeLog($data)
    {
        $return_result = array(
            'code' => 0, 'info' => '', 'data' => array()
        );
        $type = $data['type'];
        $user_id = $data['user_id'];
        $tender_id = $data['tender_id'];
        $deal_id = $data['deal_id'];
        $repay_type = $data['repay_type'];
        $repay_status = isset($data['repay_status']) ? $data['repay_status'] : 1;
        if($repay_status == 1){
            if(!in_array($repay_type,[1,2]) || !is_numeric($repay_type)){
                $return_result['info'] = '参数有误';
                $return_result['code'] = 2000;
                return $return_result;
            }
        }
        if(empty($type) || !in_array($type,[1,2]) || empty($deal_id) || !is_numeric($deal_id) || !in_array($repay_status,[1,2])){
            $return_result['info'] = '参数有误';
            $return_result['code'] = 2000;
            return $return_result;
        }
        if($repay_status == 1){
            //特殊还款验证
            if($repay_type == 2){
                //出借人ID与投资记录ID二选一必填
                if (empty($user_id) && empty($tender_id)) {
                    $return_result['info'] = "出借人ID与投资记录ID二选一必填";
                    $return_result['code'] = 2017;
                    return $return_result;
                }
            }
        }
        $where = "borrow_id = $deal_id";
        if(!empty($user_id)){
            $where .= " and user_id in ($user_id)";
        }
        if(!empty($tender_id)){
            $where .= " and tender_id in ($tender_id)";
        }
        $db_name = $type == 1 ? "fdb" : "phdb";
        $exchangeLogInfo = Yii::app()->$db_name->createCommand("select * from firstp2p_debt_exchange_log where $where and status = 1")->queryRow();
        if(!empty($exchangeLogInfo)){
            $return_result['info'] = '您有兑换中的债权，无法添加';
            $return_result['code'] = 2023;
            return $return_result;
        }
        $return_result['info'] = '校验成功';
        return $return_result;
    }
    /**
     * 添加RepaymentPlan表
     * @param $data
     * @return bool
     */
    public function addRepaymentPlanSystem($data)
    {
        Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
        $fields = array(
            "deal_id" => $data['deal_id'],
            "repay_type" => 1,//还款类型1常规还款2特殊还款
            "repay_id" => isset($data['stat_repay_id']) ? $data['stat_repay_id'] : 0, //常规还款时，stat_repay主键必传
            "repayment_form" => 0, //还款形式：0线下，1线上
            "loan_repay_type" => $data['repay_type'], //资金类型 1-本金 2-利息 3本息全回
            "project_product_class" => $data['project_product_class'], //产品大类，包括包括“产融贷”、“消费贷”和“公益标”
            "deal_name" => $data['deal_name'], //借款标题
            "jys_record_number" => $data['jys_record_number'], //交易所备案编号
            "project_name" => $data['project_name'], //项目名称，拼接规则待定
            "deal_advisory_id" => $data['deal_advisory_id'], //融资经办机构ID
            "deal_advisory_name" => $data['deal_advisory_name'], //融资经办机构名称
            "deal_user_id" => $data['deal_user_id'], //创建该投标的用户id,借款人ID
            "normal_time" => $data['loan_repay_time'], //正常还款时间,多期还款日时以逗号分隔时间戳
            "loan_user_id" => "", //出借人id,多个输入时以逗号分隔??
            "repayment_total" => $data['repay_amount'], //还款总额
            "plan_time" => $data['plan_repay_time'], //计划还款时间
            "deal_loan_id" => "", //投资记录ID,多笔逗号分隔
            "status" => 0, //还款处理状态：0待审核 1审核通过 2审核失败 3还款成功 4还款失败
            "evidence_pic" => isset($data['evidence_pic']) ? $data['evidence_pic'] : "", //还款或收款凭证图
            "send_notice" => 0, //发送通知 0不发送 1发送
            "deal_user_real_name" => $data['deal_user_real_name'], //借款人姓名
            "add_admin_id" => $userid = Yii::app()->user->id, //添加人ID
            "addtime" => time(), //添加时间
            "addip" => $_SERVER['REMOTE_ADDR'], //添加人IP
            "attachments_url" => isset($data['attachments_url']) ? $data['attachments_url'] : "", //上传附件地址
        );
        $sql = $this->get_insert_db_sql("ag_wx_repayment_plan", $fields);
        Yii::log(__FUNCTION__ . print_r($sql, true), 'debug');
        $result = \Yii::app()->fdb->createCommand($sql)->execute();
        if (!$result) {
            $returnResult['info'] = '添加失败';
            return $returnResult;
        }
        $returnResult['code'] = 0;
        $returnResult['info'] = '添加成功';
        $returnResult['data'] = $data;
        return $returnResult;

    }

    /**
     * 获取插入语句
     *
     * @param    string $tbl_name 表名
     * @param    array $info 数据
     */
    public function get_insert_db_sql($tbl_name, $info)
    {
        if (is_array($info) && !empty($info)) {
            $i = 0;
            foreach ($info as $key => $val) {
                $fields[$i] = $key;
                $values[$i] = $val;
                $i++;
            }
            $s_fields = "(" . implode(",", $fields) . ")";
            $s_values = "('" . implode("','", $values) . "')";
            $sql = "INSERT INTO
                       $tbl_name
                       $s_fields
                   VALUES
                       $s_values";
            return $sql;
        } else {
            return false;
        }
    }

    /**
     * 获取更新SQL语句
     *
     * @param    string $tbl_name 表名
     * @param    array $info 数据
     * @param    array $condition 条件
     */
    public function get_update_db_sql($tbl_name, $info, $condition)
    {
        $i = 0;
        $data = '';
        if (is_array($info) && !empty($info)) {
            foreach ($info as $key => $val) {
                if (isset($val)) {
                    $val = $val;
                    if ($i == 0 && $val !== null) {
                        $data = $key . "='" . $val . "'";
                    } else {
                        $data .= "," . $key . " = '" . $val . "'";
                    }
                    $i++;
                }
            }
            $sql = "UPDATE " . $tbl_name . " SET " . $data . " WHERE " . $condition;
            return $sql;
        } else {
            return false;
        }
    }
    private function curlRequest($api, $method = 'GET', $params = array(), $headers = [], $json_decode = true)
    {
        $curl = curl_init();
        switch (strtoupper($method)) {
            case 'GET':
                if (!empty($params)) {
                    $api .= (strpos($api, '?') ? '&' : '?') . http_build_query($params);
                }
                curl_setopt($curl, CURLOPT_HTTPGET, true);
                break;
            case 'POST':
                curl_setopt($curl, CURLOPT_POST, true);
                if(is_array($params)) {
                    $params = http_build_query($params);
                }
                curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
                break;
            case 'PUT':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
                break;
            case 'DELETE':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
                curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
                break;
        }

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $api);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($curl, CURLOPT_TIMEOUT, 60);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        if(isset($_SERVER['HTTP_USER_AGENT'])){
            curl_setopt($curl,CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        }

        $response = curl_exec($curl);
        if ($response === false) {
            curl_close($curl);
            return false;
        } else {
            // 解决windows 服务器 BOM 问题
            $response = trim($response, chr(239).chr(187).chr(191));
            if ($json_decode) {
                $response = json_decode($response, true);
            }
        }
        curl_close($curl);
        return $response;
    }
}