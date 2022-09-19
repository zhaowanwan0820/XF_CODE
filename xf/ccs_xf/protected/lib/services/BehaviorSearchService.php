<?php
/**
 * @file BehaviorSearch.php
 * @date 2017/07/25
 * 用户行为轨迹
 **/
class BehaviorSearchService extends ItzInstanceService {
    
    public function __construct()
    {
    	parent::__construct();
    }
    
    /**
     * 依据搜索条件获取列表
     * @param array $data
     * 所涉及查询方法依照elastic官方文档为准
     */
    public function getBehaviorList($data=array(),$page=1,$limit=10){

    	Yii::log ( __FUNCTION__.print_r(func_get_args(),true),'info');
    	$returnResult = array(
    			'code' => '1', 'info' => 'error', 'data' => array('listTotal'=>0,'listInfo'=>array())
    	);
    	
    	if (count($data) > 0) {
    	
    		//AuditLog没有提供手机号,转为user_id搜索
    		if (isset($data['phone']) && $data['phone'] != '') {
    			$user_id = $this->getUserIdByPhone($data['phone']);
    			$query[]['match']['user_id'] = $user_id;
    			$query[]['match']['parameters.user_id'] = $user_id;
    			$query[]['match']['parameters.send.reqData.platformUserNo']= $user_id;
    			$query[]['match']['parameters.send.respData.platformUserNo']= $user_id;
    			$params['query']['bool']['should'] = $query;	//使用match方式匹配
    		}
    		
    		//user_id搜索
    		if (isset($data['user_id']) && $data['user_id'] != '') {
    			$query[]['match']['user_id'] = $data['user_id'];
    			$query[]['match']['parameters.user_id'] = $data['user_id'];
    			$query[]['match']['parameters.send.reqData.platformUserNo']= $data['user_id'];
    			$query[]['match']['parameters.send.respData.platformUserNo']= $data['user_id'];
    			$params['query']['bool']['should'] = $query;	//使用should方式匹配
    			
    		}
    		
    		if (isset($data['client_ip']) && $data['client_ip'] != '') {
    			$ip['client_ip'] = $data['client_ip'];
    			$params['query']['bool']['must'][]['match'] = $ip;	//使用match方式匹配
    		}
    		
    		//时间范围搜索
    		if( (isset($data['begin_addtime']) && $data['begin_addtime']!='') && (isset($data['end_addtime']) && $data['end_addtime']!='') ){
    			$rangetime['timestamp']['gte'] = intval($data['begin_addtime'])*1000;
    			$rangetime['timestamp']['lte'] = intval($data['end_addtime'])*1000;
    			$params['query']['bool']['must'][]['range'] = $rangetime;
    		}
    		
    		//分页条数设置
    		$limit = in_array($data['limit'], array(10, 20, 30, 40, 50)) ? intval($data['limit']) : $limit;
    		//请求页数
    		$page = (isset($data['page']) && $data['page'] != '') ? intval($data['page']) : $page;
    	}
    	
    	$params['from'] = ($page-1)*$limit;			//处理element-vue为es可用
    	$params['size'] =  $limit;
    	$params['sort']['@timestamp'] = 'desc';    	//依据时间倒叙排序
    	$must_not[]['match_phrase']['user_id']='0';	//过滤user_id为0的数据
    	$must_not[]['match_phrase']['resource']='index/log';	//对客服无用
    	$must_not[]['match_phrase']['resource']='calculationTender';	//对客服无用
    	$must_not[]['match_phrase']['resource']='user/ajax/getDebtinfos';	//对客服无用
    	$must_not[]['match_phrase']['resource']='CANCEL_DEBENTURE_SALE'; //未记录用户ID 暂无法判断
    	$must_not[]['match_phrase']['resource']='CHECK_PASSWORD';
    	$must_not[]['match_phrase']['resource']='SYNC_TRANSACTION';	//认购债权
    	$must_not[]['match_phrase']['resource']='ASYNC_TRANSACTION';
    	$must_not[]['match_phrase']['resource']='QUERY_TRANSACTION';	//暂时屏蔽需要对应处理
    	$must_not[]['match_phrase']['resource']='CANCEL_PRE_TRANSACTION'; //取消预处理中流水号为唯一标识,未记录用户ID
    	//$must_not[]['match_phrase']['resource']='apiService/phone/getSmsVcode';
    	$must_not_transaction['must'][]['match_phrase']['action'] = 'verifySignatur'; 			
    	$must_not_transaction['must'][]['match_phrase']['resource'] = 'USER_PRE_TRANSACTION';
    	$must_not[]['bool'] =$must_not_transaction;	//过滤action为verifySignatur的USER_PRE_TRANSACTION数据
    	$must_not_debt['must'][]['exists']['field'] = 'parameters.REQUEST_URI';
    	$must_not_debt['must'][]['match_phrase']['resource'] = 'user/money_account/debt';
    	$must_not[]['bool'] =$must_not_debt;	//过滤没有debt_id的债权出让数据 
    	$params['query']['bool']['must_not']=$must_not;
		if(!$params['query']['bool']){				//设置默认值 一周内的日志
			$rangetime['timestamp']['gte'] =strtotime("-1 week")*1000;
			$rangetime['timestamp']['lte'] =time()*1000;
			$params['query']['bool']['must'][]['range'] = $rangetime;
		}
		
		//182.150.13.113;6493489;2017-09-17 21:45:40;
		//var_dump(json_encode($params));die;
    	$url = Yii::app()->c->elasticUrl.'/audit*/_search';
    	$esresult = json_decode(CcsCurlService::getInstance()->post($url,json_encode($params)),true);
    	
    	//结果转化
    	$info = $esresult['hits']['hits'];
    	$listInfo = array();
    	foreach ($info as $k => $v){
    		$listInfo[$k]['user_id']= $this->getUserIdInfo($v['_source']);
    		$listInfo[$k]['status']= $v['_source']['status'] == 'success'?'成功':'失败';
    		$listInfo[$k]['parameters']= $v['_source']['parameters'];
    		$listInfo[$k]['timestamp_format']= $v['_source']['timestamp_format'];
    		$listInfo[$k]['system']= Yii::app()->c->esAuditConfig['system'][$v['_source']['system']];
    		$listInfo[$k]['resource']= Yii::app()->c->esAuditConfig['resource'][$v['_source']['resource']];
    		$listInfo[$k]['action']= Yii::app()->c->esAuditConfig['action'][$v['_source']['action']];
    		$listInfo[$k]['app_version_tips']= $v['_source']['parameters']['app_version'] ? $v['_source']['parameters']['app_version'] : '--';
    		$listInfo[$k]['ip']= $v['_source']['client_ip'];
    		$responseType = $v['_source']['parameters']['send']['responseType'];
    		$responseType_tips = $responseType ? Yii::app()->c->esAuditConfig['responseType'][$responseType] : '';
    		$listInfo[$k]['api_tips']= $listInfo[$k]['action'].$responseType_tips;
    		switch($v['_source']['resource']){
    			case 'user/reg':	//注册
    				$listInfo[$k]['desc'] = $listInfo[$k]['resource'].$listInfo[$k]['status'];	//事件描述
    				break;    			
    			case 'user/login':	//登录
    				$listInfo[$k]['desc'] = $listInfo[$k]['resource'].$listInfo[$k]['status'];
    				break;
    			case 'user/logout':	//登出
    				$listInfo[$k]['desc'] = $listInfo[$k]['resource'].$listInfo[$k]['status'];
    				break;
    			case 'user/bank_account/card':	//银行卡
    				$listInfo[$k]['desc'] = "卡号:".$v['_source']['parameters']['account']."|银行:".$v['_source']['parameters']['branch'];
    				break;
    			case 'user/bank_account/card_safe':	//快捷卡
    				$listInfo[$k]['desc'] = "银行:".$v['_source']['parameters']['branch'];
    				break;
    			case 'user/app_recharge/step1': //快充第一步
    				$money = $v['_source']['parameters']['money'] ?  $v['_source']['parameters']['money']:$v['_source']['parameters']['amount'];
    				$listInfo[$k]['desc'] = "快捷充值".$money."元";
    				break;
    			case 'user/app_recharge/step2': //快充第二步
    				$listInfo[$k]['desc'] = $listInfo[$k]['resource'];
    				break;
    			case 'user/app_recharge/step3': //快充第三步
    				$listInfo[$k]['desc'] = $listInfo[$k]['resource'];
    				break;
    			case 'user/money_account/recharge/getResult':	//充值
    				$listInfo[$k]['desc'] = "充值订单号".$v['_source']['parameters']['trade_no'];
    				break;
    			case 'user/money_account/invest':	//投资6712497
    				
    				$investInfo = $this->getInvestInfo($v['_source']['parameters']);
    				$investInfo_tips = "[投资项目:".$investInfo['name']."] ";
    				
    				$couponInfo = $this->getCouponInfo($v['_source']['parameters']);
    				
    				$amount = $v['_source']['parameters']['money'];
    				$amount_tips = $amount==null ? "[金额未知] " : "[金额:".$amount."元] ";
    				$listInfo[$k]['desc'] = $investInfo_tips.$amount_tips.$couponInfo;
    				break;
    			case 'user/money_account/debt': //债权	invest认购	create转让	cancel取消转让
    				$debtInvestInfo = $this->getDebtInvestInfo($v['_source']['parameters']);
    				$debtCreateInfo = $this->getDebtCreateInfo($v['_source']['parameters']);
    				if($v['_source']['action'] == 'invest'){
    					$listInfo[$k]['desc'] = "认购债权[".$debtInvestInfo['name'].'],'.$v['_source']['parameters']['money']."元";
    				}else if($v['_source']['action'] == 'create'){
    					$discount = $v['_source']['parameters']['discount_money'] !="" ? $v['_source']['parameters']['discount_money'] : "0";
    					$listInfo[$k]['desc'] = "[出让债权".$debtCreateInfo['name']."] [份额".$v['_source']['parameters']['money']."元，折让金".$discount."元]";
    					$listInfo[$k]['api_tips'] = "[出让]";	//配置页的crete为公用,此处按场景处理为出让
    				}else if($v['_source']['action'] == 'cancel'){
    					$listInfo[$k]['desc'] = "[取消债权转让]";
    				}
    				break;
    			case 'user/money_account/withdraw':	//提现',
    				$listInfo[$k]['desc'] = "申请提现".$v['_source']['parameters']['money']."元 ";
    				break;
    			case 'sign': //签到	auditlog未给出明确积分值
    				$listInfo[$k]['desc'] = $v['_source']['parameters']['info'];
    				break;
    			case 'user/benefit_account/bbs_credit':	//金币兑换
    				$listInfo[$k]['desc'] = "使用".$v['_source']['parameters']['minus']."金币兑换积分 ";
    				break;
    			case 'user/benefit_account/credit_prize': //积分兑换
    				$goodsInfo = $this->getGoodsInfo($v['_source']['parameters']);
    				$listInfo[$k]['desc'] = "使用".$v['_source']['parameters']['need_credit']."积分兑换商品[".$goodsInfo['gname']."]";
    				break;
    			case 'user/auth_account/paypwd':	//交易密码 set设置 edit修改
    			case 'user/auth_account/pwd':		//登录密码 set设置 edit修改
    				$listInfo[$k]['desc'] = $listInfo[$k]['resource'].$listInfo[$k]['action'];
    				break;
    			case 'user/auth_safe/phone_auth/step1':	//修改认证手机号第1步
    				$listInfo[$k]['desc'] = "原手机号：".$v['_source']['parameters']['phone'];
    				break;
    			case 'user/auth_safe/phone_auth/step2':	//修改认证手机号第2步
    				$listInfo[$k]['desc'] = "新手机号：".$v['_source']['parameters']['phone'];
    				break;
    			case 'user/auth_safe/email_auth':	//修改认证邮箱
    				$listInfo[$k]['desc'] = "修改认证邮箱，原邮箱：".$v['_source']['parameters']['email'];
    				break;
    			case 'user/auth_safe/realname_auth_api': //接口实名认证
    			case 'user/auth_safe/realname_auth_artificial': //提交上传实名认证
    				$listInfo[$k]['desc'] = $listInfo[$k]['resource'];
    				break;
    			case 'apiService/phone/getSmsVcode':	//新版注册获取短信验证码
    				$regPhone = substr_replace($v['_source']['parameters']['phone'],'****',3,4);
    				$listInfo[$k]['desc'] = '注册手机号'.$regPhone;
    				break;
    			case 'points_exchange_goods':	//积分兑换商品
    				$listInfo[$k]['desc'] = $v['_source']['parameters']['info'];
    				break;
    			case 'RECHARGE': //充值
    				$requestNo = $v['_source']['parameters']['send']['reqData']['requestNo'];	//请求流水号;
    				$requestNo = $requestNo ? $requestNo : $v['_source']['parameters']['send']['respData']['requestNo'];
    				
    				$amount = $v['_source']['parameters']['send']['reqData']['amount'];	//充值金额
    				$amount = $amount ? $amount : $v['_source']['parameters']['send']['respData']['amount'];
    				
    				$rechargeStatus = Yii::app()->c->esAuditConfig['rechargeStatus'][$v['_source']['parameters']['send']['respData']['rechargeStatus']]; //
    				
    				//$queryType = array();
    				//$queryType['requestNo'] = $requestNo;
    				//$queryType['resource'] = 'RECHARGE'; //交易确认 直接获取该流水的结果
    				//$transStatus = $this->getTransactionInfo($queryType);	//交易结果去新网查询
    				//$status = Yii::app()->c->esAuditConfig['rechargeStatus'][$transStatus];
    				
    				//$listInfo[$k]['status'] = $status ? $status : $transStatus;
    				$listInfo[$k]['status']=  $rechargeStatus ? $rechargeStatus : $listInfo[$k]['status'];
    				$listInfo[$k]['desc'] = "[充值订单号:".$requestNo."] [金额:".$amount."]";
    				break;
    			case 'WITHDRAW': //提现
    				$queryType = array();
    				
    				$requestNo = $v['_source']['parameters']['send']['reqData']['requestNo'];	//请求流水号;
    				$requestNo = $requestNo ? $requestNo : $v['_source']['parameters']['send']['respData']['requestNo'];
    				
    				$amount = $v['_source']['parameters']['send']['reqData']['amount'];	//充值金额
    				$amount = $amount ? $amount : $v['_source']['parameters']['send']['respData']['amount'];
    				
    				$withdrawStatus = Yii::app()->c->esAuditConfig['withdrawType'][$v['_source']['parameters']['send']['respData']['withdrawStatus']];
    				
    				$remit = $v['_source']['parameters']['send']['respData']['remitType'];
    				$remitType = $remit == "NORMAL_URGENT" ? ',[到账日:普通T+0出款]': ($remit == "URGENT" ? ',[到账日:实时T+0出款]': ($remit == "NORMAL" ? ',[到账日:T+1出款]' : ''));
    				
    				//$queryType['requestNo'] = $requestNo;
    				//$queryType['resource'] = 'WITHDRAW'; //交易确认 直接获取该流水的结果
    				//$transStatus = $this->getTransactionInfo($queryType);	//交易结果去新网查询
    				//$status = Yii::app()->c->esAuditConfig['withdrawType'][$transStatus];
    				
    				//$listInfo[$k]['status'] = $status ? $status : $transStatus;
    				$listInfo[$k]['status']=  $withdrawStatus ? $withdrawStatus : $listInfo[$k]['status'];
    				$listInfo[$k]['desc'] = "[提现订单号:".$requestNo."] [金额:".$amount."]".$remitType;
    				break;
    			case 'PERSONAL_REGISTER_EXPAND': //个人绑卡注册
    			case 'ACTIVATE_STOCKED_USER': //会员激活
    				if($v['_source']['action'] == 'gateway'){	//网关接口返回信息处理
    					$authlist = $this->getAuthlist($v['_source']['parameters']['send']['reqData']);
    					$listInfo[$k]['desc'] = $authlist;
    				}else{
    					$status = $v['_source']['parameters']['send']['respData']['status'] == 'SUCCESS' ? '成功':'失败';	//状态值
    					
    					$checkType = Yii::app()->c->esAuditConfig['accessType'][$v['_source']['parameters']['send']['respData']['accessType']]; //鉴权通过类型
    					$checkType_tips = "[鉴权通过类型:".$checkType."] ";
    					
    					$userRole = Yii::app()->c->esAuditConfig['userRoleType'][$v['_source']['parameters']['send']['respData']['userRole']]; //用户角色
    					$userRole_tips = "[角色:".$userRole."] ";
    					
    					$idCardType = Yii::app()->c->esAuditConfig['idCardType'][$v['_source']['parameters']['send']['respData']['idCardType']]; //证件类型
    					$idCardType_tips = "[证件类型:".$idCardType."] ";
    					
    					$auditStatus = Yii::app()->c->esAuditConfig['auditStatus'][$v['_source']['parameters']['send']['respData']['auditStatus']]; //证件类型
    					$auditStatus_tips = "[审核状态:".$auditStatus."] ";
    					
    					
    					$realname = substr_replace($v['_source']['parameters']['send']['respData']['realName'],"**",3);
    					$realname_tips =  "[姓名:".$realname."] ";
    					
    					$bankcardNo = substr_replace($v['_source']['parameters']['send']['respData']['bankcardNo'],'****',6,9);
    					$bankcardNo_tips ="[银行卡号:".$bankcardNo."] ";
    					
    					$mobile = substr_replace($v['_source']['parameters']['send']['respData']['mobile'],'****',3,4);
    					$mobile_tips =  "[预留Phone:".$mobile."] ";
    					
    					$responseType = $v['_source']['parameters']['send']['responseType'];
    					$responseType_tips = $responseType ? Yii::app()->c->esAuditConfig['responseType'][$responseType] : '';

    					$listInfo[$k]['desc'] = $checkType_tips.$auditStatus_tips.$realname_tips.$mobile_tips.$userRole_tips.$idCardType_tips.$bankcardNo_tips;
    				}
    				$listInfo[$k]['status']=  $status ? $status : $listInfo[$k]['status'];
    				break;
				case 'USER_PRE_TRANSACTION': //债权认购,直投预处理 	目前优惠券无法处理934379;6538573
					$transInfo = $queryType = array();
					$queryType['requestNo'] = $v['_source']['parameters']['send']['reqData']['requestNo'];
					$queryType['resource'] = 'PRETRANSACTION'; //交易确认 直接获取该流水的结果
					$transStatus = $this->getTransactionInfo($queryType);	//交易结果去新网查询
					$status = Yii::app()->c->esAuditConfig['proccessType'][$transStatus];
					
					$amount = "[金额:".$v['_source']['parameters']['send']['reqData']['amount']."] ";
					
					$bizTypeInfo = $v['_source']['parameters']['send']['reqData']['bizType'];
					$bizType = Yii::app()->c->esAuditConfig['bizType'][$bizTypeInfo]; //预处理业务类型
					$bizType_tips = "[业务类型:".$bizType."] ";
					
					//预处理目前对直投和债权做了处理  若存在COMPENSATORY代偿 和 REPAYMENT还款 需补充
					//因无法通过新网看到最终的状态值,上线后查看用户6546101,0921的数据状态是否返回合规,同一时间返回了多条投标
					if($bizTypeInfo == 'TENDER'){	//直投
						$preMarketingAmount = $v['_source']['parameters']['send']['reqData']['preMarketingAmount'];//预备使用的红包金额,只记录不冻结,仅限投标业务类型 6830827
						$preMarketingAmount_tips = $preMarketingAmount ? "[红包金额:".$preMarketingAmount."] " : ""; 
						$remark_tips = "[投资项目:".$v['_source']['parameters']['send']['reqData']['remark']."] ".$preMarketingAmount_tips;
					}else if($bizTypeInfo == 'CREDIT_ASSIGNMENT'){	//债权
						$transInfo['borrow_id'] = $v['_source']['parameters']['send']['reqData']['projectNo'];
						$remark = $this->getInvestInfo($transInfo);
						$remark_tips = "[认购债权:".$remark['name']."] ";
					}
					
					$listInfo[$k]['status'] = $status ? $status : $transStatus;
    				$listInfo[$k]['desc'] = $remark_tips.$amount.$bizType_tips;
    				break;
				case 'DEBENTURE_SALE':	//单笔债券出让 
					$saleInfo['borrow_id'] = $v['_source']['parameters']['send']['reqData']['projectNo'];
					$remark = $this->getInvestInfo($saleInfo);
					$remark_tips = "[出让债权:".$remark['name']."] ";
					
					$saleShare = $v['_source']['parameters']['send']['reqData']['saleShare'];	//出让份额
					$saleShare_tips = "[份额:".$saleShare."] ";
					
					$listInfo[$k]['desc'] = $remark_tips.$saleShare_tips;
					break;
				case 'USER_AUTO_PRE_TRANSACTION':	//用户充值并投资 直连接口
					$transInfo = $queryType = array();
					$queryType['requestNo'] = $v['_source']['parameters']['send']['reqData']['requestNo'];
					$queryType['resource'] = 'TRANSACTION'; //交易确认 直接获取该流水的结果
					$transStatus = $this->getTransactionInfo($queryType);	//交易结果去新网查询
					$status = Yii::app()->c->esAuditConfig['transActionType'][$transStatus];
					
					$transInfo['borrow_id'] = $v['_source']['parameters']['send']['reqData']['projectNo'];
					$borrow = $this->getInvestInfo($transInfo);		//项目信息
					$amount = "[金额:".$v['_source']['parameters']['send']['reqData']['amount']."] ";
					
					$bizTypeInfo = $v['_source']['parameters']['send']['reqData']['bizType'];
					$bizType = Yii::app()->c->esAuditConfig['bizType'][$bizTypeInfo]; //预处理业务类型
					$bizType_tips = "[业务类型:".$bizType."] ";
					
					if($bizTypeInfo == 'TENDER'){	//直投
						$preMarketingAmount = $v['_source']['parameters']['send']['reqData']['preMarketingAmount'];//预备使用的红包金额,只记录不冻结,仅限投标业务类型 6830827
						$preMarketingAmount_tips = $preMarketingAmount ? "[红包金额:".$preMarketingAmount."] " : ""; 
						$remark_tips = "[投资项目:".$borrow['name']."] ".$preMarketingAmount_tips;
					}else if($bizTypeInfo == 'CREDIT_ASSIGNMENT'){	//债权
						$remark_tips = "[认购债权:".$borrow['name']."] ";
					}
					
    				$listInfo[$k]['status'] = $status ? $status : $transStatus;
    				$listInfo[$k]['desc'] = $remark_tips.$amount.$bizType_tips;
    				break;
    			default:
    				$listInfo[$k]['resource'] = $listInfo[$k]['resource'] ? $listInfo[$k]['resource'] :$v['_source']['resource'];
    				$listInfo[$k]['api_tips'] = $listInfo[$k]['action'] ? $listInfo[$k]['action'] : $v['_source']['action'];
    				break;
    		}
    	}
    
    	if($esresult){
    		$returnResult['code'] = 0;
    		$returnResult['info'] = 'success';
	    	$returnResult['data']['listTotal'] = $esresult['hits']['total'];
	    	$returnResult['data']['listInfo'] = $listInfo;
    	}else{
    		$returnResult['data'] = array();
    	}
    	return $returnResult;
    }
    
    /**
     * 兼容user_id,platformUserNo
     */
    public function getUserIdInfo($data){
    	
    	//判断system是否toXw
    	$stytemType = substr($data['system'],0,4);
    	
    	if($stytemType == 'toXw'){
    		foreach($data['parameters']['send'] as $k=>$v){
    					if(in_array($v['platformUserNo'],$v)){
    						$returnUserId = $v['platformUserNo'];
    					}
    		}
    	}else{
    		$returnUserId = $data['user_id']? $data['user_id'] : $data['parameters']['user_id'];
    		
    	}
    	return $returnUserId;
    	
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
	 * 获取积分商品信息
	 */
	public function getGoodsInfo($data){
	
		$GoodsModel = new DwCreditExchangeGoods();
		$criteria = new CDbCriteria;
		$attributes = array(
				"id"    =>   $data['goodid'],
		);
		$GoodsResult =$GoodsModel->findByAttributes($attributes,$criteria);
		return $GoodsResult;
	}
	
	/**
	 * 获取认购债权相关信息
	 */
	public function getDebtInvestInfo($data){
		
		
		/* $params = UrlUtil::_url2key('DEBTSM150565739459BE8232F0139304');
		if (stripos($params[0], ',') === true) {
			$isNovice = explode(',', $params[0]);
			$debtid = $isNovice[0];
		} else {
			$debtid = $params[0];
		} */
		
		$debtid = $data['debt_id'];
		$DebtModel = new Debt();
		$criteria = new CDbCriteria;
		$attributes = array(
				"id"    =>   $debtid,
		);
		$DebtResult = $DebtModel->findByAttributes($attributes,$criteria);
		$BorrowResult = $this->getInvestInfo($DebtResult);

		return $BorrowResult;
	}
	
	/**
	 * 获取出让债权相关信息
	 */
	public function getDebtCreateInfo($data){

		$DebtModel = new Debt();
		$criteria = new CDbCriteria;
		$attributes = array(
				"tender_id"    =>   $data['tender_id'],
		);
		
		$DebtResult = $DebtModel->findByAttributes($attributes,$criteria);
		$BorrowResult = $this->getInvestInfo($DebtResult);
		
		return $BorrowResult;
	}
	
	/**
	 * 获取项目信息
	 */
	public function getInvestInfo($data){
		
		$BorrowModel = new Borrow();
		$criteria = new CDbCriteria;
		$attributes = array(
				"id"    =>   $data['borrow_id'],
		);
		$BorrowResult =$BorrowModel->findByAttributes($attributes,$criteria);
		return $BorrowResult;
	
	}
	
	/**
	 * 获取优惠券信息
	 */
	public function getCouponInfo($data){

		$coupon = $data['coupon'];
		
		if( $coupon!= ""){
			if(is_array($coupon)){
				$coupon = implode(',',$coupon);
			}
			
			$sql = "select remark from dw_coupon where id in(".$coupon.")";
			$list= Yii::app()->dwdb->createCommand($sql)->queryAll();
				
			$info="";
			$sp="";
			foreach($list as $k=> $v){
				$info.=$sp.$v['remark'];
				$sp = ",";
			}
			$result = "[使用优惠券".$info."]";
		}else{
			$result = "[未使用优惠券]";
		}
	
		return $result;
	}
	/**
	 * 处理请求授权类型 不同的用户角色有不同的授权类型
	 */
	public function getAuthlist($data){
	
		$params = $data['authList'];
		$userrole = $data['userRole'];
		$authInfo = '';
		if (stripos($params, ',') == true) {
			$islist = explode(',', $params);
			foreach($islist as $k => $v){
				$authInfo .= Yii::app()->c->esAuditConfig['authList'][$v].'.';
			}
		} else {
			$authInfo = Yii::app()->c->esAuditConfig['authList'][$params];
		}
		$userRoleInfo = Yii::app()->c->esAuditConfig['userRoleType'][$userrole];
		
		$result = "[用户请求授权列表:".$authInfo."] [用户角色:".$userRoleInfo."]";
	
		return $result;
	}
	
	/*
	 * 交易查询
	 * requestNo是验签回调的唯一表示,需要在getaway请求时判断最终结果是否成功
	 * transactionType:	RECHARGE充值;  WITHDRAW提现; TRANSACTION交易确认;
	*/
	public function getTransactionInfo($data=array()){
	
		$request_no = $data['requestNo'];
		$resource= $data['resource'];
		//调取存管接口
		$requiredData = $transData =$return = array();
		$requiredData['serviceName'] = 'QUERY_TRANSACTION';
		$transData['requestNo'] = $request_no;
		$transData['transactionType'] = $resource;
		$requiredData['reqData'] = $transData;
		$result = CurlService::getInstance()->service($requiredData);
		//var_dump($result);
		if ($result['code'] == '-4'){
			$returnResult = $result['data']['errorMessage'];
		}else{
			$returnResult = $result['data']['records'][0]['status'];
		}
		return $returnResult;
	}
	
	
	/*-------------------------------------------分割线-------------------------------------------------*/
	/*
	 * 用户提现到账日查询
	*/
	public function getWithdrawInfo($data=array()){
		Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
		$returnResult = array(
				'code' => '', 'info' => '', 'data' => array('listTotal' => 0, 'listInfo' => array())
		);
		
		//条件筛选
		if (count($data) > 0) {
				
			//依据状态搜索
			if (isset($data['trade_no']) && $data['trade_no'] != '') {
				$trade_no = $data['trade_no'];
			}
			
			//分页条数设置
			$limit = in_array($data['limit'], array(10, 20, 30, 40, 50)) ? intval($data['limit']) : $limit;
			//请求页数
			$page = (isset($data['page']) && $data['page'] != '') ? intval($data['page']) : $page;
		}
		
		$trade_no = $data['trade_no'];
		//调取存管接口-提现交易明细
		$requiredData = $cashData =$return = array();
		$requiredData['serviceName'] = 'QUERY_TRANSACTION';
		$cashData['requestNo'] = $trade_no;
		$cashData['transactionType'] = 'WITHDRAW';
		$requiredData['reqData'] = $cashData;
		$result = CurlService::getInstance()->service($requiredData);
		
		if($result){
			$return['trade_no_tips'] = $trade_no;
			$records = $result['data']['records'][0];
			$type = array('NORMAL_URGENT'=>'普通T+0出款','NORMAL'=>'T+1出款','URGENT'=>'实时T+0出款');
			foreach ($records as $k=>$val){
				$return['remitType_tips'] = $type[$records['remitType']];
			}
		}
		
		if (empty($result)){
			$returnResult['info'] = "提现信息不存在";
		}else{
			$returnResult['code'] = 0;
			$returnResult['info'] = "success";
			$returnResult['data']['listTotal'] = 1;
			$returnResult['data']['listInfo'][] = $return;
		}
		return $returnResult;
	}
	
	/*
	 * 获取用户流水
	 */
	public function getUserAccountLogList($data = array(), $limit = 10, $page = 1){
		Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
		$returnResult = array(
				'code' => '', 'info' => '', 'data' => array('listTotal' => 0, 'listInfo' => array())
		);
		
		//默认展示一周内数据
		$time_start = time()-7*86400;
		$conditions = " 1 = 1 ";
		$order = ' order by id desc';
		//条件筛选
		if (count($data) > 0) {
			
			//依据状态搜索
			if (isset($data['transid']) && $data['transid'] != '') {
				$conditions .= ' and transid = "'.htmlspecialchars(addslashes(trim($data['transid']))).'"';
			}
			
			//依据状态搜索
			if (isset($data['type']) && $data['type'] != '') {
				$conditions .= ' and type = "'.htmlspecialchars(addslashes(trim($data['type']))).'"';
			}
			
			//依据用户ID
			if (isset($data['user_id']) && $data['user_id'] != '') {
				$conditions .= ' and user_id = '.intval($data['user_id']);
			}
			
			//依据用户手机号
			if (isset($data['phone']) && $data['phone'] != '') {
				$user_id = $this->getUserIdByPhone($data['phone']);
				$conditions .= ' and user_id = '.intval($user_id);
			}
			
			//使用时间搜索
			if( (isset($data['begin_addtime']) && $data['begin_addtime']!='') && (isset($data['end_addtime']) && $data['end_addtime']!='') ){
				$conditions .= " and addtime between ".intval($data['begin_addtime'])." and ".intval($data['end_addtime']);
			} else {
				$conditions .= " and addtime > ".$time_start; 
			}
			
			//分页条数设置
			$limit = in_array($data['limit'], array(10, 20, 30, 40, 50)) ? intval($data['limit']) : $limit;
			//请求页数
			$page = (isset($data['page']) && $data['page'] != '') ? intval($data['page']) : $page;
		}
		
		
		$sql = " select count(*) num from dw_account_log where " . $conditions;
		$count = Yii::app()->dwdb->createCommand($sql)->queryRow();
		$listTotal = intval($count['num']);
		if ($listTotal == 0) {
			$returnResult['code'] = 0;
			$returnResult['info'] = '暂无数据！';
			return $returnResult;
		}
		$returnResult['data']['listTotal'] = $listTotal;
		
		$sql = "select id,transid,user_id,type,borrow_type,direction,total,money,use_money,collection,invested_money,addtime 
				from dw_account_log where". $conditions;
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
	 * 查看流水详情
	 */
	public function getUserAccountLogInfo($data=array()){
	
		Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
		$returnResult = array(
				'code' => '1', 'info' => 'error', 'data' => array("listInfo"=>array())
		);
		$accountlogID = isset($data['id']) ? intval($data['id']) : 0;
		if (empty($accountlogID)){
			$returnResult['info'] = "资金流水id不存在";
			return $returnResult;
		}
		$sql = "select id,transid,user_id,type,borrow_type,direction,total,money,virtual_money,use_money,no_use_money,recharge_amount,
				collection,withdraw_free,use_virtual_money,no_use_virtual_money,invested_money,addtime,to_user,remark  
				from dw_account_log where id = ".$accountlogID;
		$info = Yii::app()->dwdb->createCommand($sql)->queryRow();
	
		$info = $this->listResTrans($info);
		
		if (empty($info)){
			$returnResult['info'] = "申请信息不存在";
		}else{
			$returnResult['code'] = 0;
			$returnResult['info'] = "success";
			$returnResult['data']['listInfo'] = $info;
		}
		return $returnResult;
	}
	
	/**
	 * 用户流水结果转化
	 */
	public function listResTrans($data=array()){
		Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
	
		$account_type = $this->getUserAccountType($data);
		$userInfo = $this->getUserInfoById($data);
		
		$data['realname'] = $userInfo['realname'];
		$data['phone'] = substr_replace($userInfo['phone'],'****',3,4);
		$data['username'] = $userInfo['username'];
		$data['all_p2p'] = $data['total']+$data['collection'];
		$data['addtime'] = date('Y-m-d H:i:s',$data['addtime']);
		$data['type_tips'] = $account_type;
		return $data;
	}
	
	//获取资金流水类型
	public function getUserAccountType($data=array()){
		//dw_account_log中type的值
		$account_type = (Yii::app()->params['account_type']);
		//新log_type
		$account_log_type = Yii::app()->params['account_log_type'];
		//项目类型
		$borrow_type = Yii::app()->params['borrow_type_online_usertrade'];
		if(isset($account_type[$data['type']])){
			$returnType = $account_type[$data['type']];
		}elseif(isset($account_log_type[$data['type']])){
			$returnType = $borrow_type[$data['borrow_type']].$account_log_type[$data['type']];
		}
	
		return $returnType;
	}
	
	/**
	 * 依据用户ID获取用户信息
	 */
	public function getUserInfoById($data=array()){
		
		$user_id=intval($data['user_id']);
		$UserModel = new User();
		$criteria = new CDbCriteria;
		$attributes = array(
				"user_id"    =>   $user_id,
		);
		$UserResult =$UserModel->findByAttributes($attributes,$criteria);
		return $UserResult;
	
	}
	
	/**
	 * 获取类型来源
	 */
	public function getAccountLogTypeSrc($data=array()){
		Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
	
		$account_type = (Yii::app()->params['account_type']);
	
		$returnResult['code'] = 0;
		$returnResult['info'] = '获取资金流水类型成功';
		$returnResult['data']['info'] = $account_type;
	
		return $returnResult;
	}
	
	
	//下载前的用户核对
	public function getUserAssets($data=array(),$limit=10,$page=1){
		
		Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
		$returnResult = array(
				'code' => '', 'info' => '', 'data' => array('listTotal' => 0, 'listInfo' => array())
		);
		
		$conditions = ' 1 = 1 ';
		$order = ' order by b.id asc';
		
		if($data['user_id'] == '' && $data['phone'] == '' && $data['username'] == ''){	//没有搜索不展示任何数据
			$returnResult['code'] = 0;
			$returnResult['info'] = '暂无数据！';
			return $returnResult;
		}
		//条件筛选
		if (count($data) > 0) {
		
			//依据用户名或用户ID搜索
			if (isset($data['user_id']) && $data['user_id'] != '') {
				$conditions .= ' and t.user_id = '.intval($data['user_id']);
			}
				
			//手机号搜索
			if (isset($data['phone']) && $data['phone'] != '') {
				$conditions .= ' and u.phone =  ' . "'" . trim($data['phone']) . "'";
			}
			
			//用户名搜索
			if (isset($data['username']) && $data['username'] != '') {
				$conditions .= ' and u.username like  ' . '"%' . htmlspecialchars(addslashes(trim($data['username']))) . '%"';
			}
				
			//分页条数设置
			$limit = in_array($data['limit'], array(10, 20, 30, 40, 50)) ? intval($data['limit']) : $limit;
			//请求页数
			$page = (isset($data['page']) && $data['page'] != '') ? intval($data['page']) : $page;
		}
		
		$sql = "SELECT count(b.id) num
				FROM dw_borrow_tender t
				LEFT JOIN dw_borrow b ON t.borrow_id = b.id
				LEFT JOIN dw_user u ON t.user_id = u.user_id
				WHERE {$conditions} AND b.STATUS IN (1, 3) AND t.money<>0
				GROUP BY b.id";
		$count = Yii::app()->dwdb->createCommand($sql)->queryAll();
		$listTotal = intval(count($count));
		if ($listTotal == 0) {
			$returnResult['code'] = 0;
			$returnResult['info'] = '暂无数据！';
			return $returnResult;
		}
		$returnResult['data']['listTotal'] = $listTotal;
		
		$sql = "SELECT b.id,b.name borrow_name,t.user_id,u.phone,sum(t.account) money,t.addtime,b.repayment_time,u.username,u.realname 
				FROM dw_borrow_tender t 
				LEFT JOIN dw_borrow b ON t.borrow_id = b.id 
				LEFT JOIN dw_user u ON t.user_id = u.user_id 
				WHERE {$conditions} AND b.STATUS IN (1, 3) AND t.money<>0
				GROUP BY b.id";
		$sql .= $order;
		$offsets = ($page - 1) * $limit;
		$sql .= " LIMIT $offsets,$limit";
		$list = Yii::app()->dwdb->createCommand($sql)->queryAll();
		
		foreach ($list as $key=>$value){
			$list[$key]['phone'] = substr_replace($value['phone'],'****',3,4);
			$list[$key]['realname'] = substr_replace($value['realname'],'**',3);
		}
	
		$returnResult['code'] = 0;
		$returnResult['info'] = '获取列表成功';
		$returnResult['data']['listInfo'] = $list;
		return $returnResult;
	}
	
	//列表导出
	public function exportUserAssets($data = array()){
		Yii::log ( __FUNCTION__.print_r(func_get_args(),true),'info');
		$returnResult = array(
				'code' => '', 'info' => '', 'data' => array()
		);
		//日志审计参数
		$logParams = array(
				'user_id' => Yii::app()->user->id, 'system' => 'admin',
				'action' => 'export', 'resource' => 'ccs', 'parameters' => '', 'status' => 'fail'
		);
	
		//导出时间
		$parameters['export_time'] = time();
		$logParams['parameters'] = json_encode($parameters);
		ini_set("memory_limit", "-1");
		ini_set('ini_setmax_execution_time', '2000');
	
		$achievementResult = $this->getUserAssets($data,2000);
		$achievementList = $achievementResult['data']['listInfo'];
		$parameters['num'] = count($achievementList);
		$logParams['parameters'] = json_encode($parameters);
	
		//引入excel类
		Yii::import("itzlib.plugins.phpexcel.*");
		$PHPExcelObj = new PHPExcel();
	
		//设置导出的title
		$PHPExcelObj->getActiveSheet()->setTitle(date("Y-m-d") . '用户资产证明');
		$PHPExcelObj->getActiveSheet()->setCellValue('A1', '投资项目ID');
		$PHPExcelObj->getActiveSheet()->setCellValue('B1', '投资项目名称');
		$PHPExcelObj->getActiveSheet()->setCellValue('C1', '投资金额');
		$PHPExcelObj->getActiveSheet()->setCellValue('D1', '投资时间');
		$PHPExcelObj->getActiveSheet()->setCellValue('E1', '最后还本时间');
	
		//设置列宽
		$PHPExcelObj->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
		$PHPExcelObj->getActiveSheet()->getColumnDimension('B')->setWidth(35);
		$PHPExcelObj->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
		$PHPExcelObj->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
		$PHPExcelObj->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
		
		$i = 2;
		foreach ($achievementList as $key => $value) {
	
			//配置数据源
			$outputData = array();
			$outputData['addtime'] =  date("Y-m-d H:i:s",$value['addtime']);
			$outputData['repayment_time'] = date("Y-m-d H:i:s",$value['repayment_time']);
			$outputData['id'] = $value['id'];
			$outputData['borrow_name'] = $value['borrow_name'];
			$outputData['money'] = $value['money'];
	
			//填充数据
			$PHPExcelObj->getActiveSheet()->setCellValueExplicit('A' . $i, $outputData['id']);
			$PHPExcelObj->getActiveSheet()->setCellValueExplicit('B' . $i, $outputData['borrow_name']);
			$PHPExcelObj->getActiveSheet()->setCellValueExplicit('C' . $i, $outputData['money']);
			$PHPExcelObj->getActiveSheet()->setCellValueExplicit('D' . $i, $outputData['addtime']);
			$PHPExcelObj->getActiveSheet()->setCellValueExplicit('E' . $i, $outputData['repayment_time']);
			$i++;
		}
		//审计日志
		$logParams['status'] = 'success';
		AuditLog::getInstance()->method('add', $logParams);

		$file_name = "用户资产证明";
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

	
}