<?php


return array(
    //远程日志服务
    'REMOTE_LOG_SERVER_IP' => 'pmlog2.wxlc.org',
    'REMOTE_LOG_SERVER_PORT' => '55003',

     /*
     * redisDataCache 配置
     */
    'REDIS_DATA_CACHE' => 1, //0:关闭 ,1:打开
    'REDIS_DATA_CACHE_DEBUG' => 0,//0:关闭 ,1:打开
    'FIRSTP2P_CONF_DATACACHE' => 'FIRSTP2P_CONF_DATACACHE', //conf redis_key

/**
 * 系统版本配置
 */

    'APP_NAME'       =>    '网信理财',
    'APP_SUB_VER' => 201911041358,
    'SYSTEM_UPGRADE' => 0,
    'SYSTEM_UPGRAGE_MSG_HTML' => '  尊敬的用户：<br />您好！<br />平台将于2016年4月9日（本周六）凌晨01:00—05:00进行系统升级，期间官网和APP将暂停服务。给您带来的不便，敬请谅解。',// 只允许出现“网信”没有理财
    'SYSTEM_UPGRAGE_MSG_APP' => '平台将于2016年4月9日（本周六）凌晨01:00—05:00进行系统升级，期间官网和APP将暂停服务。给您带来的不便，敬请谅解。',// 只允许出现“网信”没有理财


/**
 * 时区配置
 */

    'DEFAULT_TIMEZONE'=>'PRC',

/**
 * 移动端与支付端交互相关信息配置
 */
    'XFZF_SEC_KEY' => '/trBxSVzokD9vJSY/Fcj6w',
    'XFZF_AES_KEY' => '/trBxSVzokD9vJSY/Fcj6w',
    'XFZF_PAY_CALLBACK' => "https://api.ncfwx.com/account/payNotify",
    'XFZF_PAY_CREATE' => "https://cgw.unitedbank.cn/m-pay/p2pOperate/p2pCreateOrder",
    'XFZF_PAY_CHECK' => "https://cgw.unitedbank.cn/m-pay/p2pOperate/p2pQueryRechargeResult",
    'XFZF_PAY_AUTH_RESULT' => "https://cgw.unitedbank.cn/m-pay/p2pOperate/p2pQueryAuthenticateResult",
    'XFZF_PAY_MERCHANT' => 'M100000003',
    'XFZF_USER_QUERY_PAYPWD' => "https://cgw.unitedbank.cn/m-pay/p2pOperate/p2pQueryPayPasswd",
    'XFZF_USER_REGISTER' => "https://cgw.unitedbank.cn/m-pay/p2pOperate/p2pUserRegister",
    'XFZF_PAY_BANKINFO' => "https://cgw.unitedbank.cn/m-pay/p2pOperate/p2pQueryUserBindBankCard",
    'XFZF_BANKCARD_QUERY' => "https://cgw.unitedbank.cn/m-pay/p2pTransferOperate/p2pQueryTransferInfo",
    'XFZF_BANKCARD_QUERY_BY_ORDERID' => 'https://cgw.unitedbank.cn/m-pay/p2pTransferOperate/p2pQueryTransferRecordInfo',

    //基金配置
    'FUND_SEC_KEY' => 'MDF4RmpAISgjQCopKSEkJg==',
    'FUND_AES_KEY' => 'MDF4RmpAISgjQCopKSEkJg==',

    // 网信钱包交互相关信息配置
    'BONUS_SEC_KEY' => 'YKkFfyBIanXGX3jbbPYLCA==',
    'BONUS_AES_KEY' => '5kmwig+oSpROBDTRoXb2Vw==',
    'BONUS_MERCHANT_ID' => '10010',

/**
 * id5 身份证认证配置
 */
    'is_id5'        => '0',  // 是否打开身份认证
    'id5_url'       => 'http://gboss.id5.cn/services/QueryValidatorServices?wsdl',
    'id5_user'      => 'dzwxjr123',
    'id5_passwd'    => 'dzwxjr123_5_8C!K!S',
    'id5_key'       => '12345678',  // 加解密时使用
    'id5_iv'        => '12345678',

    /**
     * 上海爱金身份证认证配置
     */
    'rzb_url'       => 'http://service.sfxxrz.com/simpleCheck.ashx',
    'rzb_account'   => 'dfkj_admin',
    'rzb_key'       => 'uj4cy9Z8',

    /**
     *  公安部身份证查询配置
     */
    'license' => '?v?zQ8(WE8?[7DF2+$cj;_M1^Z8n?o?g?k?h?o=oR<QGF4)E7)/EG8ZJ*e)sRq_ob80l?z/k=<?va=LnXua3@gKr_y?x?vb<Fqc5CkToEsBbb=?x?v]wNm^eHd?x%l?f8.-o?.=x&/EgKvIzNa?x?vBu_eGg[[?x:f?f?h?h1/?v?jUdPrQrAn?x?vaL[;`8Vl?xQNcn/A2#A4WX9*R:7va$?f-[?.?v?jb>YkEdQq:b?v[sc1bX@;aMSi\\\\g]t5sKn?g9.?g*u=d.l?jRcTq\\\\haCcBVtHyHd;n?vQrbC?j1h?v?jJ[Pv\\\\t^dYjDaLaMc?x*wXsXoOoKo[lTeSwTo_kDcMc?x?vb3AwYjOs?x?g.[?g$w?va{Kf^/PtJg0g+e?jRxDxA;NrJvCzWlPtLc_tSi)e?v?jWub0Lhc5JbSzb6/k',

    'bd_activity' => array(
        'YiShang' => array(
            'url_getAward' => 'http://www.1shang.com/orders/getAward',
            'item' => array(
                0 => array(
                    'name' => '电信全国200M',
                    'prizeId' => '200',
                    'userId' => '162',
                    'prizePriceTypeId' => '194',
                    'orderId' => '2453',
                    'key' => 'yZ#jF7B!Gfxfbd?NYGstIj3?5XydFI$8',
                ),
                'Youbao15' => array(
                    'name' => '友宝15元提货码',
                    'prizeId' => '373',
                    'userId' => '162',
                    'prizePriceTypeId' => '367',
                    'orderId' => '2504',
                    'key' => 'yZ#jF7B!Gfxfbd?NYGstIj3?5XydFI$8',
                ),
                'CPA30' => array(
                    'name' => 'CPA30元礼包',
                    'prizeId' => '372',
                    'userId' => '162',
                    'prizePriceTypeId' => '366',
                    'orderId' => '2504',
                    'key' => 'yZ#jF7B!Gfxfbd?NYGstIj3?5XydFI$8',
                ),
                'Dixintong' => array(
                    'name' => 'CPA30元礼包',
                    'prizeId' => '372',
                    'userId' => '162',
                    'prizePriceTypeId' => '366',
                    'orderId' => '2558',
                    'key' => 'yZ#jF7B!Gfxfbd?NYGstIj3?5XydFI$8',
                ),
            )
        ),
    ),

    /**
     * O2O API支持
     */
    'o2o_api' => array(
        'prizeUrl' => 'http://api.firstp2p.com/gift/unpickList',
    ),
    /**
     * 文件存储器配置
     */
    'vfs_ftp'   => array(
        'ftp_host'=>'ftp.wxlc.org',
        'ftp_username'=>'firstp2p',
        'ftp_password'=>'firstp2p',
    ),

/**
 * 系统包路径
 */
    'IMPORT_PATH'   => array(
        'libs'  =>array(
            APP_ROOT_PATH.'system/',
            APP_ROOT_PATH.'libs/',
        ),
        'app'   =>array(
            APP_ROOT_PATH.'app/Lib/',
        ),
        'module'=>array(
            APP_ROOT_PATH.'app/Lib/module/',
        ),
    ),


/**
 * 允许的module
 *
 */
    'MODULE_ALLOW'  => array(
        'upload','aboutfee', 'aboutlaws', 'aboutp2p', 'acate', 'agency', 'ajax', 'article', 'avatar', 'baijin', 'belender', 'borrow', 'cate', 'coupon', 'daren', 'deal', 'deals', 'exchange', 'goods', 'guarantee',
        'help', 'helpcenter', 'huiying', 'huodong', 'link', 'mall', 'mobile', 'msg', 'notice',
        'op', 'payment', 'preset', 'put_p2p_data', 'rec', 'score', 'space', 'ss', 'sys', 'tool', 'topic',
        'uc_account', 'uc_autobid', 'uc_center', 'uc_collect', 'uc_consignee', 'uc_coupon', 'uc_credit', 'uc_deal',
        'uc_earnings', 'uc_invest', 'uc_invite', 'uc_medal', 'uc_message', 'uc_money', 'uc_msg', 'uc_order', 'uc_topic', 'uc_voucher', 'url', 'usagetip', 'user', 'vote','api',
    ),


/**
 * 站点信息
 * 针对不同域名的站点信息匹配，因为是随环境+域名不同的变量，所以被放在 env_*.conf.php 中
 *
 */

    'TEMPLATE_LIST'   => array(
        '大额业务'=>'53',
        '大额业务（临时停用）' => 56,
        '主站'=>'17',
        '商户通'=>'52',
        '艺金融'=>'43',
        '荣信汇'=>'36',
        'mulandaicn'=>'5',
        '小贷' => 65,
        '智多新底层（大额）'=>'41',
        '智多新底层（网贷）'=>'40',
        '随鑫约（大额）' => 60,
        '企业金融'=>'21',
        '开放平台定制标'=>'44',
        '普通标(3个月及以上)' => '14',
        '交易所（临时停用）'=>'45',

        '哈哈财神单独上标' => 64,
        '代销平台(3个月及以下)' => 63,
        '代销平台(3个月以上)' => 68,
        '普通标(3个月以下)' => 55,
        '通知贷'=>'39',
        '新手标'=>'37',
        '公益标'=>'38',
        'diyifangdai_alone'=>'22',
        'diandang_alone'=>'23',
        'esp2p_alone'=>'32',
        'chanrongdai_alone'=>'47',
        'wangailicai_alone' => 58,
        '主站和所有分站'=>'33',
        '消费贷特殊' => 66,
        '普通标_汇赢'=>54,
        'firstp2p'=>'1',
        'diyifangdai'=>'2',
        'mulandai'=>'4',
        'yhp2p'=>'6',
        'unitedmoney'=>'7',
        'zsz'=>'8',
        'quanfeng'=>'9',
        'fortest'=>'10',
        'chedai'=>'11',
        'diandang'=>'12',
        'tianjindai' => '13',
        'shandongdai' => '15',
        'daliandai' => '16',
        'zsz_alone'=>'18',
        'yhp2p_alone'=>'19',
        'unitedmoney_alone'=>'20',
        'tianjindai_alone'=>'24',
        'shenyangdai_alone'=>'25',
        'shandongdai_alone'=>'26',
        'daliandai_alone'=>'27',
        'chedai_alone'=>'28',
        'creditzj'=>'29',
        'creditzj_alone'=>'30',
        'esp2p'=>'31',
        'firstp2p_region'=>'34',
        'ronghua'=>'35',
        'yijinrong'=>'42',
        'chanrongdai'=>'46',
        'shanghaidai'=>'48',
        'shanghaidai_alone'=>'49',
        'all-che-chan'=>'50',
        'shtcapital'=>'51',
        'wangailicai'=> 57,
        'yuegang' => 59,
        'jifubao' => 61,
        '黄金标专用' => 62,
        'caiyitong' => 67,
        'qianguiyouxi' => 69,
        'dajinsuo' => '70',
        'firstp2pcn' => '100',
        'LinYouMoments' => '101401',
        'zhongxin' => '100082',
        'qiyelicai' => '101493',
        'nongdan' => '101864',
        'nongdan_alone' => '101',
    ),

    /**
     * 站点信息
     * 针对不同域名的站点信息匹配，因为是随环境+域名不同的变量，所以被放在 env_*.conf.php 中
     *
     */
    'TEMPLATE_LIST_CN' => array(
        '主站'=>'17',
        '商户通'=>'52',
        '哈哈财神单独上标' => 64,
        'mulandaicn'=>'5',
        '代销平台(3个月及以下)' => 63,
        '代销平台(3个月以上)' => 68,
        '普通标(3个月及以上)' => '14',
        '普通标(3个月以下)' => 55,
        '新手标'=>'37',
        '公益标'=>'38',
        'diyifangdai_alone'=>'22',
        'diandang_alone'=>'23',
        'ronghua_alone'=>'36',
        'yijinrong_alone'=>'43',
        'esp2p_alone'=>'32',
        'chanrongdai_alone'=>'47',
        'wangailicai_alone' => 58,
        '主站和所有分站'=>'33',
        '消费贷特殊' => 66,
        '普通标_汇赢'=>54,
        'firstp2p'=>'1',
        'diyifangdai'=>'2',
        'mulandai'=>'4',
        'yhp2p'=>'6',
        'unitedmoney'=>'7',
        'zsz'=>'8',
        'quanfeng'=>'9',
        'fortest'=>'10',
        'chedai'=>'11',
        'diandang'=>'12',
        'tianjindai' => '13',
        'shandongdai' => '15',
        'daliandai' => '16',
        'zsz_alone'=>'18',
        'yhp2p_alone'=>'19',
        'unitedmoney_alone'=>'20',
        'quanfeng_alone'=>'21',
        'tianjindai_alone'=>'24',
        'shenyangdai_alone'=>'25',
        'shandongdai_alone'=>'26',
        'daliandai_alone'=>'27',
        'chedai_alone'=>'28',
        'creditzj'=>'29',
        'creditzj_alone'=>'30',
        'esp2p'=>'31',

        'firstp2p_region'=>'34',
        'ronghua'=>'35',

        '智多新底层资产(消费贷专用)'=>'40',
        'yijinrong'=>'42',
        '开放平台定制标'=>'44',
        'chanrongdai'=>'46',
        'shanghaidai'=>'48',
        'shanghaidai_alone'=>'49',
        'all-che-chan'=>'50',

        'shtcapital'=>'51',
        'wangailicai'=> 57,
        'yuegang' => 59,
        '随鑫约特定' => 60,
        'jifubao' => 61,
        'caiyitong' => 67,
        'qianguiyouxi' => 69,
        'dajinsuo' => '70',
        'firstp2pcn' => '100',
        'LinYouMoments' => '101401',
        'zhongxin' => '100082',
        'qiyelicai' => '101493',
        '农担及普惠' => '101864',
        '农担分站' => 101,
    ),



/**
 * 分站资源目录信息
 */

    'TPL_SITE_LIST'   => array(
        '1' => 'default',
        '2' => 'diyifangdai',
        '4' => 'mulandai',
        '5' => 'mulandaicn',
        '6' => 'yhp2p',
        '7' => 'unitedmoney',
        '8' => 'zsz',
        '9' => 'quanfeng',
        '10' => 'fortest',
        '11' => 'chedai',
        '12' => 'diandang',
        '13' => 'tianjindai',
        '14' => 'shenyangdai',
        '15' => 'shandongdai',
        '16' => 'daliandai',
        '17' => 'firstp2p_alone',
        '18' => 'zsz_alone',
        '19' => 'yhp2p_alone',
        '20' => 'unitedmoney_alone',
        '21' => 'quanfeng_alone',
        '22' => 'diyifangdai_alone',
        '23' => 'diandang_alone',
        '24' => 'tianjindai_alone',
        '25' => 'shenyangdai_alone',
        '26' => 'shandongdai_alone',
        '27' => 'daliandai_alone',
        '28' => 'chedai_alone',
        '29' => 'creditzj',
        '30' => 'creditzj_alone',
        '31' => 'esp2p',
        '32' => 'esp2p_alone',
        '35' => 'ronghua',

        '38' => 'jinxind',
        '40' => 'dongguandai',
        '42' => 'yijinrong',
        '44' => 'cunyindai',
        '46' => 'chanrongdai',
        '48' => 'shanghaidai',
        '51' => 'shtcapital',
        '57' => 'wangailicai',
        '58' => 'wangailicai_alone',
        '59' => 'yuegang',
        '60' => 'yuegang_alone',
        '61' => 'jifubao',
        '62' => 'kanghui',
        '63' => 'yingshi',
        '64' => 'yingshi_alone',
        '65' => 'xjd',
        '66' => 'xjd_alone',
        '67' => 'caiyitong',
        '68' => 'caiyitong_alone',
        '69' => 'qianguiyouxi',
        '70' => 'dajinsuo',
        '100' => 'firstp2pcn',
        '101493' => 'qiyelicai',
        '101864' => '农担及普惠',
        '101'    => '农担分站',
        ),

    /**
     * 交易所列表  'siteId' => $exchangeName
     */
    'EXCHANGE_LIST' => array(
        '70' => 'dajinsuo',
    ),

/**
 * 分站用户分组
 */

    'SITE_USER_GROUP'   => array(
        'firstp2p'=>'1',
        'diyifangdai'=>'44',
        'mulandai'=>'10',
        'mulandaicn'=>'8',
        'yhp2p'=>'4',
        'unitedmoney'=>'6',
        'zsz'=>'46',
        'quanfeng'=>'18',
        'fortest'=>'19',
        'chedai'=>'21',
        'diandang'=>'29',
        'tianjindai'=>'40',
        'shenyangdai'=>'42',
        'shandongdai'=>'39',
        'daliandai'=>'41',
        'creditzj'=>'47',
        'esp2p'=>'45',
        'ronghua'=>'52',
        'cunyindai'=>'86',
        'dongguandai'=>'88',
        'jinxind'=>'90',
        'shanghaidai'=>'92',
        'chanrongdai'=>'94',
        'yijinrong'=>'96',
        'shtcapital'=>'161',
        'wangailicai' => '245',
        'yuegang' => '249',
        'jifubao' => '278',
        'kanghui' => '282',
        'yingshi' => '296',
        'xjd' => '291',
        'caiyitong' => '301',
        'qianguiyouxi' => '400',


        //春雨医生 患者
        'cyhb' => '461',
        'cyhd' => '461',
        'cyhz' => '461',

        //春雨医生 医生
        'cyhn' => '460',
        'cyxb' => '460',

        'wxgj' => '17',

        //领友圈
        'LinYouMoments' => '458',

        //网信一房
        'wxyf' => '217',

        'dyfdqdkw' => '27',
        'dyfdhm'   => '335',
        'klxyf'    => '199',

    ),


/**
 * 易宝支付充值名称
 * 充值时，显示给用户支付的商品的名称，并且针对不同站点跳转到不同网站的成功页面。
 *
 */

    'PAYMENT_PID'   => array(
        'firstp2p'=>array('name'=>'网信理财充值','url'=>'http://www.firstp2p.com','type'=>'充值','desc'=>'网信理财网站-会员充值'),
        'diyifangdai'=>array('name'=>'第一房贷充值','url'=>'http://p2p.diyifangdai.com','type'=>'充值','desc'=>'第一房贷网站-会员充值'),
        'mulandai'=>array('name'=>'木兰贷充值','url'=>'http://mulandai.jinrongnvlang.com','type'=>'充值','desc'=>'木兰贷网站-会员充值'),
        'mulandaicn'=>array('name'=>'木兰贷CN充值','url'=>'http://www.mulandai.cn','type'=>'充值','desc'=>'木兰贷网站-会员充值'),
        'yhp2p'=>array('name'=>'长城盈华充值','url'=>'http://www.yhp2p.cn','type'=>'充值','desc'=>'长城盈华网站-会员充值'),
        'unitedmoney'=>array('name'=>'联合货币充值','url'=>'http://e.unitedmoney.com','type'=>'充值','desc'=>'联合货币网站-会员充值'),
        'jtnsh'=>array('name'=>'九台农商行充值','url'=>'http://e.jtnsh.com','type'=>'充值','desc'=>'九台农商行网站-会员充值'),
        'quanfeng'=>array('name'=>'P2P充值','url'=>'http://quanfeng.firstp2p.com','type'=>'充值','desc'=>'P2P网站-会员充值'),
        'fortest'=>array('name'=>'P2P充值','url'=>'http://fortest.firstp2p.com','type'=>'充值','desc'=>'P2P网站-会员充值'),
    ),

    /**
     * 短信发送配置信息
     */
    'SMS_SEND_CONFIG'=> array(
            'SMS_SEND_SIGNATURE'=>0,   //短信发送 签名开关 0打开 1关闭
            'SMS_GROUP_SWITCH'=>0,      //短信集团内部接口开关 0打开 1关闭
            'SMS_SERVERURL'=>'http://sms.corp.ncfgroup.com/service',    //集团内部短信接口url
            'FAST_SMS_SERVERURL' => 'http://fastsms.corp.ncfgroup.com/service', //快速通道
            'SMS_SEND_SIGNATURE_CONTENT'=>'网信理财',
            'APP_SECRET' => 'firstp2p',
    ),

     /**
     * 短信平台模板配置
     * @author  caolong
     * @date    2014-3-7
     */
    'SMS_TEPLATE_CONFIG'=>array(
        'TPL_DEAL_THREE_SMS'=>'12',                     //三日内还款短信通知模板
        'TPL_SMS_DEAL_FULL'=>'13',                      //满标短信通知
        'TPL_DEAL_UPDATE_SMS'=>'14',                    //管理员修改借款申请对用户短信通知
        'TPL_SMS_AUTH_FAILED'=>'16',                    //用户认证信息审核失败短信通知模板
        'TPL_SMS_AUTH_OK'=>'17',                        //用户认证信息审核通过短信通知模板
        'TPL_DEAL_GUARANTOR_VERIFY_SMS'=>'18',          //借款保证人同意短信模板
        'TPL_DEAL_PUBLISH_SMS'=>'19',                   //借款后台审核通过短信通知模板
        'TPL_DEAL_GUARANTOR_SMS'=>'20',                 //借款保证人邀请注册短信模板
        'TPL_SEND_CONTRACT_SMS'=>'21',                  //满标合同短信模板
        'TPL_DEAL_SUBMIT_SMS'=>'22',                    //借款提交短信模板
        'TPL_DEAL_FAILED_SMS'=>'23',                    //流标短信模板
        'TPL_PAY_WARN_SMS_del'=>'24',                   //支付未成功通知短信模板
        'TPL_PAY_WARN_SMS'=>'25',                       //支付未成功通知短信模板
        'HY0'=>'26',                                    //汇赢1号项目（特别）
        'TPL_SMS_PREPAY'=>'27',                         //提前还款成功通知短信
        'TPL_SMS_PREPAY_PASS'=>'28',                    //提前还款审核通过
        'TPL_SMS_PREPAY_NOT_PASS'=>'29',                //提前还款申请未通过审核
        'TPL_DEAL_LOAD_REPAY_SMS'=>'30',                //还款短信模板
        'TPL_DEAL_CHANNEL_PAY_SMS'=>'31',               //渠道推广分成入账短信通知模板
        'TPL_DEAL_TENDER_SMS'=>'32',                    //投标完成短信模板
        'TPL_SMS_PAYMENT'=>'83',                        //收款短信通知模板
        'TPL_SMS_VERIFY_CODE'=>'72',                    //发送短信认证码模板
        'TPL_DEAL_UPDATE_COMFIRM_SMS'=>'38',            //用户确认借款申请修改短信通知
        'TPL_DEAL_NOTICE_SMS'=>'39',                    //借款短信通知模板
        'TPL_SMS_DEAL_FULL_BORROWER'=>'42',             //满标给借款人发短信
        'TPL_SMS_DEAL_FULL_LENDER'=>'43',               //满标给出借人发短信
        'TPL_DEAL_FAILED_SMS_BORROWER'=>'44',           //流标给借款人发短信模板
        'TPL_DEAL_FAILED_SMS_LENDER'=>'45',             //流标给出借人发短信模板
        'TPL_DEAL_PUBLISH_SMS_NEW'=>'46',               //借款后台审核通过短信通知模板-新
        'TPL_DEAL_CHANNEL_PAY_SMS_NEW'=>'47',           //渠道推广分成入账短信通知模板-新
        'TPL_SMS_CHANGE_MOBILE_NEW'=>'56',              //用户修改手机号码
        'TPL_SMS_FIRSTP2P_ACCOUNT'=>'58',               //批量用户账号
        'TPL_SMS_FIRSTP2P_SUBSIDY'=>'95',               //平台贴息率
        'TPL_SMS_MODIFY_PASSWORD' => '66',               //修改密码成功
        'TPL_SMS_MODIFY_FORGETPASSWORD_CODE' => '75',     //忘记密码发送验证码
        'TPL_SMS_MODIFY_PASSWORD_CODE'=>'1212',          //修改密码发送验证码
        'TPL_SMS_MODIFY_OLD_PHONE_CODE' => '73',         //修改原手机号发送验证码
        'TPL_SMS_MODIFY_NEW_PHONE_CODE' => '74',          //验证新手机号发送验证码
        'TPL_SMS_IDCARDPASSED' => '70',                  // 后台通过身份认证审核
        'TPL_SMS_UNIDCARDPASSED' => '71',                // 后台未通过身份认证审核
        'TPL_DEAL_LOAN_SMS' => '79',                    // 投资项目放款、计息
        'TPL_SMS_WITHDRAW_SUCCESS' => '81',              // 提现成功
        'TPL_SMS_ACCOUNT_CASHOUT' => '80',              //提现申请
        'TPL_SMS_ACCOUNT_CASHOUT_FAIL' => '82',         //提现失败
        'TPL_SMS_USER_SIGNUP_INVITE' => '84',           //邀请注册返现
        'TPL_SMS_USER_INVEST' => '86',                  //投资返利
        'TPL_SMS_USER_BIND_BANK_APPLY' => '67',         //修改银行卡申请提交
        'TPL_SMS_USER_BIND_BANK_SUCC' => '68',          //修改银行卡信息成功
        'TPL_SMS_USER_BIND_BANK_FAIL' => '69',          //修改银行卡信息失败
        'TPL_SMS_LOAN_REPAY' => '76',                   //投资项目回款
        'TPL_SMS_DEAL_FAILD' => '78',                   //流标给出借人发送短信
        'TPL_SMS_INVITE_OTHERS_INVEST' => '85',           // 邀请他人投资返利
        'TPL_REGISTER_INVITE_REBATE_SMS' => '87',           // 邀请他人注册返利已返
        'TPL_SMS_DEAL_BID' => '77',                        // 投资成功
        'TPL_SMS_DTB_REDEMPTION_APPLY' => '772',        // 多投申请赎回成功
        'TPL_SMS_DTB_DEAL_BID' => '773',                // 多投投资成功
        'TPL_SMS_EMAIL_QUEUE_WARN'=>'94',               //邮件队列报警
        'TPL_SMS_ALARM' => '158',                        //通用告警
        'TPL_SMS_BONUS_SEND' => '1107',                 //系统发送红包通知
        'TPL_SMS_JIJIN_APPLY_SUCC' => '1109',        //申购扣款成功
        'TPL_SMS_JIJIN_APPLY_FAIL' => '1110',          //申购扣款失败
        'TPL_SMS_JIJIN_SHARE_CONFIRM_SUCC' => '1111',    //份额确认成功
        'TPL_SMS_JIJIN_SHARE_CONFIRM_FAIL' => '1112',  //份额确认失败
        'TPL_SMS_JIJIN_REDEEM_APPLY' => '1113',         //赎回申请
        'TPL_SMS_JIJIN_REDEEM_SHARE_CONFIRM_SUCC' => '1114', //赎回份额确认成功
        'TPL_SMS_JIJIN_REDEEM_SHARE_CONFIRM_FAIL' => '1115', //赎回份额确认失败
        'TPL_SMS_USER_LOG_COUPON_REBATE' => '1118',                 // 优惠码返利按天发送
        'TPL_SMS_BONUS_HAPPY_NEW_YEAR' => '1151',    //拜年红包短信
        'TPL_SMS_FIRST_DEAL_BONUS_REBATE' => '1153',  //首投红包邀请人返利短信
        'TPL_SMS_REGISTER_BONUS_REBATE' => '1156',  //注册红包邀请人返利短信
        'TPL_SMS_BINDCARD_BONUS_REBATE' => '1157',  //注册红包邀请人返利短信
        'TPL_SMS_BONUS_JOBS' => '1168',  //红包任务短信
        'TPL_SMS_CASH_BONUS_RESEND' => '1169',  //补发红包短信
        'TPL_SMS_CASH_BONUS_REBATE_RESEND' => '1170',  //补发返利红包短信
        'TPL_SMS_BONUS_FIRST_DEAL_RESEND' => '1171',  //补发首投红包短信
        'TPL_SMS_BONUS_EVENT' => '1172', //任性发红包短信
        'TPL_SMS_BONUS_EVENT_APOLOGY' => '1173', //任性发红包道歉短信
        'TPL_SMS_BONUS_FOR_AA_USER' => '1174', //AA租车用户红包短信
        // 配资业务
        'TPL_SMS_PEIZI_APPLY_SUCCESS'  => '1199',  // 申请配资审核通过
        'TPL_SMS_PEIZI_APPLY_FAILED'   => '1200',  // 申请配资审核失败
        'TPL_SMS_PEIZI_RENEW_SUCCESS'  => '1201',  // 续期审核成功
        'TPL_SMS_PEIZI_RENEW_FAILED'   => '1202',  // 续期审核失败
        'TPL_SMS_PEIZI_APPEND_SUCCESS' => '1203',  // 追加审核成功
        'TPL_SMS_PEIZI_APPEND_FAILED'  => '1204',  // 追加审核失败
        'TPL_SMS_PEIZI_END_SUCCESS'    => '1205',  // 清算审核成功
        'TPL_SMS_PEIZI_PROFIT_SUCCESS' => '1206',  // 提取收益审核成功
        'TPL_SMS_PEIZI_PROFIT_FAILED'  => '1207',  // 提取收益审核失败
        'TPL_SMS_PEIZI_WARNING'        => '1208',  // 触警告线
        'TPL_SMS_PEIZI_CLOSE'          => '1209',  // 触平仓线（合约未到期）
        'TPL_SMS_SET_SITE_CODE' =>'1240',  //设置收获地址手机验证
        'TPL_SMS_MODIFY_SITE_CODE' =>'1241',  //修改收货地址手机验证
        'TPL_SMS_SET_PROTION_CODE' =>'1249',  //设置密保问题
        'TPL_SMS_MODIFY_PROTION_CODE' =>'1250',  //修改密保问题
        'TPL_SMS_WEB_RELOGIN_CODE' =>'1251',  //web防套利重新登录短信身份验证
        'TPL_SMS_JIJIN_SPECIFIC_REDEEM_CONFIRM_SUCC'=> '1127',  // p2p 私募还本后台短信模板id
        'TPL_SMS_JIJIN_SPECIFIC_BONUS_CONFIRM_SUCC' => '1128',  // p2p 私募分红后台短信模板id
        'TPL_SMS_LOAN_REPAY_MERGE' => '1164',  // 回款合并短信
        'TPL_SMS_DEAL_BID_MERGE' => '1165',  // 投资合并短信
        //股票业务
        'TPL_SMS_STOCK_TRADE_SUCC' => '1175',  //股票交易成功
        'TPL_SMS_OPEN_BEDEV' => '1242', //成为网信开发者
        'TPL_SMS_FIRST_DEAL_BONUS_REBATE_FOR_NEW' => '1174', //首投双返投资人短息
        'TPL_SMS_DISCOUNT_PUSH' => '1178', //投资劵推送短信
        'TPL_SMS_DEAL_BID_MERGE_GYB' => '1180', // 公益标短信

        'TPL_SMS_FUND_REDEEM' => '9999', // 基金赎回到账短信,id随便配置

        // JIRA#3260 企业账户二期 by fanjingwen
        'TPL_DEAL_UPDATE_SMS_NEW'               => '1130', // 管理员修改借款申请对用户短信通知
        'TPL_SMS_WITHDRAW_SUCCESS_NEW'          => '1131', // 提现成功
        'TPL_SMS_ACCOUNT_CASHOUT_NEW'           => '1132', // 提现申请
        'TPL_SMS_ACCOUNT_CASHOUT_FAIL_NEW'      => '1133', // 提现失败
        'TPL_SMS_USER_SIGNUP_INVITE_NEW'        => '1134', // 邀请注册返现
        'TPL_SMS_USER_INVEST_NEW'               => '1135', // 投资返利
        'TPL_SMS_DEAL_FAILD_NEW'                => '1136', // 流标给出借人发送短信
        'TPL_SMS_INVITE_OTHERS_INVEST_NEW'      => '1137', // 邀请他人投资返利
        'TPL_REGISTER_INVITE_REBATE_SMS_NEW'    => '1138', // 邀请他人注册已返利
        'TPL_SMS_USER_LOG_COUPON_REBATE_NEW'    => '1139', // 每日返利短信模板
        'TPL_SMS_PAYMENT_NEW'                   => '1140', // 充值成功
        'TPL_SMS_MODIFY_PASSWORD_NEW'           => '1141', // 修改密码成功
        'TPL_SMS_LOAN_REPAY_MERGE_NEW'          => '1142', // 回款成功合并短信
        'TPL_SMS_DEAL_BID_MERGE_NEW'            => '1143', // 投资成功合并短信
        'TPL_SMS_DEAL_BID_MERGE_GYB_NEW'        => '1144', // 公益标投资成功短信
        'TPL_SMS_RESERVE_LOAN_REPAY_MERGE'      => '1145', // 预约回款成功合并短信
        'TPL_SMS_RESERVE_DEAL_BID_MERGE'        => '1146', // 预约投资成功合并短信
        'TPL_SMS_RESERVE_CHARGE_REMIND'         => '1147', // 预约充值提醒短信
        'TPL_SMS_JIJIN_LOCK_MONEY_SUCC' => '1244', // 申购冻结成功
        'TPL_SMS_JIJIN_LOCK_MONEY_FAIL' => '1245', // 申购冻结失败
        'TPL_SMS_JIJIN_APPLY_CONFIRM_FAIL' => '1246', // 申购确认失败
        'TPL_SMS_DISCOUNT_RATE_PUSH' => '1247', //加息劵推送短信
        'TPL_SMS_DISCOUNT_RATE_G_PUSH' => '1262', //黄金券推送短信

        // 信用贷短信 by jinhaidong
        'TPL_SMS_CREDIT_LOAN_SUCCESS' => '1248',
        'TPL_SMS_CREDIT_REPAY_SUCCESS' => '1249',

        //存管相关
        'TPL_SMS_SUPERVISION_WITHDRAW_SUCCESS' => '1301', // 提现成功
        'TPL_SMS_SUPERVISION_WITHDRAW_APPLY' => '1302', // 提现申请
        'TPL_SMS_SUPERVISION_WITHDRAW_FAIL' => '1303', // 提现失败

        //黄金相关
        'TPL_SMS_GOLD_DEAL_BID_MERGE' => '1401', // 买金
        'TPL_SMS_GOLD_DEAL_REPAY_MERGE' => '1402', // 黄金回款
        'TPL_SMS_GOLD_FAIL_DEAL' => '1403', // 黄金流标
    ),
    'SMS_TEMPLATE_CONTENT' => array(
        '72' => '验证码：%s（3分钟内有效），欢迎您的加入。友情提示：平台奖励政策请以官网、APP和微信公众号三种官方渠道发布的信息为准。',

    ),
    //DB加密配置
    'DB_DES_MODELS' =>array(
        'firstp2p_bonus_bind' =>array('mobile'=>1), 'firstp2p_cash_present' =>array('bankcard'=>1,'mobile'=>1),
        'firstp2p_deal_agency' =>array('bankcard'=>1,'mobile'=>1), 'firstp2p_deal_guarantor' =>array('mobile'=>1),
        'firstp2p_deal_project' =>array('bankcard'=>1), 'firstp2p_loan_intention' =>array('phone'=>1),
        'firstp2p_thirdparty_order' =>array('mobile'=>1), 'firstp2p_user' =>array('idno'=>1,'mobile'=>1) ,
        'firstp2p_user_bankcard' =>array('bankcard'=>1, 'e_account'=>1,'p_account' =>1), 'firstp2p_user_bankcard_audit' =>array('bankcard'=>1),
        'firstp2p_user_carry' =>array('bankcard'=>1),'firstp2p_user_passport' =>array('idno'=>1),
        'firstp2p_user_yifang' =>array('mobile'=>1), 'firstp2p_idno_register' =>array('idno'=>1),
        'firstp2p_register_batch_log' =>array('idno'=>1,'mobile'=>1),
        'firstp2p_user_identity_modify_log' =>array('idno' => 1),
        'firstp2p_user_pwd_reset_audit' => array('mobile' => 1),
        'firstp2p_user_assessment_result' => array('mobile' => 1),
        'firstp2p_darkmoon_deal_load' =>array('idno'=>1,'mobile'=>1,'bank_id'=>1),
        'firstp2p_exchange_load' => array('bank_no' => 1, 'certificate_no' => 1, 'mobile' => 1),
    ),
    'DB_DES_OPERATOR'=>array('UPDATE'=>1,'DELETE'=>1,'SELECT'=>1),
    //几个分站的默认邀请码
    'DEFAULT_INVITE_CODE' => array(
        '2' => 'F0DC31',
        '16' => 'FBDC13',
        '42' => 'F29AB2',
        '31' => 'FH9MVR',
        '35' => 'FBDC2D'
    ),

    // 信息披露-[用于控制哪个机构需要展示,true-展示,1234对应dict-ORGANIZE_TYPE]
    'diclosure_show' => array(
        '担保机构'   => false,
        '咨询机构'   => true,
        '平台机构'   => false,
        '支付机构'   => false,
        '管理机构'   => false,
        '代垫机构'   => false,
    ),

    // web前台界面是否显示
    'WEB_DISCLOSURE_SHOW' => false,
    //第三方风控
    'THIRD_PARTY_RISK'=>array('HOST'=>'http://172.21.30.22:8686/audit','HOST_NOTIFY'=>'http://172.21.30.22:9191/addStatus','DEBUG'=>1),
    //风控投篮验证
    'RISK_SHOOT_AUTH' => 'http://172.21.30.80:8088/codeService',

    // 掌众合同类型ID
    'WESHARE_CONTRACT_TPL_TYPE' => 439,
    //滑块验证码服务
    'HKYZM_URL' =>'http://172.21.30.80:8087/codeService',
    // 红包币名称配置
    'NEW_BONUS_TITLE' => '红包',
    'NEW_BONUS_UNIT' => '元',

    //信仔服务配置
    'XIN_CHAT' => array(
        'BACKEND_HOST' => "http://xinchat.wangxinlicai.com/",
    ),
    // 通行证相关配置
    'PASSPORT_SERVER' => 'http://wangxin-rs.corp.primeledger.cn/unipassport_api',
    'PASSPORT_TIMEOUT' => 1,
    'PASSPORT_AES_KEY' => 'JON48JQ8-1A23RI6',
    'PASSPORT_PUBLIC_KEY' => '
-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDT3gcMfUYTHCyRQ6t89oVC1ZwS
bjj045VZOPDyqFlcfJK2ZAdw3qw1Io/A47BmtHw0XNS1DsltiA/Kgdl2UKeej73a
tNNccfTuZE89GRtN5Fp983Wa1Fr9gPHooljUdp2+QldbjaoQ/pZGX33wkkwK77Ac
ynCEelWUFgkAYKnZwQIDAQAB
-----END PUBLIC KEY-----
',
    'PASSPORT_WX_PUBLIC_KEY' => '
-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDokvLSTv4RXm6ziIx/ODRWk4dJ
+hDnRwn0Bq54rANfKTFxTeVeIIgnFKjKAdxFAbaRkN2RR0ylzYYaf5HIRTn8JtZY
NVnIoDq4idqSqIGDxr2UElw5neGKpHyYA9TgAuNPenPrDX3KaLPfYNjfJwkoogEp
Hq4eKJq2kBgnPi8QOwIDAQAB
-----END PUBLIC KEY-----
',
    'PASSPORT_WX_PRIVATE_KEY' => '
-----BEGIN RSA PRIVATE KEY-----
MIICXQIBAAKBgQDokvLSTv4RXm6ziIx/ODRWk4dJ+hDnRwn0Bq54rANfKTFxTeVe
IIgnFKjKAdxFAbaRkN2RR0ylzYYaf5HIRTn8JtZYNVnIoDq4idqSqIGDxr2UElw5
neGKpHyYA9TgAuNPenPrDX3KaLPfYNjfJwkoogEpHq4eKJq2kBgnPi8QOwIDAQAB
AoGAAaWIspR8mALjJcJBvGTZegNxYcSzee+20lN5yMDvBS11fCfhB9mTHTO4PjXl
KVfpwv4Tk4O9ty7NVEKy9YgH8Q2g0DE7DdhzoeHDdAJtPXWWvheGqShLDjn0EyGx
QZ6BqMVWSlKimktAHUU4jaAss7mu9iemYdn3X9fExVv/KQECQQD0wg+1AOFI6Lky
eg/2xw1Ae9OST9nBTpH2qVcIlKW3rjNFxELOz727ZQO+O6ki/7TL4h4eSLIJF7el
UvashcaDAkEA80GfpkfCGLY6KEurlo5jTvkZuzgipUjA8y0jURsXHULAEeTPdVd3
yuA5y4n8PCHIltiC8KbNCMQ0Wvu9Yweh6QJBAI4kFVMcy7i3zrXNxW+fcca9IsWZ
sfBdXM9O0Mie6w6dEBG4RMQuSRWHOIFFzJgSwECXdL5JoXs+VtygLblLh1kCQC9f
eVLtqJwdaOgODIWOh0KK+nrebMjZiVISWU1jRDYbmMIjWE+W0Cp/TmIYJjojrifK
VH2/TjDF3RhW7EQL6XkCQQDiiOIBKTebI4q0sXmd7zDoEzAo9GPYGXVcmpTFjy9K
veTvHRZ+XJTuEOwYW9+/EDg6mm0/JYJXE+ghhUKrMbpW
-----END RSA PRIVATE KEY-----
',
    'YITU_API_HOST' => 'http://face.wxlc.org',
    //BUC接口相关配置
    'BUC' => array(
        'HOST' => 'https://mobile-api.bitun.io',
        'MERCHANT_PRIVATE_KEY' => 'MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQCBIKlJEW+W4OR9ZYh/7Q38VXouK81Gog8IvAk5OknqKQOHXdjMukvLeumPE3eTf0J9VHvcpbe/Bl4J1r+EAHjuXTVPQ3kU+SGMpFi8AmkvIm4ZlTqeEQ6hy97ivsLdmleLJTL3QDphsB1Wku1EA8z7RXJ/kJz2BRqDTynsneqjYV86JNFVaDImor7YQewkVgvj7aAm7IVew3zlF6LmIxd6tgNdFyP8DIv4P2/Pni2uBzYhxa+mEHcohDwXSQyqsewT9SgU7scmPJ6U1jQTDDb6Ea0dXThbRulhVZcBDE7k+4jDMD6cXsSTzUBESHd7H2aoI1GF6tVKujkeWWSjp3vrAgMBAAECggEASxKIGTkP3XJGpTjgkS9rECdJ74n7wHL7tqJL4n+m9YJ+0LaZEv3EjzqYi1xQCurT5zW1BWU7XM4A4kt98OqtZDfAhKK/xJfLD4+FH1lveb3UsP73o2pbTWnP3Zf2hwvyla084k7vIlhX1jt5wi9Lf1Ky7MNQ3h+9eFYQE8cD4ZQjRNZYwubSfhT6ERtMlsfTP2cxww4ivlI0Rp6tD7nviAfNXDQKXWXS/oGlW5nGC9cc8S1bqqXSH46o17KAkBxIb70uBhAItn1YjuXoMz02AEkp0tVb4Ww1Dx40f0xKAxUtx9X9qZ2zGvrwchYabgMtL6kWDAYN1Hl/c73uB7zMsQKBgQDndsGCpvEuJccfKptLgfk3colaRQ4/sJ/9US7iiXiKDdmL1IXuwm709KN3+yue9d6hIiGM7Bir9YE5OmqSIomtyqQettSfkNHZkkic1ZDECs+tonPL+QNoYx1Pu09ptcBvfc/VVCvAMsidxQsqy4Kg3osvP8KtAY+KcaMhZtXdVwKBgQCO0M0eKc/C/+FqEzrmcS2cIY5MSlU8m1VEa3uBeKL6hoShHAI9xnDF14sCvwwVqZkJQka5oXzxH9guzSbFcURrFlY5oI0ALlmknDj3fHD/5ggihoOTLEjBN0fosDnGkHv7sCBl2DpZZOoqoGnJvFO3ueJFa11s0GhLVeHbFbkljQKBgQCxEhCHut7Wxt/F3/pdkVjuKhkZjaqQ3rYGPIz5kOE9+8ppRnQPa38mteA4cVW1jbJ64nl4LUXSh0YMWbwdf36EDXnwZT3TtB5Vq6sRD/BniT3HP3gGmsEYonpR/KGL5RcKM91C/dlad38x8wPQAVA1cQpZYxWy9HazGqwJaXpCkQKBgAn5WSnvH5xtxG9lEmr/ooDR8x8u3c8Sxhy2KPT82N0IQgECdMGdt0iJ6iJx90UYCCEWaNwUBCbO44k5OKj3ZSDZeYIizVYi5o3Rf0UBgt7MUOsgW9YOdLJRywrcK9zLQG1ueoTCWijeHGCHvXuiVHJzX/MbMXYlA/Om9tQXK0wNAoGBAJhIDlGA4KT7I+AW8U0j5OEphn77tjW2dU2w/7U3oQeqz9Z88sHdPyDDC6O7C1/WPMDcs49NQ22LQdjzXcBZC4y7j+AA0IpfqNtm7JzyupEFxQgv6hWC8YaQHhixyaf4TtFWxK9z4y/sWc//B+gN4Nn7AyjUkThlGNkCLiTcym0z',
        'BITUN_PUBLIC_KEY' => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAkiXP++xJIaIy9seXXxc6TKBax+2XgXcB/1TPJaExLfCwjaWK0BAcOQjdt86YaaUadX36M3/wsqpypD+pkLlfWLR017YxgfSS2KRBX16W/nTFm1WNBF3OZf0czE4TWg51nr2uh8FCGJ8qPNYi0lb6ZMiOYdCDh0vM0cLli/CHvGeLuDks4pO9u4oB+omxECFdPIFL0PeGrdSRc/0kbYOGBA+jAL4GVZBPyr9Hy1/FXk5qLGIvSgohnHfrwUpuRwasGNSIbCjjsXcx9u04TrJa7zRjOssVxIJQGpjPpNOjgniAzUsqd412XcXLBS01Mgo3qWPK4RoR6rRfSa3QrffvtQIDAQAB',
    ),

    // 信宝兑换生活商城相关配置
    'LIFE_SHOP' => [
        'SHOP_HOST' => 'https://mall.ncfgroup.com/zeus-be/auth/login.do?from_wx_xb=1',
        'TOP_PRODUCT_LIST' => 'https://mall.ncfgroup.com/zeus/goods/hotindex.do',
    ],
    //CRE接口相关配置
    'CRE' => [
        'HOST' => 'https://portal.coinreal.io',
        'SIGN_KEY' => '4B5121231E6872A898B3AE6ADE3ACDE1',
    ],
    // CANDY 信宝服务配置
    'CANDY' => [
        'HOST' => [
            'http://172.21.12.245:15001',
            'http://172.21.12.246:15001',
        ],
    ],
    //api用户体系加密TOKEN秘钥
    'TOKEN_ENCRYPT_KEY' => 'kI5ra4qnIOpEGzVwoFANrA==',
);
