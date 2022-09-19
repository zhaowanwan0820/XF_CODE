<?php
/**
 * Created by PhpStorm.
 * The configuration file of audit log query tool
 * User: JU
 * Date: 2016/6/23
 * Time: 17:05
 */
return array(
    //可选展示项
    'option' => array(
        'resource' => '行为明细',
        'client_ip' => 'ip',
        'status' => '结果状态'
    ),
    //平台
    'system' => array(
        '' => '全部',
        'web/pc' => 'PC',
        'web/wap' => 'WAP',
        'app/ios' => 'iOS',
        'app/android' => 'Android',
    ),

    //用户行为
    'action' => array(
        '' => '全部',
        'login' => '登录',
        'logout' => '登出',
        'recharge' => '快捷充值',
        'bindcard' => '绑卡'
    ),

    //行为明细展示
    'resource' => array(
        'user/app_recharge/step1' => '快捷充值第一步',
        'user/money_account/recharge/sendSms' => '快捷充值第一步',
        'user/money_account/recharge/reSendSms' => '快捷充值第一步',
        'user/app_recharge/step2' => '快捷充值第二步',
        'user/money_account/recharge/commit' => '快捷充值第二步',
        'user/app_recharge/step3' => '快捷充值第三步',
        'user/money_account/recharge/getResult' => '快捷充值第三步',
        'user/money_account/recharge' => '网银+渠道充值跳转',
        'user/app_bindcard/step1' => '绑卡第一步',
        'user/app_bindcard/step2' => '绑卡第二步',
        'user/login' => '登入',
        'user/logout' => '登出',
    ),

    //行为明细下拉框
    'resource_select' => array(
        '' => '全部',
        'user/app_recharge/step1,user/money_account/recharge/sendSms,user/money_account/recharge/reSendSms' => '快捷充值第一步',
        'user/app_recharge/step2,user/money_account/recharge/commit' => '快捷充值第二步',
        'user/app_bindcard/step1' => '绑卡第一步',
        'user/app_bindcard/step2' => '绑卡第二步',
        'user/login' => '登入',
        'user/logout' => '登出',
    )
);