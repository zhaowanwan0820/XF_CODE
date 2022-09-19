<?php

/**
 * Class BlockCC
 * 防暴力破解基础组件
 *
 * Usage:
 *
 * 1， 制定具体防爆策略后新增 class implements SuperBlockCC 或 extends BlockCCByRate 实现，在 BlockCC class constructor 中加入其映射；
 * 2， 在 itzlib/config/BlockCCConfig.php 中加入策略的配置；
 * 3， 在 /protected/class/BlockCCFilter.php preFilter 中加入对策略的过滤；
 * 4， 根据需要在业务逻辑中调用 BlockCC::getInstance()->getNew( A )->CheckCC( B );
 *     A/B 的取值参考 itzlib/config/BlockCCConfig.php 中的配置。
 *
 *
 * Class BlockCC: 对外统一调用类
 * Interface SuperBlockCC: 防 CC 通用接口
 *
 * Class BlockCCByRate: 根据速率限制基础类 {
 * 		Class BlockCCByIpAction: 根据 IP + Action 防 CC 具体实现
 *   	Class BlockCCByUserKey(原 Class PreventBruteForce): 根据 User + Key 防 CC 具体实现
 * }
 *
 * 可根据不同需求再具体实现防 CC 的其他类，比如只针对 User 的防爆破
 *
 *
 * @link http://confluence.xxx.com/pages/viewpage.action?pageId=72056867
 * @author ThomasChan <chenjunhao@xxx.com>
 *
 */

/**
 * @BlockCC config
 * 防爆破的配置文件
 *
 * Usage:
 * 配置文件的结构为：
 *     策略名 A ：[
 *     		可选参数 ClassName： 不写默认就是 BlockCCBy  + 策略名 A
 *     		action 路径： [
 *     			具体针对 action 的策略名 B
 *     		]
 *     ]
 *
 * 调用使用时 BlockCC::getInstance()->getNew( A )->CheckCC( B )
 *
 * 参考：
 * 1. 速率的取值为整数， -1 表示不限制
 *
 * @author Thomaschan <chenjunhao@xxx.com>
 * @link http://confluence.xxx.com/pages/viewpage.action?pageId=72056867
 *
 */

return [

    'IpAction' => [
        '/api/user/getSmsVcode' => [
            'total' => [
                'min' => -1,
                'hour' => -1,
                'day' => -1,
            ],
            'error' => [
                'min' => -1,
                'hour' => 20,
                'day' => 200,
            ],
        ],
        '/api/user/wapLogin' => [
            'total' => [
                'min' => -1,
                'hour' => -1,
                'day' => -1,
            ],
            'error' => [
                'min' => 30,
                'hour' => 100,
                'day' => 300,
            ],
        ],
        '/api/user/wapRegister' => [
            'total' => [
                'min' => -1,
                'hour' => -1,
                'day' => -1,
            ],
            'error' => [
                'min' => 20,
                'hour' => 50,
                'day' => 300,
            ],
        ],
        // 'ClassName' => 'BlockCCByIpAction',
        '/newuser/rAjax/login' => [
            'total' => [
                'min' => -1,
                'hour' => -1,
                'day' => -1,
            ],
            'error' => [
                'min' => 20,
                'hour' => 50,
                'day' => 300,
            ],
        ],
        '/user/ajax/login' => [
            'total' => [
                'min' => -1,
                'hour' => -1,
                'day' => -1,
            ],
            'error' => [
                'min' => 20,
                'hour' => 50,
                'day' => 300,
            ],
        ],
        '/api/user/login' => [
            'total' => [
                'min' => -1,
                'hour' => -1,
                'day' => -1,
            ],
            'error' => [
                'min' => 20,
                'hour' => 50,
                'day' => 100,
            ],
        ],
        //找回登录密码
        '/api/user/getpwd' => [
            'total' => [
                'min' => -1,
                'hour' => -1,
                'day' => 200,
            ],
            'error' => [
                'min' => -1,
                'hour' => -1,
                'day' => -1,
            ],
        ],
        '/wap/user/loginApi' => [
            'total' => [
                'min' => -1,
                'hour' => -1,
                'day' => -1,
            ],
            'error' => [
                'min' => 20,
                'hour' => 50,
                'day' => 300,
            ],
        ],
        '/wap/user/loginFromWxlottery' => [
            'total' => [
                'min' => -1,
                'hour' => -1,
                'day' => -1,
            ],
            'error' => [
                'min' => 20,
                'hour' => 50,
                'day' => 100,
            ],
        ],
        '/newuser/rAjax/nreg' => [
            'total' => [
                'min' => -1,
                'hour' => -1,
                'day' => 100,
            ]
        ],
        '/apiService/phone/getSmsVcode' => [
            'total' => [
                'min' => -1,
                'hour' => 10,
                'day' => 50,
            ],
            'error' => [
                'min' => -1,
                'hour' => 10,
                'day' => 50,
            ],
        ],
        '/api/user/reg' => [
            'total' => [
                'min' => -1,
                'hour' => -1,
                'day' => 200,
            ],
            'error' => [
                'min' => -1,
                'hour' => -1,
                'day' => -1,
            ],
        ],
        '/mt/v3/user/register' => [
            'total' => [
                'min' => -1,
                'hour' => -1,
                'day' => -1,
            ],
            'error' => [
                'min' => 5,
                'hour' => -1,
                'day' => 100,
            ],
        ],
        '/apiService/phone/isCertified' => [
            'total' => [
                'min' => 10,
                'hour' => -1,
                'day' => 100,
            ],
            'error' => [
                'min' => -1,
                'hour' => -1,
                'day' => -1,
            ],
        ],
        '/apiService/captcha/check' => [
            'total' => [
                'min' => -1,
                'hour' => -1,
                'day' => -1,
            ],
            'error' => [
                'min' => 10,
                'hour' => -1,
                'day' => 100,
            ],
        ],
        '/apiService/captcha/reg' => [
            'total' => [
                'min' => -1,
                'hour' => -1,
                'day' => 12,
            ],
            'error' => [
                'min' => -1,
                'hour' => -1,
                'day' => -1,
            ],
        ],
        '/newuser/ajax/getDebtApr' => [
            'error' => [
                'min' => 50,
                'hour' => -1,
                'day' => -1,
            ],
        ],
        '/user/ajax/getDebtApr' => [
            'error' => [
                'min' => 50,
                'hour' => -1,
                'day' => -1,
            ],
        ],
        '/home/help/like' => [
            'total' => [
                'min' => -1,
                'hour' => -1,
                'day' => 100,
            ],
            'error' => [
                'min' => -1,
                'hour' => -1,
                'day' => -1,
            ],
        ],
        '/event/index/summerplay' => [
            'error' => [
                'min' => 300,
                'hour' => -1,
                'day' => 800,
            ],
        ],
        '/newwap/activity/summerplay' => [
            'error' => [
                'min' => 300,
                'hour' => -1,
                'day' => 800,
            ],
        ],
        //防爆破 后台密码输错5次
        '/default/index/login' => [
            'error' => [
                'min' => -1,
                'hour' => 4,
                'day' => -1,
            ],
        ],
        '/wap/ajax/GetAppointmentVcode' => [
            'total' => [
                'min' => -1,
                'hour' => 60,
                'day' => 600,
            ],
            'error' => [
                'min' => -1,
                'hour' => -1,
                'day' => -1,
            ],
        ],
        '/json/Lx/tenderList' => [
            'total' => [
                'min' => -1,
                'hour' => -1,
                'day' => -1,
            ],
            'error' => [
                'min' => 20,
                'hour' => 100,
                'day' => 300,
            ],
        ],
        '/json/Lx/investedAmount' => [
            'total' => [
                'min' => -1,
                'hour' => -1,
                'day' => -1,
            ],
            'error' => [
                'min' => 20,
                'hour' => 100,
                'day' => 300,
            ],
        ],
    ],

    'UserKey' => [
        // 'ClassName' => 'BlockCCByUserKey',
        // 修改登录密码
        'modify_loginpwd' => [
            'day'    => 5,
            'hour'   => -1,
            'min'    => -1,
        ],
        // 验证登录密码
        'check_paypwd' => [
            'day'    => -1,
            'hour'   => 10,
            'min'    => -1,
        ],
        // 修改支付密码
        'modify_paypwd' => [
            'day'    => 5,
            'hour'   => -1,
            'min'    => -1,
        ],
        // 修改支付密码
        'modify_paypwd_find' => [
            'day'    => 5,
            'hour'   => -1,
            'min'    => -1,
        ],
        // 修改支付密码
        'set_paypwd' => [
            'day'    => 5,
            'hour'   => -1,
            'min'    => -1,
        ],
        // 找回支付密码
        'smsback_send_paypwd_checkcard' => [
            'day'    => 5,
            'hour'   => -1,
            'min'    => -1,
        ],
        'smsback_send_paypwd' => [
            'day'    => 5,
            'hour'   => -1,
            'min'    => -1,
        ],
        'smsback_submit_paypwd' => [
            'day'    => -1,
            'hour'   => 100,
            'min'    => 10,
        ],
        // 修改手机号
        'modify_phone_send_orig' => [
            'day'    => 5,
            'hour'   => -1,
            'min'    => -1,
        ],
        'modify_phone_submit_orig' => [
            'day'    => 5,
            'hour'   => -1,
            'min'    => -1,
        ],
        'modify_phone_send_new' => [
            'day'    => 5,
            'hour'   => -1,
            'min'    => -1,
        ],
        'modify_phone_submit_new' => [
            'day'    => 5,
            'hour'   => -1,
            'min'    => -1,
        ],
        // 找回登录密码
        'smsback_send_loginpwd' => [//发送短信
            'day'    => 5,
            'hour'   => -1,
            'min'    => -1,
        ],
        'smsback_submit_loginpwd' => [//验证短信验证码
            'day'    => 5,
            'hour'   => -1,
            'min'    => -1,
        ],
        'find_login_pwd_check_card' => [//验证身份证号
            'day'    => 5,
            'hour'   => -1,
            'min'    => -1,
        ],
        'app_login_check_device_smssend' => [//移动端登录验证新设备
            'day'    => 5,
            'hour'   => -1,
            'min'    => -1,
        ],
        'app_login_check_device_smssend_check_code' => [//移动端登录验证新设备 验证验证码
            'day'    => 5,
            'hour'   => -1,
            'min'    => -1,
        ],
        'app_device_switch' => [//设备锁开关 验证密码
            'day'    => 5,
            'hour'   => -1,
            'min'    => -1,
        ],
        'funds_submit_check_code' => [//私募项目 验证验证短信
            'day'    => 100,
            'hour'   => -1,
            'min'    => -1,
        ],
        'funds_submit_send_code' => [//私募短信 发送
            'day'    => 100,
            'hour'   => -1,
            'min'    => -1,
        ],
        'find_login_pwd_set_pwd' => [//修改登录密码
            'day'    => 5,
            'hour'   => -1,
            'min'    => -1,
        ],
        // 注册成功后手机认证接口
        'bind_phone_send' => [
            'day'    => 5,
            'hour'   => -1,
            'min'    => -1,
        ],
        'bind_phone_submit' => [
            'day'    => 5,
            'hour'   => -1,
            'min'    => -1,
        ],
        // 获取投标验证码防爆破
        'tender_imgcode_counter' => [
            'day'    => 1000,
            'hour'   => -1,
            'min'    => 20,
        ],
        // 投标开放时间前10分钟防爆破
        'tender_counter' => [
            'day'    => -1,
            'hour'   => -1,
            'min'    => -1,
        ],
        // 投标防爆破
        'tender_post' => [
            'day'    => 240,
            'hour'   => 30,
            'min'    => -1,
        ],
        'tender_post_linghuo' => [
            'day'    => 240,
            'hour'   => 50,
            'min'    => -1,
        ],
        // 预约防爆破
        'reserve_post' => [
            'day'    => 240,
            'hour'   => 30,
            'min'    => -1,
        ],
        // 注册成功后手机认证接口
        'bind_phone_send_newreg' => [
            'day'    => 5,
            'hour'   => -1,
            'min'    => -1,
        ],
        'bind_phone_submit_newreg' => [
            'day'    => 5,
            'hour'   => -1,
            'min'    => -1,
        ],
        'get_reserve_borrow' => [
            'day'    => 20,
            'hour'   => -1,
            'min'    => -1,
        ],
        'save_reserve_oppion' => [
            'day'    => 5,
            'hour'   => -1,
            'min'    => -1,
        ],
        'send_phone_stype_direct' => [
            'day'    => 20,
            'hour'   => -1,
            'min'    => 1,
        ],
        'new_reg_get_sms_vcode' => [
            //'day'	 => 5,
            'day'    => 30,
            'hour'   => -1,
            'min'    => -1,
        ],
        'phone_auth_get_sms_vcode' => [
                'day'    => 2,
                'hour'   => -1,
                'min'    => -1,
            ],
        'modify_phone_auth_get_sms_vcode' => [
                'day'    => 2,
                'hour'   => -1,
                'min'    => -1,
            ],
        'find_pay_pwd_vcode' => [
                'day'    => 2,
                'hour'   => -1,
                'min'    => -1,
            ],
        'newuser_PaymentNotify_AdminRechargeRepair' => [
            'day'    => 500,
            'hour'   => 100,
            'min'    => 10,
        ],
        'newuser_ajax_getvcode_phone' => [
            'day'    => 8,
            'hour'   => -1,
            'min'    => 1,
        ],
        'newuser_ajax_getvcode_user' => [
            'day'    => 8,
            'hour'   => -1,
            'min'    => 1,
        ],
        'cancel_order_send' => [
            'day'    => -1,
            'hour'   => 12,
            'min'    => -1,
        ],
        'activity_summer' =>[
            'day'    => 500,
            'hour'   => -1,
            'min'    => 100,
        ],
        '/newwap/ajax/addSpokesman' => [
            'error' => [
            'min' => 200,
            'hour' => -1,
            'day' => 2000,
            ],
        ],
        '/newwap/ajax/commitSpokesman' => [
            'error' => [
            'min' => 200,
            'hour' => -1,
            'day' => 2000,
            ],
        ],
        'wap_reg_get_sms_vcode' =>[
            'day'    => -1,
            'hour'   => 20,
            'min'    => 200,
        ],
        // 用户 邮箱认证次数限制（防无限制发邮件）
        'newuser_safeAjax_emailAuth_user' =>[
            'day'    => 6,
            'hour'   => -1,
            'min'    => -1,
        ],
        'wap_cancle_funds_get_sms_vcode' => [
            'day'   => -1,
            'hour'  => 20,
            'min'   => 200,
        ],
        'get_sms_vcode:xf_auth_login' => [//授权登录短信验证码
            //'day'	 => 5,
            'day'    => 10,
            'hour'   => -1,
            'min'    => -1,
        ],
        'get_sms_vcode:xf_common_login' => [//登录短信验证码
            //'day'	 => 5,
            'day'    => 10,
            'hour'   => -1,
            'min'    => -1,
        ],

        /**
         ************** iseecapital ****************
         */
        // 发送短信验证码（防无限制发送）
        'isee_user_sendSms' =>[
            'day'    => 18,
            'hour'   => -1,
            'min'    => -1,
        ],
        'isee_appointment_get_sms_vcode' => [
            'day'    => 50,
            'hour'   => -1,
            'min'    => -1,
        ],
    ],

];
