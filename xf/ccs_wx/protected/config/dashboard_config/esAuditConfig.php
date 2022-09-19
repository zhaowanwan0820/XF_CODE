<?php

return array(

    //平台
    'system' => array(
        '' => '全部',
        'web/pc' => 'PC',
        'web/wap' => 'WAP',
        'app/ios' => 'iOS',
        'app/android' => 'Android',
    	'toXw/pc' => 'PC(to新网)',
        'toXw/wap' => 'WAP(to新网)',
        'toXw/ios' => 'iOS(to新网)',
        'toXw/android' => 'Android(to新网)',
    	'toXw/' => '(to新网)'
    	
    		
    ),

    //用户行为 依据前台日志查看 action判断不了准确行为
    'action' => array(
        '' => '全部',
    	'reg' => '注册',
        'login' => '登录',
        'logout' => '登出',
    	'register' => '注册',
        'recharge' => '快捷充值',
        'bindcard' => '绑卡',
    	'invest' => '[投资]',
    	'create' => '添加',
    	'edit' => '编辑',
    	'delete' => '删除',
    	'set' => '设置',
    	'recharge' => '[跳转]',
    	'callback' => '[回调]',
    	'withdraw' => '[提现]',
    	'cancel' => '[取消债权]',
    	'modify' => '修改',
    	'verifySignatur' => '[验签接口]',
    	'gateway' => '[网关接口]',
    	'service' => '[直连接口]',
    ),

    //行为明细展示
    'resource' => array(
    		
    	'user/reg' => '注册爱投资',		
    	'user/semregv3' => 'landingpagev3',
    	'user/reg/step1' => 'wap第一步',
    	'user/reg/step2/getSms' => 'wap注册第二步',
    	'user/reg/step2/commit' => 'wap注册第二步',
    	'user/reg/sendSms' => 'pc注册第二步',
    	'user/reg/phoneConfirm' => 'pc注册第二步',
    		
    	'user/login' => '登录爱投资',
    	'user/logout' => '登出',
    		
    	//3app启动
    	
    	'user/bank_account/card' => '银行卡操作',
    	'user/bank_account/card_safe' => '快捷卡操作',
    		
    	'user/money_account/recharge/getResult' => '充值',
    	'user/money_account/withdraw' => '提现',
    	'user/money_account/invest' =>'投资',   		
    	'user/money_account/debt' => '债权',  //cancel取消 create出让
    	
    	'sign' => '签到',
    	'user/benefit_account/bbs_credit' => '金币兑换',
    	'user/benefit_account/credit_prize' => '积分兑换',

    	//11、进入论坛；12、论坛发帖；13、论坛回帖
    	
    	'user/benefit_account/invite' => '邀请好友',
    		
		//15分享页面
		
    	'user/contract/download' => 'PC用户中心合同下载',
    	'user/contractDebt/download'=> '用户中心购买债券合同下载',
    		
    	//17 18 绑定 解绑微信
    	'user/auth_account/pwd/step1' => '找回登录密码第一步',
    	'user/auth_account/pwd/step2' => '找回登录密码第二步',
    	'user/auth_account/pwd/step3'=> '找回登录密码第三步',
    	'user/auth_account/pwd/step2/checkCardId' => '找回登录密码第二步',
    	'user/auth_account/pwd/step2/commit' => '找回登录密码第二步',
    	'user/auth_account/pwd/step3/commit' => '找回登录密码第三步',	
    		
    	'user/auth_account/pwd' => '登录密码',
    	'user/auth_account/paypwd' => '支付密码',	//set edit 
    	
    	'user/auth_safe/phone_auth/step1' => '修改手机认证第一步',
    	'user/auth_safe/phone_auth/step2' => '修改手机认证第二步',
    	'user/safeAjax/modifyPhoneAuth/step2'	 => '修改手机号第二步',
    	'user/safeAjax/getModifyPhoneAuthCode' => '修改手机号获取验证码',
    	'user/safeAjax/modifyPhoneAuth/step1' => '修改手机号第一步',
		'user/auth_safe/email_auth' => '邮箱认证', 		
    	'user/auth_safe/realname_auth_api' => '接口实名认证',
    	'user/auth_safe/realname_auth_artificial' => '提交上传实名认证',
    	
    	//25、登录安见资本；26、签署合格投资者承诺书；27、风险问卷调查；28、申购安见订单；29、上传付款凭证。
    	'index/log' => 'app客户端日志记录',	
    	'install' => 'app客户端安装',
    	'calculationTender' => '分析 app 用户优惠券使用 bug',
    	'user/auth_account/paypwd/step1' => '找回支付密码第一步',
    	'user/auth_account/paypwd/step2' => '找回支付密码第二步',
    	'user/auth_account/paypwd/step3' => '找回支付密码第三步',
    	'user/auth_safe/phone_auth/getSms' => '手机认证',
    	'user/auth_safe/phone_auth/commit' => '手机认证',
    	'user/auth_safe/phone_auth/getSmsVcode' =>'手机认证',
    	'user/auth_safe/phone_auth' => '手机认证,',
    	'user/safeAjax/updatePwd' => '登录密码修改',
    	'user/app_bindcard/step1' => '快捷充值绑卡第一步',
    	'user/app_bindcard/step2' => '快捷充值绑卡第二步',
        'user/app_recharge/step1' => '快捷充值第一步',
        'user/money_account/recharge/sendSms' => '快捷充值第一步',
        'user/money_account/recharge/reSendSms' => '快捷充值第一步',
        'user/app_recharge/step2' => '快捷充值第二步',
        'user/money_account/recharge/commit' => '快捷充值第二步',
        'user/app_recharge/step3' => '快捷充值第三步',
        //'user/money_account/recharge/getResult' => '快捷充值第三步',
        'user/money_account/recharge' => '充值跳转',
    	'apiService/phone/getSmsVcode'     => '新版注册获取短信验证码',
    	'user/ajax/getDebtinfos'           => '债权转让页面获取数据',
    	'points_exchange_goods'            => '积分兑换商品',
    	'event/firstInvestAgain/luckydraw' => '在活动期间进行首投的用户抽奖活动 ： 分享之后抽奖接口',
    	'event/firstInvest/luckydraw'      => '在活动期间进行首投的用户抽奖活动 ： 第一次抽奖接口',
    		
    	/* ----------存管---------- */
		'RECHARGE' =>'充值',
		'WITHDRAW' =>'提现',	//4098025 1813346
		'ACTIVATE_STOCKED_USER' =>'会员激活', //平台通过此接口将已导入存管系统的会员激活，用于迁移场景 1009262   1132633
		'DEBENTURE_SALE' =>'单笔债权出让',	//6371271	6844225
		'PERSONAL_REGISTER_EXPAND' =>'个人绑卡注册', // 用户在 P2P 平台注册完成后，P2P平台引导个人用户跳转到存管页面填写四要素信息
		'RESET_PASSWORD' =>'修改交易密码',	//用户发起重置密码，平台引导用户跳转至存管页面
		'USER_AUTO_PRE_TRANSACTION' =>'用户充值并投资',	//6843995
		'MODIFY_MOBILE_EXPAND' =>'预留手机号更新',	//平台调用此接口引导用户跳转至存管系统页面
    	'USER_PRE_TRANSACTION' => '债权认购,直投预处理', //债权认购 直投预处理 4098025 6742743 948280 5719871   查(139.204.46.78)
    	'CANCEL_DEBENTURE_SALE' => '债权出让取消',	
    	'PERSONAL_REGISTER_EXPAND' => '个人绑卡注册',	//6843995 6830827
    	'CHECK_PASSWORD' => '验证密码',
    	'CHANGE_USER_BANKCARD' =>'未激活换卡',
    	'PERSONAL_BIND_BANKCARD_EXPAND' => '个人绑卡',
    	'SYNC_TRANSACTION' => '单笔交易',
    	'ASYNC_TRANSACTION' => '批量交易',
    ),
		
	//xw充值状态 不能这么取,去查动态
	'rechargeStatus'=>array(
		'SUCCESS' => '支付成功',
		'PENDDING' => '支付中',
		'FAIL' => '支付失败',
		'ERROR' => '支付错误'
	),
	
	//交易确认明细
	'transActionType'=>array(
		'SUCCESS' => '成功',
		'FAIL' => '失败',
		'INIT' => '初始化',
		'ERROR' => '异常',
		'ACCEPT' => '已受理',
		'PROCESSING' => '处理中'
	),
	
	//预处理结果
	'proccessType'=>array(
		'INIT' => '初始化',
		'FREEZED' => '冻结成功',
		'UNFREEZED' => '全部解冻',
		'FAIL' => '冻结失败',
		'ERROR' => '异常',
	),

	//2回调类型
	'responseType'=>array(
		'CALLBACK' => ' [浏览器返回] ',
		'NOTIFY' => ' [服务器异步通知] '
	),
		
	//3证件类型
	'idCardType' => array(
		'PRC_ID' => '身份证',
		'PASSPORT' => '护照',
		'COMPATRIOTS_CARD' => '澳台通行证',
		'PERMANENT_RESIDENCE' => '外国人永久居留证',
	),
			
	//4用户角色 
	'userRoleType' => array(
		'GUARANTEECORP' => '担保机构',
		'INVESTOR' => '投资人',
		'BORROWERS' => '借款人',
		'INTERMEDIATOR' => '居间人',
		'COLLABORATOR' => '合作机构',
		'SUPPLIER' => '供应商',
		'PLATFORM_COMPENSATORY' => '平台代偿账户',
		'PLATFORM_MARKETING' => '平台营销款账户',
		'PLATFORM_PROFIT' => '平台分润账户',
		'PLATFORM_INCOME' => '平台收入账户',
		'PLATFORM_INTEREST' => '平台派息账户',
		'PLATFORM_ALTERNATIVE_RECHARGE' => '平台代充值账户',
		'PLATFORM_FUNDS_TRANSFER' => '平台总账户',
		'PLATFORM_URGENT' => '垫资账户',
	),
	

	//7审核状态
	'auditStatus' => array(
		'AUDIT' => '审核中',
		'PASSED' => '审核通过',
		'BACK' => '审核回退',
		'REFUSED' => '审核拒绝',
	),
		
	//8预处理业务类型
	'bizType'=>array(
			'TENDER' => '投标',
			'REPAYMENT' => '还款',
			'CREDIT_ASSIGNMENT' => '债权认购',
			'COMPENSATORY' => '代偿',
	),	
		
	//9交易类型
	'transType'=>array(
			'TENDER' => '投标',
			'REPAYMENT' => '还款',
			'CREDIT_ASSIGNMENT' => '债权认购',
			'COMPENSATORY' => '直接代偿',
			'INDIRECT_COMPENSATORY' => '间接代偿',
			'PLATFORM_INDEPENDENT_PROFIT' => '独立分润',
			'MARKETING' => '平台营销款',
			'PLATFORM_SERVICE_DEDUCT' => '收费',
			'INTELLIGENT_APPEND' => '批量投标追加',
			'FUNDS_TRANSFER' => '平台资金划拨',
	),
	
	//10业务类型
	'businessType'=>array(
			'TENDER' => '投标确认',
			'REPAYMENT' => '还款',
			'CREDIT_ASSIGNMENT' => '债权认购',
			'COMPENSATORY' => '代偿',
			'COMPENSATORY_REPAYMENT' => '还代偿款',
			'PLATFORM_INDEPENDENT_PROFIT' => '独立分润',
			'MARKETING' => '营销红包',
			'INTEREST' => '派息',
			'ALTERNATIVE_RECHARGE' => '代充值',
			'INTEREST_REPAYMENT' => '还派息款',
			'COMMISSION' => '佣金',
			'PROFIT' => '关联分润',
			'APPEND_FREEZE' => '追加冻结',
			'DEDUCT' => '平台服务费',
			'FUNDS_TRANSFER' => '平台资金划拨',
				
	),
		
	//15鉴权通过类型
	'accessType' => array(
			'FULL_CHECKED' => '四要素验证通过',
			'NOT_AUTH' => '未鉴权',
			'AUDIT_AUTH' => '特殊用户认证',
	),
		
	//22提现交易状态
	'withdrawType'=> array(
			'CONFIRMING' => '待确认',
			'ACCEPT' => '已受理',
			'REMITING' => '出款中',
			'SUCCESS' => '提现成功',
			'FAIL' => '提现失败',
			'ACCEPT_FAIL' => '受理失败',
	),
		
	//27用户授权列表
	'authList' => array(
		'TENDER' => '自动投标',
		'REPAYMENT' => '自动还款',
		'CREDIT_ASSIGNMENT' => '自动债权认购',
		'COMPENSATORY' => '自动代偿',
		'WITHDRAW' => '自动提现',
		'RECHARGE' => '自动充值',
	),
		
	//28 鉴权验证类型
	'authcheckType' => array(
		'LIMIT' => '强制四要素',
	),
		
		

);