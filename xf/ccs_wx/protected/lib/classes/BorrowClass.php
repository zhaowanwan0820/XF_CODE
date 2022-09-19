<?php

/**
 * BorrowClass .
 */

class BorrowClass  {
	
	public $code = '0000';
	public $message = NULL;
	
	//创建高级项目
	public function createProject($data) {
        return $this->createP2CProject($data,1);
	}
    //创建预告项目
	public function createProjectPre($data) {
        return $this->createP2CProject($data,2);
    }
    //修改基础
    public function updateProjectPre($id, $data) {
        return $this->updateP2CProject($id, $data,2);
    }
    //修改高级
	public function updateProject($id, $data) {
		return $this->updateP2CProject($id, $data,1);
	}
	
    public function _getP2CSaveData(&$data) {
        $_data = array();
        $_data = $this->_getP2CSaveDataPre($data);
        
        if(empty($_data)){
            return NULL;
        }
        
        $_data['agreement_template']        = $data['agreement_template'];//合同模板ID
        $_data['agreement_template_debt']   = $data['agreement_template_debt'];//债权合同模板
        $_data['score']                     = $data['score'];//项目评分
        $_data['content']                   = $data['content'];//项目描述
        $_data['use_detail']                = $data['use_detail'];//资金用途
        $_data['repayment_source']          = $data['repayment_source'];//还款来源
        $_data['risk_insurance']            = $data['risk_insurance'];//风险保障金
        $_data['guarantor_opinion']         = $data['guarantor_opinion'];//担保公司意见
        $_data['guarantor_status']          = $data['guarantor_status'];//担保公司审核状态
        $_data['mortgage_info']             = $data['mortgage_info'];//抵押物信息
        $_data['risk_control']              = $data['risk_control'];//风险控制措施
        $_data['valid_time']                = Yii::app()->params['valid_time'];//线上募资天数
        $_data['lowest_account']            = Yii::app()->params['lowest_account'];//最低投资金额
        $_data['most_account']              = $data['most_account'];//最高投标总额
        $_data['invest_step']               = Yii::app()->params['invest_step'];////投资递增金额
        $_data['complaint_information']     = $data['complaint_information'];//涉诉信息
        $_data['relevant_policies']         = $data['relevant_policies'];//项目的政府政策支持
        $_data['market_analysis']           = $data['market_analysis'];//项目的产业环境市场分析
        $_data['borrow_remark']             = $data['borrow_remark'];//内部备注信息
        $_data['delay_value_days']          = $data['delay_value_days'];//起息时间;
        $_data['rzt_contract_no']           = $data['rzt_contract_no'];//爱融租协议编号;
        
        //爱保理
        if($data['type'] == 6){
            //保理项目，保理合同签署日期不能是未来时间
            if(strtotime($data['factoring_contract_time'])>time()){
                $this->code = '0004';
                $this->message = '保理合同签署日期不能是未来时间 ！';
                return NULL;
            }
            //保理项目，应收账款提交时判断只能填大于0的数字
            if($data['factoring_accounts_receivable']<=0 || !is_numeric($data['factoring_accounts_receivable'])){
                $this->code = '0005';
                $this->message = '应收账款只能填大于0的数字 ！';
                return NULL;
            }
            
            $_data['factoring_des']                 = $data['factoring_des'];   //基础交易描述
            $_data['factoring_service_des']         = $data['factoring_service_des'];//保理业务描述
            $_data['factoring_contract_name']       = $data['factoring_contract_name'];//交易合同名称factoring_contract_name
            $_data['factoring_contract_time']       = strtotime($data['factoring_contract_time']);//保理合同日期
            $_data['factoring_contract_number']     = $data['factoring_contract_number'];//保理合同编号
            $_data['factoring_deal_number']         = $data['factoring_deal_number'];//基础交易合同编号
            $_data['factoring_original_creditor']   = $data['factoring_original_creditor'];//原债权人
            $_data['factoring_accounts_receivable'] = $data['factoring_accounts_receivable'];//应收账款
            $_data['insurance_company']             = $data['insurance_company'];//保险公司
            $_data['insurance_underwriting']        = $data['insurance_underwriting'];//保险承运
        }
        
        //爱收藏
        if($data['type'] == 7){
            $_data['collection_basics']     = $data['collection_basics'];//藏品基础信息
            $_data['collection_worth']      = $data['collection_worth'];//藏品价值
            $_data['collection_context']    = $data['collection_context'];//藏品背景描述
            $_data['collection_safekeeping']= $data['collection_safekeeping'];//藏品保管箱
        }
        
        if(in_array($data['type'],array(100,101,102,200,201,202,302,2000))){//省心计划 &零钱计划
            $_data['factoring_accounts_receivable'] = $data['factoring_accounts_receivable'];//债权总额
            $_data['project_stages']                = $data['project_stages'];//项目期数描述
            $_data['guarantors']                    = $data['guarantors'];     //担保公司
            $_data['special_welfare']               = $data['special_welfare'];//特殊福利
            
            if($data['type']!=2000){
                $_data['lowest_account']                = 100;//最低投资金额
                $_data['invest_step']                   = 100;//投资递增金额
            }else{
                $_data['lowest_account']                = 1;//最低投资金额
                $_data['invest_step']                   = 1;//投资递增金额
            }
        }

        if(isset($data['internal_audit_status'])){
            if($data['internal_audit_status'] == 2){
                $_data['internal_audit_status'] = 0;//内部人员审核状态
            }else{
                $_data['internal_audit_status'] = $data['internal_audit_status'];//内部人员审核状态
            }
        }
        
        
        // TODO 计算还款金额
        $eq = array();
        $eq['account'] = $_data['account'];
        $eq['year_apr'] = $_data['apr'];
        $eq['repayment_time'] = $_data['repayment_time'];
        $eq['type'] = "all";
        $eq['borrow_style'] = $_data['style'];  
        $eq['delay_value_days'] = $_data['delay_value_days'];
        $_equalData = $this->EqualInterest($eq);
        $equalResult = $_equalData[0];
        $_data['repayment_account'] = $equalResult['repayment_account'];
        return $_data;
    }
	
    
    public function _getP2CSaveDataPre(&$data) {
        
        $requiredParams = array('name', 'account', 'type', 'apr', 'repayment_time','style','formal_time');
        
        foreach($requiredParams as $param) {
            if($data['type']==0){
                $data['type'] = 2;
            }
            if(!isset($data[$param]) || $data[$param] === '') {
                $this->code = '0001';
                $this->message = '有必填项为空请检查 ！'.$param;
                return NULL;
            } 
        }
        if($data['priority_type'] == 2){
            if($data['appointment_money']!= 0){
                $this->code = '0100';
                $this->message = '新手项目不可设预约额度 ！';
                return NULL;
            }
        }else{
            $max_appointment_money = $data['account']*0.2;
            //最大为借贷金额的20%，最小为0
            if($data['appointment_money']>=0 && $data['appointment_money']<=$max_appointment_money){
                //预约额度校验
                if($data['appointment_money']%50000 != 0){
                    $this->code = '0101';
                    $this->message = '预约额度应为5万元的整数倍！';
                    return NULL;
                }
            }else{
                $this->code = '0102';
                $this->message = '预约额度最大不能超过借款金额的20%，最小不能低于0！';
                return NULL;
            }
        }
        //开放投资时间不能为空
        if (!isset($data['formal_time']) || $data['formal_time'] == '') {
            $this->code = '0104';
            $this->message = '开放投资时间不能为空！';
            return NULL;
        }
        //省心计划还款时间校验
        if (!isset($data['repayment_time']) || $data['repayment_time'] == '') {
            $this->code = '0103';
            $this->message = '还款时间不能为空！';
            return NULL;
        }
        $repayment_time = strtotime($data['repayment_time']);//还款时间
        $formal_time = strtotime($data['formal_time']);//开放投资时间
        //还款方式不能为空
        if (!isset($data['style']) || $data['style'] == '') {
            $this->code = '0106';
            $this->message = '还款方式不能为空！';
            return NULL;
        }
        //等额本息类型的项目还款时间校验
        if ($data['style'] == 5) {
            if (($repayment_time < $data['timeStart'] || $repayment_time > $data['timeEnd']) && $data['type'] < 100) {
                $this->code = '0108';
                $this->message = '您选择的还款日期不在建议的范围内，请修改！';
                return NULL;
            }
        } elseif ($data['style'] == 3) {//按季付息类型的项目还款时间校验
            if (($repayment_time - $formal_time)/86400 <= 100) {
                $this->code = '0107';
                $this->message = '项目期限小于3个月，不能选择按季付息，到期还本的还款方式，请更改！';
                return NULL;
            }
        }
        //省心计划还款时间校验
        if (in_array($data['type'], array(100, 200))) {
            $days = 3;
        } elseif (in_array($data['type'], array(101, 201))) {
            $days = 6;
        } elseif (in_array($data['type'], array(102, 202, 302))) {
            $days = 12;
        }
        //省心计划的还款时间限制
        if (isset($days) && $days > 0) {
            $monthsLater = strtotime("+$days months", strtotime('midnight', $formal_time ));//几个月后的时间戳
            $beforeTime = $monthsLater - 10 * 86400;//还款时间区间起始时间
            $afterTime = $monthsLater + 10 * 86400;//还款时间区间结束时间
            //还款区间校验
            if ($repayment_time < $beforeTime || $repayment_time > $afterTime) {
                $this->code = '0105';
                $this->message = '还款时间不在建议时间范围内！';
                return NULL;
            }
        }
        //担保公司类型判断
        $g_capitalrepaydays = 0;
        $g_interestrepaydays = 0;
        $guarantorsInfo = GuarantorNew::model()->findByPk($data['guarantors']);
        if($guarantorsInfo){
            $item_class_list = json_decode($guarantorsInfo->item_class,true);
            if(count($item_class_list)>0){
                $i = 0;
                foreach ($item_class_list as $key => $value) {
                    if(
                        ($key == 100 && in_array($data['type'],array(100,101,102))) ||
                        ($key == 200 && in_array($data['type'],array(200,201,202))) ||
                        ($key == 300 && in_array($data['type'],array(300,301,302))) ||
                        $key == $data['type']
                    ){
                        $data['compensate_delay_days'] = $g_capitalrepaydays = $value['capitalrepaydays'];
                        $g_interestrepaydays = $value['interestrepaydays'];
                        $i++;
                        break;
                    }
                }
                
                if( $data['guarantors'] > 0 && $i == 0 ){
                    $this->code = '0103';
                    $this->message = '请去保障机构处设置该保障机构对此类项目的代偿期！';
                    return NULL;
                }
            }
        }else{
            $this->code = '0104';
                $this->message = '保障机构信息获取有误！';
                return NULL;
        }
        
        
        //项目利率与期限限制
        if($data['type'] > 0){
            $returnRes = $this->verifyApr(NULL,$data['type'], $data['apr'],$data['repayment_time'], $data['formal_time'],$data['insurance_company'],$data['increase_apr'],$data['factoring_type']);
            if($returnRes['code'] != 1){
                $this->code     = $returnRes['code'];
                $this->message  = $returnRes['info'];
                return NULL;
            }
        }else{
            $this->code = '0002';
            $this->message = '项目类型参数有误 ！';
            return NULL;
        }
        
        // 获取系统配置参数
        $systemParamsArr = array('con_borrow_maxaccount','con_borrow_maxaccount','con_borrow_apr');
        $_criteria = new CDbCriteria;
        $_criteria->index = 'nid';
        $systemParamRecord = System::model()->findAllByAttributes(array('nid' => $systemParamsArr), $_criteria);
        
        //$maxAccount = isset($systemParamRecord['con_borrow_maxaccount']) ? intval($systemParamRecord['con_borrow_maxaccount']->value) : 20000000;
        $minAccount = isset($systemParamRecord['con_borrow_minaccount']) ? intval($systemParamRecord['con_borrow_minaccount']->value) : 100000;
        $maxApr = isset($systemParamRecord['con_borrow_apr']) ? floatval($systemParamRecord['con_borrow_apr']->value) : 24;
        unset($systemParamRecord);
        // 判断融资金额是否符合要求
        $account = intval($data['account']);
        /*
        if($account > $maxAccount){
            $this->code = '0011';
            $this->message = '借款不能高于最高额度';
            return NULL;
        }
         * */
        if($account < $minAccount){
            $this->code = '0012';
            $this->message = '借款不能低于最低限额';
            return NULL;
        }
        // 判断借款利率是否符合要求
        $_aprFloat = floatval($data['apr']);
        if(FunctionUtil::float_bigger($_aprFloat, $maxApr, 2)){
            $this->code = '0021';
            $this->message = '年化收益不能超出0~100%';
            return NULL;
        }
        $apr = substr(sprintf('%.3f', $_aprFloat), 0, -1);
        
        $_data = array();
        $_data['name']                  = trim($data['name']);//项目名称
        $_data['type']                  = intval($data['type']);//项目类型
        $_data['apr']                   = $apr;//项目利率
        $_data['status']                = 0;//项目状态
        $_data['account']               = $account;//借款金额
        $_data['repayment_time']        = strtotime($data['repayment_time']);//还款日期
        $_data['style']                 = $data['style'];//还款方式
        $_data['priority_type']         = $data['priority_type'];//项目投资权
        $_data['formal_time']           = strtotime($data['formal_time']);//开放投资时间
        $_data['return_coupon']         = $data['return_coupon'];//投资此项目是否送券
        $_data['time_limit']            = ($data['style'] == 5) ? intval($data['time_limit']) : "";
        $_data['borrow_logo']           = $data['borrow_logo'];//项目标识图
        $_data['project_city']          = $data['project_city'];   //所属办事处
        $_data['project_source']        = $data['project_source']; //项目来源
        $_data['guarantors']            = $data['guarantors'];     //担保公司
        $_data['guarantors_status']     = $data['guarantors_status'];//担保公司审核状态
        $_data['compensate_delay_days'] = $data['compensate_delay_days'];//本金赔付时间
        $_data['borrow_mode']           = $data['borrow_mode'];//小贷借款模式
        $_data['interest_style']        = $data['interest_style'];//计息方式、
		$_data['banner_src']            = $data['banner_src'];//项目宣传图
        $_data['banner_link']           = $data['banner_link'];//项目宣传图链接
        $_data['appointment_money']     = $data['appointment_money'];//预约额度
        $_data['guarantor_verify_status'] = $data['guarantor_verify_status'];//担保机构审核状态（项目拆分时复制）
        
        if($data['parent_id'] >0 ){
            $_data['parent_id'] = $data['parent_id'];//父项目ID
        }
		if (isset($data['parent_mark']) && $data['parent_mark'] != '') {
			$_data['parent_mark'] = $data['parent_mark'];//项目标识
		}
        if($data['type'] == 2000){
            $_data['is_join_reward'] = 0;//零钱计划默认不颁奖
        }
        
        if($data['type'] == 5){//爱融资
            $_data['rzt_status']    = $data['rzt_status'];//融租类型
            $_data['lease_subject'] = $data['lease_subject'];//融租标的
        }
        if($data['type'] == 6){//爱保理
            $_data['factoring_type']    = $data['factoring_type'];//保理类型
            $_data['insurance_company'] = $data['insurance_company'];//保险公司
        }
        if(in_array($data['type'],array(100,101,102,200,201,202,302,2000))){//省心计划
            //$_data['priority_type']     = 0;//项目投资权
            $_data['risk_insurance']    = $data['risk_insurance'];//风险保障金
            $_data['increase_apr']      = $data['increase_apr'];//加息收益，省心计划影视类
        }
       
        if($data['type'] == 302){//影视类
                $_data['risk_insurance']= 0;//风险保障金
        }
        if(!in_array($data['type'],array(100,101,102))){//典当项目
            $_data['user_id']   = intval($data['company_user_id']);//原债务企业名称
            $_data['is_renew']  = $data['is_renew']; //项目是否续借
            if($data['is_renew'] == 1){
                $_data['renewal_times'] = $data['renewal_times'];//续借次数
            }
        }
        $_data['infoData'] = array();
        //企业信息转移至项目
        if(!empty($_data['user_id'])){
            $companyInfo = User::model()->findByPk($_data['user_id']);//企业原有详情
            $stampInfo   = Userinfo::model()->find('user_id='.$_data['user_id']);//企业原有详情
            if(in_array($data['type'], array(2,5,7,100,101,102,200,201,202,302,2000))){//爱担保爱融租
                $_data['infoData']['borrower_name']         = isset($companyInfo)?$companyInfo->realname:''; //借款人
                $_data['infoData']['borrower_card_type']    = isset($companyInfo)?$companyInfo->card_type:'';//证件类型
                $_data['infoData']['borrower_card_id']      = isset($companyInfo)?$companyInfo->card_id:'';  //证件号码
                if($data['type'] !=5 ){
                    $_data['infoData']['borrower_stamp']        = isset($stampInfo)?$stampInfo->stamp:'';    //合同章
                    $_data['infoData']['borrower_crt']          = isset($stampInfo)?$stampInfo->crt:'';          //合同证书
                }
                if(in_array($data['type'],array(100,101,102,200,201,202,302,2000))){//省心计划
                    $_data['infoData']['borrower_desc']         = isset($stampInfo)?$stampInfo->borrower_desc:'';//借款人描述
                }
            }        
        }
        //担保公司信息转移
        if(!empty($data['guarantors'])){
            if(isset($guarantorsInfo)){
                $_data['infoData']['guarantor_name']                = $guarantorsInfo->name;
                $_data['infoData']['guarantor_business_license']    = $guarantorsInfo->business_license;
                $_data['infoData']['guarantor_stamp']               = $guarantorsInfo->stamp;
                $_data['infoData']['guarantor_crt']                 = $guarantorsInfo->crt;
                $_data['infoData']['guarantor_capitalrepaydays']    = $g_capitalrepaydays;
                $_data['infoData']['guarantor_interestrepaydays']   = $g_interestrepaydays;
                
                //爱融租项目
                if($data['type'] ==5 ){
                    $_data['infoData']['guar_business_entity_stamp']        = $guarantorsInfo->entity_stamp;        //合同章
                    $_data['infoData']['guar_business_entity_crt']          = $guarantorsInfo->entity_crt;          //合同证书
                    $_data['infoData']['guar_business_entity']              = $guarantorsInfo->business_entity;     //担保公司企业法人
                }
                
            }
        }
        return $_data;
    }

	public function createP2CProject(&$data,$version) {
		$BorrowModel = new Borrow;
		
        if($version==1){//高级
            $_data = $this->_getP2CSaveData($data);
        }else{//基础
            $_data = $this->_getP2CSaveDataPre($data);
        }
		
		if(empty($_data)) {
			return 0;
		}else{
		    $_infoData = $_data['infoData'];
            unset($_data['infoData']);
		}
		
		$responseArr = array();
        //项目名称唯一校验
         if($this->checkBorrowName($_data['name'])){
            $this->code = '1007';
            $this->message = '项目名称已存在！';
            return 0;
        }else{
		$BorrowModel->attributes = $_data;
		if($BorrowModel->save()){
		        //授信额度不得高于担保公司总授信额度的80%
                $guarantorInfo = GuarantorNew::model()->findByPk($_data['guarantors']);
                $criteria = new CDbCriteria();  
                $criteria->select    = ' sum(account) account ';  
                $criteria->condition = ' guarantors='.$_data['guarantors'].' and status in (101,100,1,3,5)';  
                $borrowInfo    = Borrow::model()->find($criteria);
                $creditedT     = ($guarantorInfo->credited)*10000*0.8;//可担保总金额
                $creditedR     = (count($borrowInfo) == 0) ? 0 : $borrowInfo->account;//已担保总金额
                if(($creditedR+$_data['account']) >= $creditedT){
                    //发邮件给Credit_Supervisor
                    $MailClass = new MailClass();
                    $roles = ItzAuthAssignment::model()->findAllByAttributes(array('itemname'=>'Credit_Supervisor'));
                    $msg = array();
                    $msg['title'] = $guarantorInfo->name.'增信通知';
                    $msg['content'] = 'Hi <hr/>'.$guarantorInfo->name.'的在保项目额度已达到'.($creditedR+$_data['account']).'元（合作机构所有在保项目的借款金额之和），总授信额度为'.$guarantorInfo->credited.'万元，请尽快增信，谢谢!';
                    foreach ($roles as $key => $value) {
                        $msg['email'] = $this->getEmailByUid($value->userid);
                       // $MailClass->send($msg['email'],'system',$msg['title'], $msg['content'], array(), 1, false);
                        $MailClass->sendToUserInternal($value->userid,$msg['email'], $msg['title'], $msg['content'],"","",true);
                    }
                }
		        if(!in_array($_data['type'], array(100,101,102,200,201,202,302,2000))){
		            $this->delArticle($BorrowModel->id);
                    $_data1 = array();
                    $_data1['type_id']       = 14;
                    $_data1['article_id']    = $BorrowModel->id;
                    $_data1['borrow_type']   = $BorrowModel->type;
                    $_data1['litpic']        = $BorrowModel->borrow_logo;
                    $_data1['thumb_url']     = $BorrowModel->borrow_logo;
                    $_data1['content']       = '项目题图';
                    $_data1['is_visible']    = 2;//默认显示
                    
                    $BorrowuploadModel = new Borrowupload;
                    $BorrowuploadModel->attributes = $_data1;
                    if($BorrowuploadModel->save()){
                            $returnInfo             = array();
                            $this->code             = '1000';
                            $this->message          = '创建项目成功';
                            $returnInfo['id']       = $BorrowModel->id;
                            $returnInfo['infoData'] = $_infoData;
                            return $returnInfo;
                    }else{
                        $this->code = '1003';
                        $this->message = '此项目题图上传失败';
                        return 0;
                    }
		        }else{
                        $returnInfo             = array();
                        $this->code             = '1000';
                        $this->message          = ($_data['type']==2000)?'创建零钱计划项目成功':'创建省心计划项目成功';
                        $returnInfo['id']       = $BorrowModel->id;
                        $returnInfo['infoData'] = $_infoData;
                        return $returnInfo;
		        }
		} else {
			$this->code = '1001';
			$this->message = '创建项目失败';
			return 0;
		}
        }
		
		return 0;
	}
    //修改项目
	public function updateP2CProject($id, &$data,$version) {
	
		if(empty($id)) {
			$this->code = '0001';
			$this->message = '项目id是空的';
			return false;
		}
		$BorrowModel = new Borrow;
		// 查询项目是否存在
		$projectRecord = $BorrowModel->findByPk($id);
	    
		if(empty($projectRecord)) {
			$this->code = '0002';
			$this->message = '项目ID:'.$id.'不存在';
			return false;
		}
        
        //项目是“预告中”状态时，预约额度字段，不可修改
        if($projectRecord->status == 101){
            if($projectRecord->appointment_money != $data['appointment_money']){
                $this->code = '0103';
                $this->message = '项目是“预告中”状态时，预约额度字段，不可修改！';
                return false;
            }
        }
        //当项目融资金额发生变化时，父项目不允许修改，子项目变动父项目关联修改
        if($projectRecord->account != $data['account']){
            if($projectRecord->parent_mark==1){
                $this->code = '0002';
                $this->message = '此项目为父项目，不允许修改融资金额！';
                return false;
            }
            
            //父项目信息
            $parent_id = $projectRecord->parent_id;
            if($parent_id >0){
                $parentInfo = Borrow::model()->findByPk($parent_id);
                if(count($parentInfo)==0){
                    $this->code = '0003';
                    $this->message = '此项目父项目信息有误！';
                    return false;
                }
                
                if(($parentInfo->account+$projectRecord->account) < $data['account']){
                    $this->code = '0004';
                    $this->message = '子项目融资金额不允许大于父项目剩余金额！';
                    return false;
                }
                
                //父项目融资金额变更
                $borrowSplitEditData= array();
                $borrowSplitEditData['id']      = $parent_id;
                if($data['account']>$projectRecord->account){
                    $changeAmount = $data['account']-$projectRecord->account;
                    $risk_insurance = ($changeAmount/$parentInfo->account)*$parentInfo->risk_insurance;
                    $borrowSplitEditData['account'] = ($parentInfo->account)-$changeAmount;
                    $borrowSplitEditData['risk_insurance'] = ($parentInfo->risk_insurance)-$risk_insurance;
                    $data['risk_insurance'] = $projectRecord->risk_insurance+$risk_insurance;
                }
                if($data['account']<$projectRecord->account){
                    $changeAmount = $projectRecord->account-$data['account'];
                    $risk_insurance = ($changeAmount/$parentInfo->account)*$parentInfo->risk_insurance;
                    $borrowSplitEditData['account'] = ($parentInfo->account)+$changeAmount;
                    $borrowSplitEditData['risk_insurance'] = ($parentInfo->risk_insurance)+$risk_insurance;
                    $data['risk_insurance'] = $projectRecord->risk_insurance-$risk_insurance;
                }
                
                //修改父项目信息
                $parentBorrowInfo = BaseCrudService::getInstance()->update('Borrow', $borrowSplitEditData, 'id');
                if($parentBorrowInfo == false){
                    $this->code = '0005';
                    $this->message = '父项目信息修改失败！';
                    return false;
                }
            }
            
            
        }
        
        //如果内部审核状态为不通过
        $internal_audit_status = $projectRecord->internal_audit_status;
        if($internal_audit_status == 2){
            $data['internal_audit_status'] = 2;
        }
        if($version==1){//高级
            $_data = $this->_getP2CSaveData($data);
        }else{//基础
            $_data = $this->_getP2CSaveDataPre($data);
        }
		if(empty($_data)) {
			return false;
		}else{
		    $_infoData = true;
            if($_data['status'] == 0){//只有待预告状态可编辑
                $_infoData = $_data['infoData'];
            }
		    unset($_data['infoData']);
		}
	
		$responseArr = array();
		//预告中项目置为待预发布
		$status = $projectRecord->status;
        $_data['status'] = $status;
        //项目名称唯一校验
        if($this->checkBorrowName($_data['name'],$id)){
            $this->code = '1007';
            $this->message = '更新失败！项目名称已存在！';
            return false;
        }else{
		$projectRecord->attributes = $_data;
		if($projectRecord->save()) {
		        if(!in_array($_data['type'], array(100,101,102,200,201,202,302,2000))){//是否省心计划项目，省心计划不需要LOGO
		            //是否已存在类型14的题图LOGO
		            $logo = Borrowupload::model()->findByAttributes(array('article_id'=>$projectRecord->id,'type_id'=>14));
                    if($logo){
                        if($logo->thumb_url != $projectRecord->borrow_logo){//已存在并且更新了的话
                            $editLogo = Borrowupload::model()->updateByPk($logo->id,array('thumb_url'=>$projectRecord->borrow_logo,'litpic'=>$projectRecord->borrow_logo));
                            if(!$editLogo){
                                $this->code = '1001';
                                $this->message = '更新项目题图信息失败';
                                return false;
                            }else{
                                $this->code = '1000';
                                $this->message = '更新项目信息成功';
                                    return $_infoData;
                            }
                        }else{
                            $this->code = '1000';
                            $this->message = '更新项目信息成功';
                                return $_infoData;
                        }
                    }else{//新增LOGO
                        $_data1 = array();
                        $_data1['type_id']       = 14;
                        $_data1['article_id']    = $projectRecord->id;
                        $_data1['borrow_type']   = $projectRecord->type;
                        $_data1['litpic']        = $projectRecord->borrow_logo;
                        $_data1['thumb_url']     = $projectRecord->borrow_logo;
                        $_data1['content']       = '项目题图';
                        $_data1['is_visible']    = 2;//默认显示
                        $BorrowuploadModel = new Borrowupload();
                        $BorrowuploadModel->attributes = $_data1;
                        if(!$BorrowuploadModel->save()){
                            $this->code = '1001';
                            $this->message = '更新项目题图信息失败';
                            return false;
                        }else{
                            $this->code = '1000';
                            $this->message = '更新项目信息成功';
                                return $_infoData;
                            }
                        }
                    }else{
                        $this->code = '1000';
                        $this->message = '更新项目信息成功';
                        return $_infoData;
                    }
            } else {
                $this->code = '1001';
                $this->message = '更新项目信息失败';
                return false;
            }
        }
		
		return false;
	}  
	
	/**
	 * 计算付本还息数据
	 *
	 * @param Array $data: repayment_time, borrow_time, year_apr, account, delay_value_days
	 * @return Array
	 */
	public function equalInterest($data){
	
		if (isset($data['borrow_style']) && $data['borrow_style']!=""){
			$borrow_style = $data['borrow_style'];
		}
	
		if ($borrow_style==0){
			return $this->equalNextMonthByDay($data);
		}elseif ($borrow_style==1){
			$data['type'] = 'all';
			return $this->equalNextMonthByDay($data);
		}elseif ($borrow_style==2){
			return $this->equalEndMonthByDay($data);
		}
	
	}
	
	// 用于计算29、30、31投资的特殊情况
	public function dateNextMonth($now, $date = 0) {
		$mdate = array(0, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
		list($y, $m, $d) = explode('-', (is_int($now) ? strftime('%Y-%m-%d', $now) : $now));
	
		if ($date)
			$d = $date;
		if (++$m == 13){
			$m = 1;
			++$y;
		}
		if ($m == 2)
			$d = (($y % 4) === 0) ? (($d <= 29) ? $d : 29) : (($d <= 28) ? $d : 28);
		else
			$d = ($d <= $mdate[$m]) ? $d : $mdate[$m];
	
		return mktime(0, 0, 0, $m, $d, $y);
	}
	
	/**
	 * 计息核心函数
	 *
	 * @param Array $data: repayment_time, borrow_time, year_apr, account, delay_value_days
	 * @return Array
	 */
	//到期还本，按月付息，按日计息
	//到期还本付息，按日计息(type=all)
	public function equalNextMonthByDay($data){
	
		//到期日
		if (isset($data['repayment_time']) && $data['repayment_time']>0){
			$repayment_time = strtotime("midnight", $data['repayment_time']);
		}else{
			return "";
		}
	
		//借款的总金额
		if (isset($data['account']) && is_numeric($data['account']) && $data['account']>0){
			$account = $data['account'];
		}else{
			return "";
		}
	
		//借款的年利率
		if (isset($data['year_apr']) && is_numeric($data['year_apr']) && $data['year_apr']>0){
			$year_apr = $data['year_apr'];
		}else{
			return "";
		}
	
		$delay_value_days =  Yii::app()->params['delay_value_days'];//起息时间;
		//借款的时间
		if (isset($data['borrow_time']) && $data['borrow_time']>0){
			$borrow_time = strtotime("midnight", $data['borrow_time']) + $delay_value_days*24*60*60;
		}else{
			$borrow_time = strtotime("midnight", time()) + $delay_value_days*24*60*60;
		}
		//借款日
		$borrow_day = date("d", $borrow_time);
		//借款时间必须在还款时间之前
		if ($borrow_time > $repayment_time){
			return "";
		}
	
		//日利率
		$daily_apr = $year_apr/(365*100);
	
		//总利息=投资额*日息*投资天数
		$invest_days = round(($repayment_time-$borrow_time)/(24*60*60));
		$total_interest = floor(100 * $account * $daily_apr * $invest_days)/100;
	
		//对于到期还本付息，按日计息的情况
		if (isset($data['type']) && $data['type']=="all"){
			$_result[0]['repayment_account'] = $account + $total_interest;
			$_result[0]['repayment_time'] = $data['repayment_time'];
			$_result[0]['interest'] = $total_interest;
			$_result[0]['capital'] = $account;
			$_result[0]['days'] = $invest_days;
			return $_result;
		}
		$i = 0;
		while($this->dateNextMonth($borrow_time, $borrow_day) < $repayment_time){
			$borrow_time_next_month = $this->dateNextMonth($borrow_time, $borrow_day);
			$interest = round($account * $daily_apr * round(($borrow_time_next_month - $borrow_time)/(24*60*60)), 2);
			$_result[$i]['repayment_account'] = $interest;
			$_result[$i]['repayment_time'] = $borrow_time_next_month;
			$_result[$i]['interest'] = $interest;
			$_result[$i]['capital'] = 0;
			$_result[$i]['days'] = round(($borrow_time_next_month - $borrow_time)/(24*60*60));
			$borrow_time = $borrow_time_next_month;
			$i++;
			$total_interest = round($total_interest - $interest, 2);
		}
		$_result[$i]['repayment_account'] = $account + $total_interest;
		$_result[$i]['repayment_time'] = $repayment_time;
		$_result[$i]['interest'] = $total_interest;
		$_result[$i]['capital'] = $account;
		$_result[$i]['days'] = round(($repayment_time - $borrow_time)/(24*60*60));
	
		return $_result;
	}
	
	
	/**
	 * 计息核心函数
	 *
	 * @param Array $data: repayment_time, borrow_time, year_apr, account, delay_value_days
	 * @return Array
	 */
	//到期还本，月底付息，按日计息
	function equalEndMonthByDay ($data) {
		//到期日
		if (isset($data['repayment_time']) && $data['repayment_time']>0){
			$repayment_time = strtotime("midnight", $data['repayment_time']);
		}else{
			return "";
		}
	
		//借款的总金额
		if (isset($data['account']) && is_numeric($data['account']) && $data['account']>0){
			$account = $data['account'];
		}else{
			return "";
		}
	
		//借款的年利率
		if (isset($data['year_apr']) && is_numeric($data['year_apr']) && $data['year_apr']>0){
			$year_apr = $data['year_apr'];
		}else{
			return "";
		}
	
		$delay_value_days =  Yii::app()->params['delay_value_days'];//起息时间;
		//借款的时间
		if (isset($data['borrow_time']) && $data['borrow_time']>0){
			$borrow_time = strtotime("midnight", $data['borrow_time']) + $delay_value_days*24*60*60;
		}else{
			$borrow_time = strtotime("midnight", time()) + $delay_value_days*24*60*60;
		}
		//借款时间必须在还款时间之前
		if ($borrow_time > $repayment_time){
			return "";
		}
		//日利率
		$daily_apr = $year_apr/(365*100);
	
		//总利息=投资额*日息*投资天数
		$invest_days = round(($repayment_time-$borrow_time)/(24*60*60));
		$total_interest = floor(100 * $account * $daily_apr * $invest_days)/100;
	
		//对于到期还本付息，按日计息的情况
		if (isset($data['type']) && $data['type']=="all"){
			$_result['repayment_account'] = $account + $total_interest;
			$_result['repayment_time'] = $data['repayment_time'];
			$_result['interest'] = $total_interest;
			$_result['capital'] = $account;
			$_result['days'] = $invest_days;
			return $_result;
		}
	
		$i = 0;
		$tmp_time = $borrow_time;
		while(strtotime("-1 day", strtotime("first day of next month", $tmp_time)) < $repayment_time){
			$borrow_time_next_month = strtotime("-1 day", strtotime("first day of next month", $tmp_time));
			$interest = round($account * $daily_apr * round(($borrow_time_next_month - $borrow_time)/(24*60*60)), 2);
			if ( $interest > 0 ){
				$_result[$i]['repayment_account'] = $interest;
				$_result[$i]['repayment_time'] = $borrow_time_next_month;
				$_result[$i]['interest'] = $interest;
				$_result[$i]['capital'] = 0;
				$_result[$i]['days'] = round(($borrow_time_next_month - $borrow_time)/(24*60*60));
				$i++;
			}
			$borrow_time = $borrow_time_next_month;
			$tmp_time = strtotime("+1 day", $borrow_time_next_month);
			$total_interest = round($total_interest - $interest, 2);
		}
		$_result[$i]['repayment_account'] = $account + $total_interest;
		$_result[$i]['repayment_time'] = $repayment_time;
		$_result[$i]['interest'] = $total_interest;
		$_result[$i]['capital'] = $account;
		$_result[$i]['days'] = round(($repayment_time - $borrow_time)/(24*60*60));
	
		return $_result;
	}

    private function delArticle($borrow_id){
        if(Borrowupload::model()->findAll('type_id=14 and article_id='.$borrow_id)){
                        Borrowupload::model()->deleteAll('`type_id` = 14 and article_id='.$borrow_id);
         }
    }
    //校验项目名称唯一
    private function checkBorrowName($name,$id=NULL){
        $c = ' name = "'.$name.'"';
        if(isset($id)){
            $c .= ' and id !='.$id;
        }
        $checkRes = Borrow::model()->find($c);
        if(count($checkRes)>0){
            return true;
        }else{
            return false;
        }
        
    }
    // 获取系统用户的email
    private function getEmailByUid($uid){
        $res = ItzUser::model()->findByPk($uid);
        if($res){
           return $res->email;
        }
     }
    
    //验证项目期限利率是否匹配
    public function verifyApr($borrow_id=NULL,$type=NULL,$apr=NULL,$repayment_time=NULL,$formal_time=NULL,$insurance_company=0,$increase_apr=NULL,$factoring_type=1){
            $returnResult = array(
                   'code'=>'3000','info'=>'项目利率与期限不符!','data'=>'',
            );
            
            //发预告
            if(isset($borrow_id)){
                $borrowInfo = Borrow::model()->findByPk($borrow_id);
                if(count($borrowInfo)>0){
                    $type               = $borrowInfo->type;
                    $apr                = $borrowInfo->apr;
                    $repayment_time     = $borrowInfo->repayment_time;
                    $formal_time        = $borrowInfo->formal_time;
                    $insurance_company  = $borrowInfo->insurance_company;
                    $increase_apr       = $borrowInfo->increase_apr;
                    $factoring_type     = $borrowInfo->factoring_type;
                    $time_limit = ($repayment_time-$formal_time)/86400;
                }else{
                    $returnResult['code'] = '3005';
                    $returnResult['info'] = '此项目不存在！';
                    return $returnResult;
                }
            }else{
                //项目期限
                $time_limit = (strtotime($repayment_time)-strtotime(date("Y-m-d 0:0:0",strtotime($formal_time))))/86400;
            }
            
            //项目所属分类
            $borrow_type_name = Yii::app()->params['borrow_type_online_usertrade'][$type];
            
            //爱保理
            if($type == 6){
                    if($time_limit>0 && $time_limit<=260){
                            if($insurance_company == 0 && $factoring_type != 3){//明保理暗保理类型项目无保险公司的情况下
                                    //4个月以下项目，0天 < 项目期限 <= 140天，利率 >= 10%
                                    if($time_limit>0 && $time_limit<=140){
                                        if($apr<10){
                                            $returnResult['code'] = '3001';
											$returnResult['info'] = $borrow_type_name.'无保险项目且非反向保理项目，项目期限在0至140天之间，不包括0包括140，年化收益必须大于等于10%';
                                            return $returnResult;
                                        }
                                    }
                                    //4个月以上9个月以下项目，140天 < 项目期限 <= 260天，利率 >= 11%
                                    if($time_limit>140 && $time_limit<=260){
                                        if($apr<11){
                                            $returnResult['code'] = '3002';
											$returnResult['info'] = $borrow_type_name.'无保险项目且非反向保理项目，项目期限在140至260天之间，不包括140包括260，年化收益必须大于等于11%';
                                            return $returnResult;
                                        }
                                    }
                }elseif($insurance_company >0 || $factoring_type==3){//有保险公司 或者反向保理 
                                    //4个月以下项目，0天 < 项目期限 <= 140天，利率 >= 9%
                                    if($time_limit>0 && $time_limit<=140){
                                        if($apr<9){
                                            $returnResult['code'] = '3003';
											$returnResult['info'] = $borrow_type_name.'有保险项目或反向保理项目，项目期限在0至140天之间，不包括0包括140，年化收益必须大于等于9%';
                                            return $returnResult;
                                        }
                                    }
                                    //4个月以上9个月以下项目，140天 < 项目期限 <= 260天，利率 >= 10%
                                    if($time_limit>140 && $time_limit<=260){
                                        if($apr<10){
                                            $returnResult['code'] = '3004';
											$returnResult['info'] = $borrow_type_name.'有保险项目或反向保理项目，项目期限在140至260天之间，不包括140包括260，年化收益必须大于等于10%';
                                            return $returnResult;
                                        }
                                    }
                            }
                    }else{
                        $returnResult['code'] = '4003';
                        $returnResult['info'] = $borrow_type_name.'项目，项目期限必须在0至260天之间，不包括0包括260';
                        return $returnResult;
                    }
            }

            //爱担保
            if($type == 2){
                    if($time_limit>0 && $time_limit<=380){
                            //4个月以下项目，0天 < 项目期限 <= 140天，利率 >= 11%
                            if($time_limit>0 && $time_limit<=140){
                                    if($apr<11){
                                        $returnResult['code'] = '3006';
                                        $returnResult['info'] = $borrow_type_name.'项目，项目期限在0至140天之间，不包括0包括140，年化收益必须大于等于11%';
                                        return $returnResult;
                                    }
                             }
                            
                            //4个月以上9个月以下项目，140天 < 项目期限 <= 260天，利率 >= 12%
                            if($time_limit>140 && $time_limit<=260){
                                    if($apr<12){
                                        $returnResult['code'] = '3007';
                                        $returnResult['info'] = $borrow_type_name.'项目，项目期限在140至260天之间，不包括140包括260，年化收益必须大于等于12%';
                                        return $returnResult;
                                    }
                             }
                            
                            //9个月项目，260天 < 项目期限 <= 290天，利率 >= 13%
                            if($time_limit>260 && $time_limit<=290){
                                    if($apr<13){
                                        $returnResult['code'] = '3008';
                                        $returnResult['info'] = $borrow_type_name.'项目，项目期限在260至290天之间，不包括260包括290，年化收益必须大于等于13%';
                                        return $returnResult;
                                    }
                             }
                            
                             //9个月以上12个月项目，290天 < 项目期限 <= 380天，利率 >= 14%
                             if($time_limit>290 && $time_limit<=380){
                                    if($apr<14){
                                        $returnResult['code'] = '3009';
                                        $returnResult['info'] = $borrow_type_name.'项目，项目期限在290至380天之间，不包括290包括380，年化收益必须大于等于14%';
                                        return $returnResult;
                                    }
                             }
                    }else{
                        $returnResult['code'] = '4001';
                        $returnResult['info'] = $borrow_type_name.'项目，项目期限必须在0至380天之间，不包括0包括380';
                        return $returnResult;
                    }                
            }

            //爱融租
            if($type == 5){
                    if($time_limit>0 && $time_limit<=750){
                            //9个月以下项目，0天 < 项目期限 <= 290天，利率 >= 10%
                            if($time_limit>0 && $time_limit<=290){
                                    if($apr<10){
                                        $returnResult['code'] = '3010';
                                        $returnResult['info'] = $borrow_type_name.'项目，项目期限在0至290天之间，不包括0包括290，年化收益必须大于等于10%';
                                        return $returnResult;
                                    }
                             }
                             
                            //9个月以上18个月以下项目，290天 < 项目期限 <= 500天，利率 >= 12%
                            if($time_limit>290 && $time_limit<=500){
                                    if($apr<12){
                                        $returnResult['code'] = '3011';
                                        $returnResult['info'] = $borrow_type_name.'项目，项目期限在290至500天之间，不包括290包括500，年化收益必须大于等于12%';
                                        return $returnResult;
                                    }
                             }
                            
                             //18个月以上24个月以下项目，500天 < 项目期限 <= 750天，利率 >= 13%
                             if($time_limit>500 && $time_limit<=750){
                                    if($apr<13){
                                        $returnResult['code'] = '3012';
                                        $returnResult['info'] = $borrow_type_name.'项目，项目期限在500至750天之间，不包括500包括750，年化收益必须大于等于13%';
                                        return $returnResult;
                                    }
                              }
                    }else{
                        $returnResult['code'] = '4002';
                        $returnResult['info'] = $borrow_type_name.'项目，项目期限必须在0至750天之间，不包括0包括750';
                        return $returnResult;
                    }
                
            }
                      
            //省心计划A套餐项目
            if(in_array($type, array(100, 200, 300))){
                //3个月项目，80天 <= 项目期限 <= 100天，利率 = 8% +1%
                if($time_limit>=80 && $time_limit<=100){
                        if($apr != 8){
                            $returnResult['code'] = '3013';
                            $returnResult['info'] = $borrow_type_name.'项目，项目期限在80至100天之间，包括80包括100，年化收益必须等于8%';
                            return $returnResult;
                        }
                }else{
                    $returnResult['code'] = '4005';
                    $returnResult['info'] = $borrow_type_name.'项目，项目期限必须在80至100天之间，包括80包括100';
                    return $returnResult;
                }
            }
            
            //省心计划B套餐项目
            if(in_array($type, array(101, 201, 301))){
                //6个月项目，170天 <= 项目期限 <= 200天，利率 = 10% +1%
                if($time_limit>=170 && $time_limit<=200){
                        if($apr != 10){
                            $returnResult['code'] = '3014';
                            $returnResult['info'] = $borrow_type_name.'项目，项目期限在170至200天之间，包括170包括200，年化收益必须等于10%';
                            return $returnResult;
                        }
                }else{
                    $returnResult['code'] = '4006';
                    $returnResult['info'] = $borrow_type_name.'项目，项目期限必须在170至200天之间，包括170包括200';
                    return $returnResult;
                }
            }
            
            //省心计划C套餐项目
            if(in_array($type,array(102,202,302))){
                //12个月项目，350天 <= 项目期限 <= 380天，利率 = 12% +1%
                if($time_limit>=350 && $time_limit<=380){
                        if($apr != 12){
                            $returnResult['code'] = '3015';
                            $returnResult['info'] = $borrow_type_name.'项目，项目期限在350至380天之间，包括350包括380，年化收益必须等于12%';
                            return $returnResult;
                        }
                }else{
                    $returnResult['code'] = '4007';
                    $returnResult['info'] = $borrow_type_name.'项目，项目期限必须在350至380天之间，包括350包括380';
                    return $returnResult;
                }
            }
            
            //爱收藏项目
            if($type == 7){
                    if($time_limit<=0 || $time_limit>380){
                        $returnResult['code'] = '4004';
                        $returnResult['info'] = $borrow_type_name.'项目，项目期限必须在0至380天之间，不包括0包括380';
                        return $returnResult;
                    }
            }
            
            //零钱计划项目
            if($type==2000){
                
                // if(!in_array($time_limit, array(20,21,22))){
                    // $returnResult['code'] = '3017';
                    // $returnResult['info'] = '零钱计划项目，还款日期为20-22天。';
                    // return $returnResult;
                // }else{
                    // //还款日期节假日顺延
                    // $checkDateModel = new withdrawDay;
                    // $checkRes = $checkDateModel->getRepaymentTime($repayment_time);
                    // if($checkRes != 1){
                        // $returnResult['code'] = '3016';
                        // $returnResult['data'] = $checkRes['data'];
                        // $returnResult['info'] = '零钱计划项目，还款日期遇节假日顺延，建议还款日期为'.$checkRes['data'];
                        // return $returnResult;
                    // }
                // }
                //                 
                
                //年化收益必须等于8%。
                if($apr!=8.00){
                        $returnResult['code'] = '3018';
                        $returnResult['info'] = '零钱计划项目，年化收益必须等于8%。';
                        return $returnResult;
                }
            }
            $returnResult['code'] = 1;
            $returnResult['info'] = '验证成功!';
            return $returnResult;
    }
}