<?php
return [
    // 充值
    'recharge' => [
        'error_msg' => '非常抱歉，新网银行维护中，该功能暂停使用，敬请谅解。',
        'app_error_msg' => '尊敬的爱亲，
您好！为了给您提供更好的服务，新网银行将于9月1日进行网络升级迁移，届时新网存管系统将暂停服务，停服时间2018/9/01 19:50 - 2018/9/02 00:00。
届时将会影响您充值、提现、认购和开户等操作，如涉及以上操作，请您避开此维护时间段，给您带来的不便深表歉意！
',
        'actions' => [
            [
                'url' => '/user/ajax/KjPcode',
                'desc' => 'PC快捷充值 获取短信验证码接口',
                'type' => 'json'
            ],
            [
                'url' => '/user/ajax/DoRecharge',
                'desc' => 'PC快捷充值 充值提交接口',
                'type' => 'json'
            ],
            [
                'url' => '/user/main/RechargePost',
                'desc' => 'PC网银充值 提交接口',
                'type' => 'form'
            ],
            [
                'url' => '/wap/user/rechargeInit',
                'desc' => 'H5快捷充值 获取短信验证码接口',
                'type' => 'json'
            ],
            [
                'url' => '/wap/user/doRecharge',
                'desc' => 'H5快捷充值 充值提交接口',
                'type' => 'json'
            ],
			[
				'url' => '/newwap/user/doRecharge',
				'desc' => 'H5快捷充值 充值提交接口',
				'type' => 'json'
			],
            [
                'url' => '/api/user/doRecharge',
                'desc' => 'APP服务器端支付：手机充值提交',
                'type' => 'json'
            ],
            [
                'url' => '/api/user/otherPay',
                'desc' => 'APP其他支付初始化（微信）',
                'type' => 'json'
            ],
            [
                'url' => '/api/user/rechargeInit',
                'desc' => 'APP手机充值初始化接口',
                'type' => 'json'
            ],
            [
                'url' => '/mt/v3/investment/userAuth',
                'desc' => 'app获取用户充值提现信息',
                'type' => 'json'
            ],

        ]
    ],
    // 提现
    'withdraw' => [
		'error_msg' => '非常抱歉，新网银行维护中，该功能暂停使用，敬请谅解。',
        'app_error_msg' => '尊敬的爱亲，
您好！为了给您提供更好的服务，新网银行将于9月1日进行网络升级迁移，届时新网存管系统将暂停服务，停服时间2018/9/01 19:50 - 2018/9/02 00:00。
届时将会影响您充值、提现、认购和开户等操作，如涉及以上操作，请您避开此维护时间段，给您带来的不便深表歉意！
',
        'actions' => [
            [
                'url' => '/user/ajax/withDrawSafeCard',
                'desc' => 'PC快捷卡 提现提交接口',
                'type' => 'json'
            ],
            [
                'url' => '/user/ajax/withDraw',
                'desc' => 'PC提现卡 提现提交接口',
                'type' => 'json'
            ],
            [
                'url' => '/user/ajax/withDrawCancel',
                'desc' => 'PC 取消提现接口',
                'type' => 'json'
            ],
            [
                'url' => '/wap/user/withDrawApi',
                'desc' => 'H5快捷卡 提现提交接口',
                'type' => 'json'
            ],
			[
				'url' => '/newwap/user/withDrawApi',
				'desc' => 'H5快捷卡 提现提交接口',
				'type' => 'json'
			],
            [
                'url' => '/api/user/withDraw',
                'desc' => 'APP提现接口',
                'type' => 'json'
            ],
            [
                'url' => '/api/user/withdrawCancel',
                'desc' => 'APP取消提现接口',
                'type' => 'json'
            ]
        ]
    ],
    // 绑卡
    'bindCard' => [
		'error_msg' => '非常抱歉，新网银行维护中，该功能暂停使用，敬请谅解。',
        'app_error_msg' => '尊敬的爱亲，
您好！为了给您提供更好的服务，新网银行将于9月1日进行网络升级迁移，届时新网存管系统将暂停服务，停服时间2018/9/01 19:50 - 2018/9/02 00:00。
届时将会影响您充值、提现、认购和开户等操作，如涉及以上操作，请您避开此维护时间段，给您带来的不便深表歉意！
',
        'actions' => [
            [
                'url' => '/user/bankCardAjax/PcBindCardVerify',
                'desc' => 'PC快捷卡 绑卡接口',
                'type' => 'json'
            ],
            [
                'url' => '/user/bankCardAjax/PcBindCard',
                'desc' => 'PC快捷卡 绑卡获取短信接口',
                'type' => 'json'
            ],
            [
                'url' => '/user/bankCardAjax/getSetBankCardCode',
                'desc' => 'PC提现卡 绑卡获取短信接口',
                'type' => 'json'
            ],
            [
                'url' => '/user/bankCardAjax/addBankCard',
                'desc' => 'PC提现卡 绑卡接口',
                'type' => 'json'
            ],
            [
                'url' => '/user/bankCardAjax/PhoneValidcode',
                'desc' => 'PC修改快捷卡获取短信接口',
                'type' => 'json'
            ],
            [
                'url' => '/user/bankCardAjax/updateSafeBankCard',
                'desc' => 'PC修改快捷卡接口',
                'type' => 'json'
            ],
            [
                'url' => '/user/bankCardAjax/modifyBankCard',
                'desc' => 'PC提现卡 修改绑卡信息',
                'type' => 'json'
            ],
            [
                'url' => '/user/bankCardAjax/delBankCard',
                'desc' => 'PC提现卡 删除银行卡接口',
                'type' => 'json'
            ],
            [
                'url' => '/api/account/bindCard',
                'desc' => 'APP 绑定银行卡oldCards',
                'type' => 'json'
            ],
            [
                'url' => '/api/account/bindCardVerify',
                'desc' => 'APP 绑定银行卡验证',
                'type' => 'json'
            ],
            [
                'url' => '/api/account/setSafeCard',
                'desc' => 'APP 设置安全银行卡',
                'type' => 'json'
            ],
            [
                'url' => '/api/account/upgradeSafeCard',
                'desc' => 'APP 升级老卡为安全卡',
                'type' => 'json'
            ],
            [
                'url' => '/api/user/bankBranchCard',
                'desc' => 'APP更新银行卡分支行数据接口',
                'type' => 'json'
            ],
            [
                'url' => '/wap/bank/bindCard',
                'desc' => 'H5 绑定快捷卡发送短信接口',
                'type' => 'json'
            ],

			[
				'url' => '/newwap/newBank/bindCard',
				'desc' => 'H5 绑定快捷卡接口',
				'type' => 'json'
			],
            [
                'url' => '/wap/bank/bindCardVerify',
                'desc' => 'H5 绑定快捷卡接口',
                'type' => 'json'
            ],
            [
                'url' => '/wap/user/bankBranchCard',
                'desc' => 'H5 修改快捷卡分支行接口',
                'type' => 'json'
            ]
        ]
    ],
    // 债权转让和认购
    'debt' => [
		'error_msg' => '非常抱歉，新网银行维护中，该功能暂停使用，敬请谅解。',
        'app_error_msg' => '尊敬的爱亲，
您好！为了给您提供更好的服务，新网银行将于9月1日进行网络升级迁移，届时新网存管系统将暂停服务，停服时间2018/9/01 19:50 - 2018/9/02 00:00。
届时将会影响您充值、提现、认购和开户等操作，如涉及以上操作，请您避开此维护时间段，给您带来的不便深表歉意！
',
        'actions' => [
            [
                'url' => '/user/ajax/newDebt',
                'desc' => 'PC 债权转让接口',
                'type' => 'json'
            ],
            [
                'url' => '/api/debt/confirmSellDebt',
                'desc' => 'APP 出售债权接口',
                'type' => 'json'
            ],[
                'url' => '/mt/v5/debt/confirmSellDebtSub',
                'desc' => 'APP 出售债权接口',
                'type' => 'json'
            ],
            [
                'url' => '/invest/ajax/debtCommit',
                'desc' => 'PC 购买债权提交接口',
                'type' => 'json'
            ],
            [
                'url' => '/wap/debt/debt',
                'desc' => 'H5 购买债权提交接口',
                'type' => 'json'
            ],
            [
                'url' => '/api/debt/debt',
                'desc' => 'APP 购买债权提交接口',
                'type' => 'json'
            ],[
                'url' => '/api/debt/debtPrepare',
                'desc' => 'APP 购买债权提交接口',
                'type' => 'json'
            ],
            [
                'url' => '/user/ajax/canceldebt',
                'desc' => 'PC 取消债权转让接口',
                'type' => 'json'
            ],
            [
                'url' => '/api/debt/cancelDebt',
                'desc' => 'APP 取消出售债权接口',
                'type' => 'json'
            ],[
                'url' => '/mt/v3/base/bankAddress',
                'desc' => 'APP 新网相关接口',
                'type' => 'json'
            ],
			[
				'url' => '/user/newBank/detPreTransaction',
				'desc' => 'PC 购买省心债权提交接口',
				'type' => 'json'
			],
			[
				'url' => '/user/newBank/wisdomDetPreTransaction',
				'desc' => 'PC 购买阳光债权提交接口',
				'type' => 'json'
			],
			[
				'url' => '/user/newBank/wiseDetPreTransaction',
				'desc' => 'PC 购买集合散标债权提交接口',
				'type' => 'json'
			],
			[
				'url' => '/user/newBank/wisdomDebtVerifyPassword',
				'desc' => 'PC 转让债权提交接口',
				'type' => 'json'
			],
            [
                'url' => '/mt/v3/investment/quitWisePlan',
                'desc' => 'app 智选计划退出',
                'type' => 'json'
            ],
            [
                'url' => '/user/ajax/ExitPlan',
                'desc' => 'PC 智选计划退出',
                'type' => 'json'
            ],
            [
                'url' => '/mt/v3/investment/cancelReserveWisePlan',
                'desc' => 'app 智选计划取消预约',
                'type' => 'json'
            ],


        ]
    ],
    // 直投
    'invest' => [
		'error_msg' => '非常抱歉，新网银行维护中，该功能暂停使用，敬请谅解。',
        'app_error_msg' => '尊敬的爱亲，
您好！为了给您提供更好的服务，新网银行将于9月1日进行网络升级迁移，届时新网存管系统将暂停服务，停服时间2018/9/01 19:50 - 2018/9/02 00:00。
届时将会影响您充值、提现、认购和开户等操作，如涉及以上操作，请您避开此维护时间段，给您带来的不便深表歉意！
',
        'actions' => [
            [
                'url' => '/invest/confirm/tender',
                'desc' => 'PC 直投提交接口',
                'type' => 'json'
            ],
            [
                'url' => '/wap/invest/tenderPost',
                'desc' => 'H5 直投投资接口',
                'type' => 'json'
            ],
            [
                'url' => '/api/invest/tenderPost',
                'desc' => 'APP 投资提交接口',
                'type' => 'json'
            ],
            [
                'url' => '/mt/v5/investment/wisePlanConfirm',
                'desc' => 'APP 智选计划投资接口',
                'type' => 'json'
            ],
            [
                'url' => '/wisdom/confirm/investPlan',
                'desc' => 'PC 智选计划投资接口',
                'type' => 'json'
            ],
            [
                'url' => '/api/invest/tenderPrepare',
                'desc' => 'APP 投资接口',
                'type' => 'json'
            ],
        ]
    ],
    // 实名认证
    'realAuth' => [
		'error_msg' => '非常抱歉，新网银行维护中，该功能暂停使用，敬请谅解。',
        'app_error_msg' => '尊敬的爱亲，
您好！为了给您提供更好的服务，新网银行将于9月1日进行网络升级迁移，届时新网存管系统将暂停服务，停服时间2018/9/01 19:50 - 2018/9/02 00:00。
届时将会影响您充值、提现、认购和开户等操作，如涉及以上操作，请您避开此维护时间段，给您带来的不便深表歉意！
',
        'actions' => [
            [
                'url' => '/user/safeAjax/realnameAuth',
                'desc' => 'PC实名认证接口',
                'type' => 'json'
            ],
            [
                'url' => '/user/safeAjax/realnamePicAuth',
                'desc' => 'PC实名上传身份证方式验证接口',
                'type' => 'json'
            ],
            [
                'url' => '/api/user/realnameCheck',
                'desc' => 'APP用户实名认证接口',
                'type' => 'json'
            ],
            [
                'url' => '/wap/user/realnameCheck',
                'desc' => 'H5实名认证接口',
                'type' => 'json'
            ],
            [
                'url' => 'newuser/ajax/RealnameCheck',
                'desc' => '论坛用-实名认证接口',
                'type' => 'jsonp'
            ],
            [
                'url' => '/newuser/ajax/RealnameCheckPic',
                'desc' => '论坛用-实名认证上传认证接口',
                'type' => 'jsonp'
            ]
        ]
    ],
    // 修改手机号
    'editPhone' => [
		'error_msg' => '非常抱歉，新网银行维护中，该功能暂停使用，敬请谅解。',
        'app_error_msg' => '尊敬的爱亲，
您好！为了给您提供更好的服务，新网银行将于9月1日进行网络升级迁移，届时新网存管系统将暂停服务，停服时间2018/9/01 19:50 - 2018/9/02 00:00。
届时将会影响您充值、提现、认购和开户等操作，如涉及以上操作，请您避开此维护时间段，给您带来的不便深表歉意！
',
        'actions' => [
            [
                'url' => '/user/safeAjax/getModifyPhoneAuthCode',
                'desc' => 'PC 修改手机号获取短信验证码接口',
                'type' => 'json'
            ],
            [
                'url' => '/user/safeAjax/modifyPhoneAuth',
                'desc' => 'PC 修改手机号提交接口',
                'type' => 'json'
            ],
            [
                'url' => '/api/user/updateMobile',
                'desc' => 'APP 修改手机号提交接口',
                'type' => 'json'
            ]
        ]
    ],
    // 注册
    'register' => [
        'error_msg' => '非常抱歉，银行存管上线期间该功能暂停使用，敬请谅解。',
        'app_error_msg' => '尊敬的爱亲，
您好！为了给您提供更好的服务，新网银行将于9月1日进行网络升级迁移，届时新网存管系统将暂停服务，停服时间2018/9/01 19:50 - 2018/9/02 00:00。
届时将会影响您充值、提现、认购和开户等操作，如涉及以上操作，请您避开此维护时间段，给您带来的不便深表歉意！
',
        'actions' => [
            [
                'url' => '/apiService/phone/getSmsVcode',
                'desc' => 'PC 注册获取短信验证码',
                'type' => 'json'
            ],
            [
                'url' => '/user/ajax/register',
                'desc' => 'PC站 注册提交接口',
                'type' => 'json'
            ],
            [
                'url' => '/newuser/RAjax/nreg',
                'desc' => 'PC SEM注册提交接口',
                'type' => 'json'
            ],
            [
                'url' => '/wap/user/newreg',
                'desc' => 'H5站 + SEM注册提交接口',
                'type' => 'json'
            ],
            [
                'url' => '/api/user/verifyAndSet',
                'desc' => 'APP 注册提交接口',
                'type' => 'json'
            ],
        ]
    ],
    // 修改支付密码
    'editPaypwd' => [
		'error_msg' => '非常抱歉，新网银行维护中，该功能暂停使用，敬请谅解。',
        'app_error_msg' => '尊敬的爱亲，
您好！为了给您提供更好的服务，新网银行将于9月1日进行网络升级迁移，届时新网存管系统将暂停服务，停服时间2018/9/01 19:50 - 2018/9/02 00:00。
届时将会影响您充值、提现、认购和开户等操作，如涉及以上操作，请您避开此维护时间段，给您带来的不便深表歉意！
',
        'actions' => [
			[
				'url' => '/user/ajax/changeTradePwd',
				'desc' => 'PC 修改支付密码接口',
				'type' => 'json'
			],
            [
                'url' => '/wap/user/UpdatePaymentPwd',
                'desc' => 'H5 修改支付密码提交接口',
                'type' => 'json'
            ],
			[
				'url' => '/wap/user/UpdatePaymentPwd',
				'desc' => 'H5 修改支付密码提交接口',
				'type' => 'json'
			],
            [
                'url' => '/newwap/newBank/editTradePwd',
                'desc' => 'H5 重置支付密码提交接口',
                'type' => 'json'
            ],
            [
                'url' => '/user/safeAjax/modifyPayPwd',
                'desc' => 'PC 修改支付密码提交接口',
                'type' => 'json'
            ],
            [
                'url' => '/user/ajax/updatePaypwd',
                'desc' => 'PC 设置支付密码提交接口',
                'type' => 'json'
            ],
            [
                'url' => '/user/safeAjax/setPayPwd',
                'desc' => 'PC 设置支付密码提交接口',
                'type' => 'json'
            ],
            [
                'url' => '/user/safeAjax/forgetPayPwdTwo',
                'desc' => 'PC 找回支付密码提交接口',
                'type' => 'json'
            ],
            [
                'url' => '/api/user/verifyCardID',
                'desc' => 'APP 找回支付密码前置验证',
                'type' => 'json'
            ],
            [
                'url' => '/api/user/payPwdValidCode',
                'desc' => 'APP 找回支付密码短信请求',
                'type' => 'json'
            ],
            [
                'url' => '/api/user/updatePaymentPwd',
                'desc' => 'APP 找回支付密码提交接口',
                'type' => 'json'
            ]
        ]
    ],
	// 开户
	'openAccount' => [
		'error_msg' => '非常抱歉，新网银行维护中，该功能暂停使用，敬请谅解。',
		'app_error_msg' => '尊敬的爱亲，
您好！为了给您提供更好的服务，新网银行将于9月1日进行网络升级迁移，届时新网存管系统将暂停服务，停服时间2018/9/01 19:50 - 2018/9/02 00:00。
届时将会影响您充值、提现、认购和开户等操作，如涉及以上操作，请您避开此维护时间段，给您带来的不便深表歉意！
',
		'actions' => [
			[
				'url' => '/newwap/newBank/openAccount',
				'desc' => 'h5 新网银行开通账户',
				'type' => 'json'
			],
			[
				'url' => '/user/newBank/openAccount',
				'desc' => 'PC 新网银行开通账户',
				'type' => 'json'
			],
		]
	]
    
];
