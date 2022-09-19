<?php
/**
 * 线上环境配置
 */
define("PRE_HTTP", get_https());
define("DEBUG", false);
$http = 'https://';

$p2pRc = get_cfg_var('p2p_rc');
if ($p2pRc)
{
    $notifyDomain = $http.'prenotify.firstp2p.cn';
} else {
    $notifyDomain = $http.'notify.firstp2p.cn';
}
$env_conf = array(
    // 通知域名
    'NOTIFY_DOMAIN' => $notifyDomain,
    'QYGJ_SITE_ID' => 15, //签约管家 siteId

    //获取白泽任务配置
    'BAIZE_DOMAIN' => "http://baize.corp.ncfgroup.com",
    'BAIZE_KEY' => "!@$#P2P*%#2",
    /**
     * OPEN HOST
     */
    'OPEN_HOST' => "http://open.ncfwx.com",
    'FIRSTP2P_WAP_DOMAIN'  => "m.ncfwx.com",
    'IS_P2P_TPL_USE_V3'    => true, // 主站是否使用v3的模板
    'FENZHAN_OPEN_SWITCH'  => true, //全局分站开关
    'FENZHAN_NOT_OPEN'     => array( //这些站点不走分站的逻辑
        'wei2.p2pdev.ncfgroup.com',  //红包
        'prenotify.wangxinlicai.com', // 灰度回调地址
        'prenotify.ncfwx.com', // 灰度回调地址
        'prenotify.firstp2p.cn', // 灰度回调地址
        'notify.wangxinlicai.com', // 生产环境回调地址
        'notify.ncfwx.com', // 生产环境回调地址
        'notify.firstp2p.cn', // 生产环境回调地址
        //'fangdai.firstp2p.com',
        'www.mulandai.cn',
        //'yh.firstp2p.com',
        //'an.firstp2p.com',
        //'um.firstp2p.com',
        //'zsz.firstp2p.com',
        //'cnpawn.firstp2p.com',
        //'tianjindai.firstp2p.com',
        //'cnp2p.firstp2p.com',
        //'daliandai.firstp2p.com',
        //'esp2p.firstp2p.com',
        //'rxh.firstp2p.com',
        //'xianfengwuliucaifu.firstp2p.com',
        //'yijinrong.firstp2p.com',
        //'cunyindai.firstp2p.com',
        //'chanrongdai.firstp2p.com',
        //'shanghaidai.firstp2p.com',
        //'shtcapital.firstp2p.com',
        //'yuegang.firstp2p.com',
 //       'yingshi.firstp2p.com',
        'rongxh.diyifangdai.com',
        'caish.diyifangdai.com',
        'm.cytfinance.com',
        'cytfinance.wangxinlicai.com',
        'm.cnpawn.cn',
        'm.firstp2p.com',
        'm.jryhpt.com',
        'm.yijinrong.com',
        'weixin.diyifangdai.com',
    ),

    'ROOT_DOMAIN'         => '.ncfwx.com',
    'MARKETING_DOMAIN'    => 'a.ncfwx.com',
    'NCFWX_DOMAIN'       => "www.ncfwx.com",
    'WXLC_DOMAIN'         => "www.ncfwx.com",
    'FIRSTP2P_COM_DOMAIN' => "www.firstp2p.com",
    'FIRSTP2P_CN_DOMAIN'  => "www.firstp2p.cn",
    'FIRSTP2P_QIYE_DOMAIN' => "qiye.ncfwx.com",
    'NCFPH_WAP_URL' => "https://m.firstp2p.cn",

    'API_HOST' => "https://api.ncfwx.com",

    //第三方渠道注册时发红包的邀请码
    'CHANNEL_REGIST_COUPON' => array('FZNJVE', 'FPZKNZ'),

    //营销分级配置
    'MINNIE_PRO_ID'  => 2,
    'MINNIE_ADD_URL' => "http://minnie.wangxinlicai.com/collect/add",

    /**
    * 电子签章环境
    */
    'CONTRACT_SIGN_SERVER'=> array('http://esigna1.wxlc.org/pdfsign/sign','http://esigna2.wxlc.org/pdfsign/sign'),

    /*
    * 第三方curlhook配置
    */
    'CURL_HOOK_CONF'=>array(
        'HaHa' => 'https://cytfinance.wangxinlicai.com/api/cytfp2p/updateUser',
        'TianMaiInvest' => 'http://pmall.yaotv.tvm.cn/open/financial/order',
        'TianMaiCoupon' => 'FLUV41',
    ),

    /**
     * 一房接口相关参数配置
     */
    'HOUSE_YIFANG_SERVICE' => array(
        'url' => 'http://cdhryx.cn:6100/chan/M01/',
        'userId' => 'WANGXIN',
        'password' => '123456',
        'encryptPassword' => 'YBiGWtYLwZG6+7NsjSugDw==',
        'aesKey' => '451135a1e3*da@e1f9b!61992',
    ),


    /**
     * 功夫贷通知相关参数配置
     */
    'NOTIFY_GDF_SERVICE' => array(
        'url' => 'https://trade.99gfd.com/notify/callPremium',
        'merchant' => 'WANGXIN',
        'version' => '0.0.1',
        'rsa_private' => '
-----BEGIN RSA PRIVATE KEY-----
MIICeAIBADANBgkqhkiG9w0BAQEFAASCAmIwggJeAgEAAoGBAMOKEyL7OF9Tlspj
VDM4JaNEQ/OuxSjhkJwTuGFX/nUXhCkTpkd2CXdAy57CMvDkfcioVP7bq/LXs1RX
pfVJC15qhYmjpfLMRSi7fwKzMWpFnmoThKDIPvS8/Xu8dCS+lBc3CH9uYcDQFMCD
ndJ5D2H1Tc6pREyNATDVFP5WD+71AgMBAAECgYAFcfXgdoB2XxyG3Ec++eRKbJ87
zPUek1F0lzP+OfYTCqmafzqVKNtQn9RLwnqqrKI4ET/0rOdX5tvOkHZFo1gWp4EB
gzFJ5mQmT3vZidMBFXHG2iCvAit3BPqiOdGjYHtBTnnCUF1TThUEWBS5Np23kxTn
rgdy+FF8CAtGxb+srQJBAO/ch4inP98d2Zp0MoKhOI7eQnJDesbifeftKABWRQ2x
Pfb2FDAX0duvZ9RApwA5DTBFmqg+jQYDXcsUeiiQVHsCQQDQsh+s6GHklwJPc1By
IqczVV1eRMbHpGXv555v940xbb/mAcF+lbpLdfqwDktoJ4XkzpWA7yoHI15lsrCk
G4dPAkEAlkjQSpiv9jWXr6R6OUqWSz2K2FjbRl2GkZgP5hYncerJbkDEaVWjUUfX
gC958zPLxaD2w89dQJU/YQxVdbDonQJBAKf65/IWLk1/mzV2PQdRi0GPcZLiSxoA
4qgix+2Z1YU2sKKjQSrxu7znnru9FcclIOnVupLIbwzF1EKJfRLqsZMCQQDZqg9m
B2y5biKWaprxsX71d7cmpoh+8B9C6oTkTzVFmjaiaL5WDTPrvda9z1w0y2dtiWOy
RUArx3mqOWI9Q/bc
-----END RSA PRIVATE KEY-----
',
        'rsa_public' => '
-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCeCRLu3cPL5pPxj9pMvB5r551M
WlhGyKCaQ5N8KPL/OXH5JwABHoOA2fATLeotBSW5uiyuY99SZrM/sCqyVOuX/4Q1
aeG04NeE16c5mZwNDmIehQDAKCFhz/qqDRpK5j273WtVc6l5jBIf9nBgDzDmI8o9
Y5xpFX0ZizoyHrVDVQIDAQAB
-----END PUBLIC KEY-----
',
        'des_3des' => 'ThisIsTrippleDESKeyDashu',
    ),

/**
 * 环境标志，by laijinhai@ucfgroup.com, at 2013-11-25
 * 值可以为 dev, test, online
 * 添加原因是因为静态资源一块有因为环境不同而不同的逻辑；
 */
    'ENV_FLAG'            => 'online',

/**
 * 服务器是否处于维护状态
 */
    'IS_UNDER_REPAIR' => 0,

/**
 * adviser配置文件
 */

    'P2P_API_URL_iS_OK'    => 0,//edit by wenyanlei 20131108, 关闭 顾问相关功能
    'P2P_API_URL'        => PRE_HTTP.'www.9888.cn/p2p/ajax/ajax.get_commission_permonth.php',


/**
 * oauth配置文件
 * 和环境相关的参数
 */
    // 是否开启oauth登录，方便测试
    'IS_OAUTH_AUTH'            => 1,
    // oauth登录验证的url
    'OAUTH_AUTH_URL'        => 'http://oauth.9888.cn/',
    // 重置密码的APIurl
    'SET_PWD_API_URL'        => PRE_HTTP.'oauth.9888.cn/oauth2/firstp2p_set_pwd.php',
    // 自动注册API URL

    // new oauth登录验证的url
    'NEW_OAUTH_AUTH_URL'        => 'https://oauth.9888.com/',
    // new oauth接口url
    'NEW_OAUTH_API_URL'        => 'http://oauth.9888.com/',
    // 修改密码的url
    'NEW_SET_PWD_API_URL'        => 'https://oauth.9888.com/oauthserver_firstp2p/firstp2p/password/modify/get.do',
    // 自动注册API URL
    'NEW_AUTO_REGISTER_API_URL'    => 'https://oauth.9888.com/oauthserver_firstp2p/firstp2p/oauth/register/get.do',
    // 退出 URL
    'NEW_OAUTH_LOGOUT_URL'    => 'https://oauth.9888.com/oauthserver_firstp2p/firstp2p/logout/get.do',
    // 客户端登陆的clientID
    'NEW_OAUTH_API_CLIENTID' => '1680b3ee2de83634c1c307db4ee9b618',

    'AUTO_REGISTER_API_URL'    => PRE_HTTP.'oauth.9888.cn/oauth2/firstp2p_auto_register.php',

    // 根据批量导入借款的用户名（firstp2p不存在的）去oauth验证是否存在，存在就返回用户信息，并在firstp2p注册
    'USERINFO_OAUTH_API_URL'=> PRE_HTTP.'oauth.9888.cn/oauth2/firstp2p_get_userinfo.php',


/**
 * admin配置文件
 * 和环境相关的参数
 */
    // 以下ip不开启admin登录验证码校验，方便测试
    'IS_ADMIN_VERIFY_WHITE_LIST' => array(),

/**
 *
 * 和环境相关的参数
 */
// 是否开启注册验证码校验，方便测试
'IS_REGISTER_VERIFY'            => true,


/**
 * 静态文件路径配置，by laijinhai@ucfgroup.com, at 2013-11-25
 *
 * STATIC_OPTION , 开发环境下调用静态资源的方式，支持三种值，NOCACHE（不缓存），CACHE（缓存，同之前版本），PUBLISH（发布）；
 * 默认值为 PUBLISH，只有开发环境下可以配置；
 *     NOCACHE , 调用不缓存的静态文件 , 适用于前端开发时的调试，不需要清除缓存即可立刻显示修改的js、css；
 *     CACHE , 调用缓存静态文件，如不存在则生成，同之前版本 , 缓存写在public/static/目录下；
 *     PUBLISH , 调用发布后的静态文件，参数通过其他几个配置来指定；
 *
 * STATIC_DOMAIN_ROOT,STATIC_DOMAIN_NAME,STATIC_WEB_PATH
 * 这三个参数可以拼接成发布后静态文件的url前缀，比如：
 *     http://fp1.ncfstatic.com/runtime/static/{STATIC_FILE_PATH}
 * 开发环境下建议配置为 lc.fp[1-9].ncfstatic.com, dev.fp[1-9].ncfstatic.com；
 * 测试环境下建议配置为 test.fp[1-9].ncfstatic.com；
 * 线上环境下建议配置为 fp[1-9].ncfstatic.com；
 *
 * 发布静态文件的脚本请见 scripts/publish_static_file.php
 */
    //开发环境下调用静态资源的方式
    'STATIC_OPTION'            => 'PUBLISH',
    //静态资源根域名
    'STATIC_DOMAIN_ROOT'    => '.firstp2p.cn',
    //静态资源的二级域名前缀，用于构造随机的静态域名，比如前缀为fp，那么构造fp1~fp9.ncfstatic.com
    'STATIC_DOMAIN_NAME'    => 'assets',
    //CDN 二级域名前缀
    'CDN_DOMAIN_NAME' => 'assets',
    //用户上传资源CDN二级域名前缀
    'UPLOAD_DOMAIN_NAME' => 'fp',
    //CDN 跟域名
    'CDN_DOMAIN_ROOT' => '.ncfstatic.com',
    //静态资源的放置路径，注意是绝对路径
    'STATIC_WEB_PATH'        => '/',
    //静态文件（图片cssjs目录版本，同时作为文件夹名调用静态资源）
    'STATIC_FILE_VERSION'    => 'v1',

    //白泽调用接口url
    'STATISTICS_BAIZE_URL' => 'http://log.corp.ncfgroup.com',
    //日报周报统计调用接口url
    'STATISTICS_EMAIL_URL'      =>'http://log.corp.ncfgroup.com/api/ncfrs/',

    // 用户上传静态文件路径
    'STATIC_UPLOAD_HOST'    => 'http://fpupload.wangxingroup.com',
    // 新的静态文件服务器
    'STATIC_HOST' => '//static.firstp2p.com',

    // 房贷专用静态文件服务器域名
    'ISTATIC_HOST' => '//istatic.firstp2p.com',

    'OAUTH_SERVER_CONF' => array(
        //人脸接口
        'a0b778af1632d2719d51ea3ee3d7d05d' => array(
            'client_id' => 'a0b778af1632d2719d51ea3ee3d7d05d',
            'client_secret' => '9c39cabcce17683267e0a9a41fa507b4',
            'grant_type' => 'open_id',
        ),
        //放心花
        'fd2773f0024dce786ae8e464' => array(
            'client_id' => 'fd2773f0024dce786ae8e464',
            'client_secret' => 'dcd32a820110fba84ddc78045f52408a',
            'reservation' => ['type' => 'XJDYYJ', 'deadline' => [['length' => 21, 'unit' => 1]]],
        ),
        //云图享花项目
        '85feb125dfc0a0db06f22cb4' => array(
            'client_id' => '85feb125dfc0a0db06f22cb4',
            'client_secret' => '29998ff5b629bbcc274a250cdc999c0e',
            'grant_type' => 'open_id',
        ),
        // 悠融代扣项目
        'd9cb481e6bf0cf5d71530c5893d91631' => array(
            'client_id' => 'd9cb481e6bf0cf5d71530c5893d91631',
            'client_secret' => '107f111736bb92f671dac647ed29af62',
            'grant_type' => 'open_id',
        ),
        //OAPI 转发
        'oapi' => array(
            'client_id' => 'oapi',
            'client_secret' => '8b95f819e6b51d795d131er7se7fb3k1',
            'grant_type' => 'open_id',
        ),
        //即付宝项目
        '7377f017474ea416a2b57548' => array(
            'client_id' => '7377f017474ea416a2b57548',
            'client_secret' => '676ed52576f07e49ce336c058310ef82',
            'redirect_uri' => '',
            'grant_type' => 'developer_credentials',
        ),

         //房贷增量数据接口
        '7f220c0112e0bb3b8a3c4fc5' => array(
            'client_id' => '7f220c0112e0bb3b8a3c4fc5',
            'client_secret' => 'cb85f818e6b51d795d111ee7ae7fb381',
            'redirect_uri' => '',
            'grant_type' => 'developer_credentials',
            'site_id' => 2,
        ),
        //商户通后台数据接口
        '9u37f01r474ea416a2b395p7' => array(
            'client_id' => '9u37f01r474ea416a2b395p7',
            'client_secret' => '293sd53076f07e49ce336c058310ef3b',
            'redirect_uri' => '',
            'grant_type' => 'developer_credentials',
            'site_id' => 51,
        ),
        //荣信汇
        '8365f78859915a7db00e37c6' => array(
            'client_id' => '8365f78859915a7db00e37c6',
            'client_secret' => '93551c334a19377170179fc0a4bf3dc9',
            'redirect_uri' => 'http://rongxh.diyifangdai.com/api',
            'tpl' => array(
                'register' => 'web/views/user/register_h5_8365f78859915a7db00e37c6.html',
                'login' => 'openapi/views/user/login_8365f78859915a7db00e37c6.html',
                'combineRegist' => 'openapi/views/user/combine_regist_8365f78859915a7db00e37c6.html',
            ),
            'js' => array(
                'register' => 'https://www.wangxinlicai.com/api/fzjs?m=rxh&t=register',
                'login' => 'https://www.wangxinlicai.com/api/fzjs?m=rxh&t=login',
                'combineRegist' => 'http://rongxh.diyifangdai.com/Static/firstp2p/combineRegist.js',
                'modifyBank' => 'http://rongxh.diyifangdai.com/Static/firstp2p/modifyBank.js',
            ),
        ),

        //理财师管理平台项目
        'b03841ee67d44dfebddac650' => array(
            'client_id' => 'b03841ee67d44dfebddac650',
            'client_secret' => 'bd10817d6247bcc1d2887b5c6fb9d146',
            'redirect_uri' => 'http://test.lcs.ncfgroup.com/oauth/LoginBack'
        ),

        //房贷微信
        'bb469276d5eb331f2cb7c451' => array(
            'client_id' => 'bb469276d5eb331f2cb7c451',
            'client_secret' => 'a4c43f9a445c59b0bce1c836152d7cde',
            'redirect_uri' => 'http://caish.diyifangdai.com/api',
            'tpl' => array(
                'register' => 'web/views/user/register_h5_bb469276d5eb331f2cb7c451.html',
                'login' => 'openapi/views/user/login_bb469276d5eb331f2cb7c451.html',
                'combineRegist' => 'openapi/views/user/combine_regist_bb469276d5eb331f2cb7c451.html',
            ),
        ),

        //房贷
        'd3e9e24156be0f5b8e1100ac' => array(
            'client_id' => 'd3e9e24156be0f5b8e1100ac',
            'client_secret' => '2ebfa523ba188dc6ecd958b0edf3f445',
            'redirect_uri' => 'http://weixin.diyifangdai.com/api',
            'tpl' => array(
                'register' => 'web/views/user/register_h5_d3e9e24156be0f5b8e1100ac.html',
                'login' => 'openapi/views/user/login_d3e9e24156be0f5b8e1100ac.html',
                'combineRegist' => 'openapi/views/user/combine_regist_d3e9e24156be0f5b8e1100ac.html',
            ),
            'js' => array(
                'register' => '/api/fzjs?m=fangdai&t=register',
                'login' => 'https://www.wangxinlicai.com/api/fzjs?m=fangdai&t=login',
                'combineRegist' => 'http://weixin.diyifangdai.com/Static/firstp2p/combineRegist.js',
                'modifyBank' => 'http://weixin.diyifangdai.com/Static/firstp2p/modifyBank.js',
            ),
        ),
        //理财师 2.0
        '01e592bb43c6b9510ee0951f' => array(
            'client_id' => '01e592bb43c6b9510ee0951f',
            'client_secret' => 'd4eb3d27b2aab394edabe01e92fefb21',
            'redirect_uri' => 'http://lcstest.firstp2p.com/oauth'
        ),
        //weixin
        'b2b6f7468a584ddf6bc2589b' => array(
            'client_id' => 'b2b6f7468a584ddf6bc2589b',
            'client_secret' => '30162a48bb5e44ebb6ec5294aadd0f39',
            'redirect_uri' => 'http://wei.firstp2p.com'
        ),

        // 网信理财师
        '3c5d1b1e446ce5a2a51b3b81' => array(
                'client_id' => '3c5d1b1e446ce5a2a51b3b81',
                'client_secret' => '9dcd8b8b531d7bf45cb6084f79f51cce',
                'redirect_uri' => 'http://lcs.firstp2p.com/oauth'
            ),
        // firstp2p m域
        '7b9bd46617b3f47950687351' => array(
                'client_id' => '7b9bd46617b3f47950687351',
                'client_secret' => '1a398dd609837aba9943db9438db5c0e',
                'redirect_uri' => 'http://m.firstp2p.com/oauth',
                'error_back_uri'=> 'http://m.firstp2p.com/',
                'js' => array(
                    'register' => '/static/v3/js/event/register-tpl.js',
                    'login' => '/static/v3/js/login-tpl.js',
                    'combineRegist' => '/static/v3/js/combine-tpl.js',
                    'modifyBank' => '/static/v3/js/modify-tpl.js',
                )
            ),

        //网信理财wap 新域名
        'db6c30dddd42e4343c82713e' => array(
                'client_id' => 'db6c30dddd42e4343c82713e',
                'client_secret' => 'a95f67220cdbcd30e410af7b28f811a0',
                'redirect_uri' => 'http://m.wangxinlicai.com/oauth',
                'error_back_uri'=> 'http://m.wangxinlicai.com/',
                'js' => array(
                    'register' => '/static/v3/js/event/register-tpl.js',
                    'login' => '/static/v3/js/login-tpl.js',
                    'combineRegist' => '/static/v3/js/combine-tpl.js',
                    'modifyBank' => '/static/v3/js/modify-tpl.js',
                )
            ),

        // 工资宝
        '10e0a47e10db6fe4dcdac5cf' => array(
                'client_id' => '10e0a47e10db6fe4dcdac5cf',
                'client_secret' => 'c87f99da4bda28c65ae809a332e8ea81',
                'redirect_uri' => 'https://salary.trusdom.com/m/firstp2p/index',
                'mosaic' => array(
                    'mobile'=>'moblieFormat',
                    'idno' => 'idnoFormat',
                ),
            ),


        // 哈哈农场
        '6d03d1ab2ac33258fb1b5fcf' => array(
                'site_id' => 67,
                'client_id' => '6d03d1ab2ac33258fb1b5fcf',
                'client_secret' => '107d32580b1e39f31ac10cec2b58b0b3',
                'redirect_uri' => 'https://cytfinance.wangxinlicai.com/Pwap/User/callback',
                'error_back_uri'=> 'https://cytfinance.wangxinlicai.com/',
                'mosaic' => array(
                    'mobile'=>'moblieFormat',
                    'idno' => 'idnoFormat',
                ),
                'tpl' => array(
                    'register' => 'web/views/user/register_h5_6d03d1ab2ac33258fb1b5fcf.html',
                    'login' => 'openapi/views/user/login_6d03d1ab2ac33258fb1b5fcf.html',
                    'combineRegist' => 'openapi/views/user/combine_regist_6d03d1ab2ac33258fb1b5fcf.html',
                    'modifyBank' => 'openapi/views/user/modify_bankcard_6d03d1ab2ac33258fb1b5fcf.html',
                ),
                'js' => array(
                    'register' => '/api/fzjs?m=caiyitong&t=register',
                    'login' => 'https://www.wangxinlicai.com/api/fzjs?m=caiyitong&t=login',
                    'combineRegist' => 'http://static.kcdns.net/caiyitong.combineRegist.js',
                    'modifyBank' => 'http://static.kcdns.net/caiyitong.modifyBank.js',
                ),
            ),
        //影视宝wap
        '64fd6590e7ab66b20b71b5ba' => array(
            'site_id' => 63,
            'client_id' => '64fd6590e7ab66b20b71b5ba',
            'client_secret' => '635371216a307a4a35ec530855cfc915',
            'redirect_uri' => 'http://m.yingshifan.com/Pwap/User/callback',
            'tpl' => array(
                'register' => 'web/views/user/register_h5_64fd6590e7ab66b20b71b5ba.html',
                'login' => 'openapi/views/user/login_64fd6590e7ab66b20b71b5ba.html'
            )
         ),
        // 影视宝
        '5c1ccc24b8e4fed93a694bcb' => array(
                'client_id' => '5c1ccc24b8e4fed93a694bcb',
                'client_secret' => '348608cc0e91233c03e668dc3b1535be',
                'redirect_uri' => 'http://ysb.p2p.x.kcdns.net/Pwap/User/callback',
                'mosaic' => array(
                    'mobile'=>'moblieFormat',
                    'idno' => 'idnoFormat',
                ),
            ),
        //典当联盟wap
        '24cf469b079e3b94ed5a71b9' => array(
            'site_id' => 12,
            'client_id' => '24cf469b079e3b94ed5a71b9',
            'client_secret' => 'fcacf6288d764687a82a6b490070f740',
            'redirect_uri' => 'http://m.cnpawn.cn/Pwap/User/callback',
            'tpl' => array(
                'register' => 'web/views/user/register_h5_24cf469b079e3b94ed5a71b9.html',
                'login' => 'openapi/views/user/login_24cf469b079e3b94ed5a71b9.html'
             )
         ),

        //金融一号wap
        '4f853a4df204ffcd00924517' => array(
            'client_id' => '4f853a4df204ffcd00924517',
            'client_secret' => '409fd147a3dd6da148d55c523a0b85b8',
            'redirect_uri' => 'http://m.jryhpt.com/Pwap/User/callback',
            'mosaic' => array(
                    'mobile'=>'moblieFormat',
                    'idno' => 'idnoFormat',
            ),
            'tpl' => array(
                'register' => 'web/views/user/register_h5_4f853a4df204ffcd00924517.html',
                'login' => 'openapi/views/user/login_4f853a4df204ffcd00924517.html',
        'combineRegist' => 'openapi/views/user/combine_regist_4f853a4df204ffcd00924517.html'
             )
         ),


        // 典当联盟
        '198248cdb63143a6affb86f4' => array(
                'client_id' => '198248cdb63143a6affb86f4',
                'client_secret' => '3b28312c9ce7e5d01d3e941479daa409',
                'redirect_uri' => 'http://www.cnpawn.cn/'
            ),
        // 第1车贷
        '5674bd5534b6fd8292638b13' => array(
                'client_id' => '5674bd5534b6fd8292638b13',
                'client_secret' => 'deb03255f961abe3672869d8fd65243b',
                'redirect_uri' => 'http://www.chedai.com/'
            ),
        // 第一房贷
        '6440ab83d8e24400dc2daaa4' => array(
                'client_id' => '6440ab83d8e24400dc2daaa4',
                'client_secret' => '1ee40e0a063d35dc2d48276ee91697dd',
                'redirect_uri' => 'http://www.diyifangdai.com/'
            ),
        // 租上租
        '8609ab50ff35fc74a582c476' => array(
                'client_id' => '8609ab50ff35fc74a582c476',
                'client_secret' => '3b7bb3ba5b3b9e131e3c4bef3d237810',
                'redirect_uri' => 'http://www.zushangzu.com/'
            ),
        // E收贷
        'abf09fee0c8c4d91c8aa37a2' => array(
                'client_id' => 'abf09fee0c8c4d91c8aa37a2',
                'client_secret' => 'ff2f11da4b98aa848dae9c323ccf5fea',
                'redirect_uri' => 'http://www.esp2p.com/'
            ),
        // 浙江甬贷
        'd03a2d2fcfef21bc86dbc963' => array(
                'client_id' => 'd03a2d2fcfef21bc86dbc963',
                'client_secret' => '3a52e32de0f6ff543bd54bb0ec34a071',
                'redirect_uri' => 'http://www.creditzj.com/'
        ),
        //集团信息部 call center , grant_type = developer_credentials
        '533ad242648e17c6895bf4d6' => array(
                'client_id' => '533ad242648e17c6895bf4d6',
                'client_secret' => '29bbedbda2c607655348b0715233702a',
                'grant_type' => 'developer_credentials'
        ),
        //集团第三方外包
        '3e2e8aac63354efdf2007076' => array(
            'client_id' => '3e2e8aac63354efdf2007076',
            'client_secret' => 'e81047bc833ff6edf33f4a779db6362b',
            'grant_type' => "developer_credentials"
        //'redirect_uri' => 'http://dev.renchoubao.com/get_token'
        ),
        // 艺金融$
        '8a2ea778ca2a7953aa21e34e' => array(
                'client_id' => '8a2ea778ca2a7953aa21e34e',
                'client_secret' => '2b68c4266a4ab0d5a3b587c454463fa7',
                'redirect_uri' => 'http://www.yijinrong.com/'
            ),
        // 山东-岱宗会$
        'a526355bfc1767e5bce99f85' => array(
                'client_id' => 'a526355bfc1767e5bce99f85',
                'client_secret' => '37b7b65b104b0ff554e826b774053368',
                'redirect_uri' => 'http://www.cnp2p.com/'
            ),
        // 天津贷
        '493596f05c2c30813d7d187b' => array(
                'client_id' => '493596f05c2c30813d7d187b',
                'client_secret' => '8a9327f24db90bd508aeb9473a9427b1',
                'redirect_uri' => 'http://www.tianjinp2p.com/'
            ),
        // 中国信贷
        '9a5d8b2d777e160de5afee17' => array(
                'client_id' => '9a5d8b2d777e160de5afee17',
                'client_secret' => 'cea424bc6b6bd2fbc0a71197328dda7e',
                'grant_type' => "developer_credentials"
            ),
        //理财师后台
        '1a4fab3bd9633fa08ef91830' => array(
                'client_id' => '1a4fab3bd9633fa08ef91830',
                'client_secret' => 'c82e0563173714ebf4055739fe355f7e',
                'redirect_uri' => 'http://lcs.ncfgroup.com/oauth/LoginBack'
            ),

        //理财师后台V2
        'dea455fd1789d0b44126462d' => array(
                'client_id' => 'dea455fd1789d0b44126462d',
                'client_secret' => '3fc91612c718ba0df39c54d65a887c35',
                'redirect_uri' => 'http://lcs.wangxinlicai.com/oauth/LoginBack'
            ),

        // 商户通
        '6514ed80788547024e5bb882' => array(
                'client_id' => '6514ed80788547024e5bb882',
                'client_secret' => '47942cb29e6af6eab528f0928c7f3a01',
                'redirect_uri' => 'http://www.shtcapital.cn/'
            ),

        // 荣信汇
        '96cdc769df91e831cf60a7be' => array(
                'client_id' => '96cdc769df91e831cf60a7be',
                'client_secret' => '1770b683ae5c6aa32301052659115de3',
                'redirect_uri' => 'http://www.rxh365.com/',
                'js' => array(
                    'register' => 'http://rongxh.diyifangdai.com/Static/firstp2p/register.js',
                    'login' => 'http://rongxh.diyifangdai.com/Static/firstp2p/login.js',
                    'combineRegist' => 'http://rongxh.diyifangdai.com/Static/firstp2p/combineRegist.js',
                    'modifyBank' => 'http://rongxh.diyifangdai.com/Static/firstp2p/modifyBank.js',
                )
            ),
        //大连贷
        '592f02cf0998f127ec6ea9de' => array(
                'client_id' => '592f02cf0998f127ec6ea9de',
                'client_secret' => 'b17e34a8e65843ebc66adf6d7b8d5d2f',
                'redirect_uri' => 'http://www.daliandai.com/'
            ),
        //上海贷
        '9d74b0f56af15dade4a8743f' => array(
                'client_id' => '9d74b0f56af15dade4a8743f',
                'client_secret' => '39643ec89d74b0f56af15dad50b3367a',
                'redirect_uri' => 'http://www.lendingsh.com/'
            ),
        //沈阳贷
        'c38b47445cbc75f9ae8ce349' => array(
                'client_id' => 'c38b47445cbc75f9ae8ce349',
                'client_secret' => '9ae991be7800054569219e22fca4365d',
                'redirect_uri' => 'http://www.shenyangp2p.com/'
            ),

        //康辉
        '26c4dea7c1de6f0426964937' => array(
            'client_id' => '26c4dea7c1de6f0426964937',
            'client_secret' => '0ca752243a9db20fc1edad37a19949cc',
            'redirect_uri' => 'http://kanghui.x.kcdns.net/user/callback',
            ),

        //粤港
        '993f5e7f0f018f8efc3b86ed' => array(
            'client_id' => '993f5e7f0f018f8efc3b86ed',
            'client_secret' => '8e8d064f0691691c77a7850c7c71c52b',
            'redirect_uri' => 'http://www.yuegangp2p.com/',
            ),
        //网爱理财(金融1号)
        '2ba4e591dcf6f805372fac48' => array(
            'client_id' => '2ba4e591dcf6f805372fac48',
            'client_secret' => '810cc1f1b6f3e7df44aea0e672f9e4df',
            'redirect_uri' => 'http://www.jryhpt.com/',
            ),
        //新疆贷
        'a17e0d80a2a48ddfe0144065' => array(
            'client_id' => 'a17e0d80a2a48ddfe0144065',
            'client_secret' => 'd396307a2986af172d6ba457f27b8379',
            'redirect_uri' => 'http://www.wxxjdp2p.com/?auto_login=true',
            ),
        // 先锋游戏
        'e01e4e865e87f57999f14fce' => array(
            'client_id' => 'e01e4e865e87f57999f14fce',
            'client_secret' => '70c379661241a41af8290ed062fdba2a',
            'redirect_uri' => 'http://www.qg8.com/user/skiploading.html',
            'tpl' => array(
                'register' => 'web/views/user/register_e01e4e865e87f57999f14fce.html',
                )
            ),
        //钱柜游戏
        '3b5883c1f384f73007a0cb0c' => array(
            'client_id' => '3b5883c1f384f73007a0cb0c',
            'client_secret' => 'b8915b9d62a4c213bf7abf2f56af57bc',
            'redirect_uri' => 'http://www.qg8.com/mobile/html/skip/index.html',
            'error_back_uri' => 'http://www.qg8.com/',
            'tpl' => array(
                'register' => 'web/views/user/register_h5_fz.html',
                'login' => 'openapi/views/user/login_fz.html',
                'combineRegister' => 'openapi/views/user/combile_register_fz.html',
                'modifyBank' => 'openapi/views/user/modify_bankcard_fz.html',
            ),
            'js' => array(
                'register' => 'http://www.qg8.com/mobile/scripts/wxstyle/register.js',
                'login' => 'http://www.qg8.com/mobile/scripts/wxstyle/login.js',
                'combineRegist' => 'http://www.qg8.com/mobile/scripts/wxstyle/bindcard.js',
                'modifyBank' => 'http://www.qg8.com/mobile/scripts/wxstyle/bindcard.js',
            )
        ),
        //艺金融wap站
        '5610e2cd133cd29ecf8e32ee' => array(
            'client_id' => '5610e2cd133cd29ecf8e32ee',
            'client_secret' => '0ccaeda2def4e2f03197069fe7e720cd',
            'redirect_uri' => 'http://m.yijinrong.com/Pwap/User/callback',
            'js' => array(
                'register' => 'https://www.wangxinlicai.com/api/fzjs?m=yijinrong',
                'login' => 'https://www.wangxinlicai.com/api/fzjs?m=yijinrong',
                'combineRegist' => 'https://www.wangxinlicai.com/api/fzjs?m=yijinrong',
                'modifyBank' => 'https://www.wangxinlicai.com/api/fzjs?m=yijinrong',
            )
        ),

        //掌众理财
        '64767eae136b547dffc49d28' => array(
            'client_id' => '64767eae136b547dffc49d28',
            'client_secret' => '50d4e3b9d0cb9ffde5de5d08ed6c23ae',
            'reservation' => ['type' => 'ZZJR', 'deadline' => [['length' => 21, 'unit' => 1], ['length' => 50, 'unit' => 1]]],
            'product_type' => array('ZZJR'),
        ),

        //悠融闪信贷
        'cbbc3e85de19e34020db8cfc' => array(
            'client_id' => 'cbbc3e85de19e34020db8cfc',
            'client_secret' => '42d8ffebb3962c4394abc45a28a71e1c',
            'reservation' => ['type' => 'XSJK', 'deadline' => [['length' => 14, 'unit' => 1]]],
            'product_type' => array('XFD'),
        ),

        //大树金融 功夫贷
        '0f7ed5e6ced827be2a39239b' => array(
            'client_id' => '0f7ed5e6ced827be2a39239b',
            'client_secret' => '980df37b6111fdfebcbd1ae683ea8e77',
            'product_type' => array('XJDGFD'),
        ),

        //微信小程序
        'b4a4fbd1c2049167fee6f635' => array(
            'client_id' => 'b4a4fbd1c2049167fee6f635',
            'client_secret' => 'dd3bb14a99cc973d25edc203e92a8a49',
        ),

        //优金
        'bcd8ee0bd148695c4fa9ce76' => array(
                'client_id' => 'bcd8ee0bd148695c4fa9ce76',
                'client_secret' => 'c15f660cc17b67597496d134115117d2',
        ),

        // 零售信贷
        '882962c4ca8d8678d9380a1d' => array(
            'client_id' => '882962c4ca8d8678d9380a1d',
            'client_secret' => 'b8bf98b260bb781ecf86e6f6ee2756ff',
            'grant_type' => "developer_credentials"
        ),

        // 网信房贷
        '99e4f8d09b6411cd68161dad5b0f98f6' => array(
            'client_id' => '99e4f8d09b6411cd68161dad5b0f98f6',
            'client_secret' => 'ef8d9a75fc48d6e778787c337dc34aac',
            'grant_type' => "developer_credentials"
        )

        ),

        'CHANGE_BANKCARD_URL_MAP' => array(
            '7b9bd46617b3f47950687351' => 'http://m.firstp2p.com/account/setbank',
            '6d03d1ab2ac33258fb1b5fcf' => 'https://cytfinance.wangxinlicai.com/user/modifyBank',
        ),

        'SMS_ROUTE' => array (
            'firstp2p' => 'firstp2p',
        //    'chedai' => 'chedai',
            'diyifangdai' => 'diyifangdai',
            'esp2p' => 'esp2p',
            'diandang' => 'diandang',
            'creditzj' => 'creditzj',
            'shenyangdai' => 'shenyangdai',
            'daliandai' => 'daliandai',
            'ronghua' => 'ronghua',
            'zsz' => 'zsz',
            'tianjindai' => 'tianjindai',
            'shandongdai' => 'shandongdai',
            'cunyindai' => 'cunyindai',
            'dongguandai' => 'dongguandai',
            'yijinrong' => 'yijinrong',
            'shanghaidai' => 'shanghaidai',
            'chanrongdai' => 'chanrongdai',
            'jinxind' => 'jinxind',
            'shtcapital' => 'shanghutong',
            'wangailicai' => 'wangailicai',
            'jifubao' => 'jifubao',
            'yuegang' => 'yuegang',
            'yingshi' => 'yingshi',
            'xjd' => 'xjd',
            'caiyitong' => 'caiyitong',
            'qianguiyouxi' => 'qianguiyouxi',
            'dajinsuo' => 'dajinsuo',
            'LinYouMoments' => 'LinYouMoments',//邻友圈
        ),

        //user后台桥接
        'USER_ADMIN_DOMAIN' => "useradmin.firstp2p.cn",
        //open 开放平台后台域名
        'OPEN_ADMIN_DOMAIN' => "open.firstp2padmin.corp.ncfgroup.com",
        //markting 后台域名
        'MARKETING_ADMIN_DOMAIN' => "marketing.firstp2padmin.corp.ncfgroup.com",
        //理财师后台域名
        'LCS_ADMIN_DOMAIN' => "lcs.ncfgroup.com",
        //bonus后台域名
        'BONUS_ADMIN_DOMAIN' => "bonus.firstp2padmin.corp.ncfgroup.com",

        //速贷后台域名
        'CREDITLOAN_ADMIN_DOMAIN' => "admin.creditloan.wangxinlicai.com", // 生产环境速贷后台桥接
        'CREDITLOAN_ADMIN_DOMAIN_PRE' => "preadmin.creditloan.corp.ncfgroup.com", // 灰度环境速贷后台桥接

        // 新版抢红包
        'BONUS_GROUP_GRAB_URL' => "//a.ncfwx.com/bonus_group/grab",

        //ncftrust香港 后台域名
        'NCFTRUSTHK_ADMIN_DOMAIN' => "ncftrust.admin.ncfwx.com",

        //存管配置
        'SUPERVISION' => [
            //对账
            'check' => [
                //ftp
                'ftp_config' => [
                    'ftp_host' => 'oYJKnkwaNRyxIiQ.unitedbank.cn',
                    'ftp_username' => 'm22646207',
                    'ftp_password' => 'BqJ3AFq5',
                    'ftp_port' => '21',
                ],
                'files' => [
                    'recharge' => [
                        'remote' => '/%s/%s/%s_%s_RECHARGE.txt',
                        'local' => '/tmp/%s_supervision_recharge.txt',
                    ],
                    'withdraw' => [
                        'remote' => '/%s/%s/%s_%s_WITHDRAW.txt',
                        'local' => '/tmp/%s_supervision_withdraw.txt',
                    ],
                    'transaction' => [
                        'remote' => '/%s/%s/%s_%s_TRANSACTION.txt',
                        'local' => '/tmp/%s_supervision_transaction.txt'
                    ],
                ],
            ],
        ],
);



/**
 * 和站点相关的配置，包括 oauth 和 站点配置
 *
 * oauth配置文件
 * 和域名相关的参数，默认值为数组的第一组内容
 * 如果要新增域名，可以新增对应的oauth
 *
 *
 *
 */
$siteDomains = array(
    'firstp2p' => 'www.firstp2p.cn',
    'wangxinlicai' => 'www.wangxinlicai.com',
    'ncfwx' => "www.ncfwx.com",
    'firstp2p_alone' => 'www.firstp2p.com',
    'diyifangdai' => 'fangdai.firstp2p.com',
    'diyifangdai_alone' => 'fangdai.firstp2p.com',
    'mulandai' => 'mulandai.jinrongnvlang.com',
    'mulandaicn' => 'www.mulandai.cn',
    'yhp2p' => 'www.yhp2p.cn',
    'yhp2p_alone' => 'www.yhp2p.cn',
    'unitedmoney' => 'e.unitedmoney.com',
    'unitedmoney_alone' => 'e.unitedmoney.com',
    'zsz' => 'zsz.firstp2p.com',
    'zsz_alone' => 'zsz.firstp2p.com',
    'quanfeng' => 'quanfeng-esp2p.firstp2p.com',
    'quanfeng_alone' => 'quanfeng-esp2p.firstp2p.com',
    'fortest' => 'fortest.firstp2p.com',
    'chedai' => 'chedai.firstp2p.com',
    'chedai_alone' => 'chedai.firstp2p.com',
    'diandang' => 'cnpawn.firstp2p.com',
    'diandang_alone' => 'cnpawn.firstp2p.com',
    'tianjindai' => 'tianjindai.firstp2p.com',
    'tianjindai_alone' => 'tianjindai.firstp2p.com',
    'shenyangdai' => 'shenyangdai.firstp2p.com',
    'shenyangdai_alone' => 'shenyangdai.firstp2p.com',
    'shandongdai' => 'cnp2p.firstp2p.com',
    'shandongdai_alone' => 'cnp2p.firstp2p.com',
    'daliandai' => 'daliandai.firstp2p.com',
    'daliandai_alone' => 'daliandai.firstp2p.com',
    'creditzj' => 'creditzj.firstp2p.com',
    'creditzj_alone' => 'creditzj.firstp2p.com',
    'esp2p' => 'esp2p.firstp2p.com',
    'esp2p_alone' => 'esp2p.firstp2p.com',
    'ronghua' => 'rxh.firstp2p.com',
    'ronghua_alone' => 'rxh.firstp2p.com',

    'all'=>'www.firstp2p.com',
    'firstp2p_region'=>'www.firstp2p.com',
    'all-chedai'=>'www.firstp2p.com',

    'jinxind'=>'jinxind.firstp2p.com',
    'jinxind_alone'=>'jinxind.firstp2p.com',
    'dongguandai'=>'xianfengwuliucaifu.firstp2p.com',
    'dongguandai_alone'=>'xianfengwuliucaifu.firstp2p.com',
    'yijinrong'=>'yijinrong.wangxinlicai.com',
    'yijinrong_alone'=>'yijinrong.firstp2p.com',
    'cunyindai'=>'cunyindai.firstp2p.com',
    'cunyindai_alone'=>'cunyindai.firstp2p.com',
    'chanrongdai'=>'chanrongdai.firstp2p.com',
    'chanrongdai_alone'=>'chanrongdai.firstp2p.com',
    'shanghaidai'=>'shanghaidai.firstp2p.com',
    'shanghaidai_alone'=>'shanghaidai.firstp2p.com',
    'all-che-chan'=>'www.firstp2p.com',

    'shtcapital'=>'shtcapital.wangxinlicai.com',
    'shtcapital_alone'=>'shtcapital.firstp2p.com',
    'all-che-chan-sht'=>'www.firstp2p.com',
    'all-che-sht'=>'www.firstp2p.com',

    'all-che-chan-shandong-sht' => 'www.firstp2p.com',
    'all-che-chan-sht-yijinrong' => 'www.firstp2p.com',
    'wangailicai' => 'an.firstp2p.com',
    'wangailicai_alone' => 'an.firstp2p.com',
    'yuegang' => 'yuegang.firstp2p.com',
    'yuegang_alone' => 'yuegang.firstp2p.com',
    'yingshi' => 'yingshi.firstp2p.com',
    'yingshi_alone' => 'yingshi.firstp2p.com',
    'xjd' => "xjd.firstp2p.com",
    'caiyitong' => "caiyitong.wangxinlicai.com",
    'qianguiyouxi' => '7y.firstp2p.com',
    );

$site_title = array(
    'firstp2p' => '网信理财',
    'firstp2p_alone' => '网信理财',
    'diyifangdai' => '第一房贷',
    'diyifangdai_alone' => '第一房贷',
    'mulandai' => '木兰贷',
    'mulandaicn' => '木兰贷',
    'yhp2p' => '盈华财富',
    'yhp2p_alone' => '盈华财富',
    'unitedmoney' => '联合货币',
    'unitedmoney_alone' => '联合货币',
    'zsz' => '租上租',
    'zsz_alone' => '租上租',
    'quanfeng' => '全峰P2P',
    'quanfeng_alone' => '全峰P2P',
    'fortest' => 'ForTest',
    'chedai' => '第1车贷',
    'chedai_alone' => '第1车贷',
    'diandang' => '典当联盟',
    'diandang_alone' => '典当联盟',
    'tianjindai' => '天津贷',
    'tianjindai_alone' => '天津贷',
    'shenyangdai' => '沈阳贷',
    'shenyangdai_alone' => '沈阳贷',
    'shandongdai' => '山东贷',
    'shandongdai_alone' => '山东贷',
    'daliandai' => '大连贷',
    'daliandai_alone' => '大连贷',
    'creditzj' => '浙江甬贷',
    'creditzj_alone' => '浙江甬贷',
    'esp2p' => 'e收贷',
    'esp2p_alone' => 'e收贷',
    'ronghua'=>'荣信汇',
    'ronghua_alone'=>'荣信汇',

    'all'=>'网信理财',
    'firstp2p_region'=>'网信理财',
    'all-chedai'=>'网信理财',

    'jinxind'=>'晋信贷',
    'jinxind_alone'=>'晋信贷',
    'dongguandai'=>'先锋物流财富',
    'dongguandai_alone'=>'先锋物流财富',
    'yijinrong'=>'艺金融',
    'yijinrong_alone'=>'艺金融',
    'cunyindai'=>'村银贷',
    'cunyindai_alone'=>'村银贷',
    'chanrongdai'=>'产融贷',
    'chanrongdai_alone'=>'产融贷',
    'shanghaidai'=>'上海贷',
    'shanghaidai_alone'=>'上海贷',
    'all-che-chan'=>'网信理财',

    'shtcapital'=>'商户通',
    'shtcapital_alone'=>'商户通',

    'all-che-chan-sht'=>'网信理财',
    'all-che-sht'=>'网信理财',

    'all-che-chan-shandong-sht' => '网信理财',
    'all-che-chan-sht-yijinrong' => '网信理财',
    'wangailicai' => '金融1号',
    'wangailicai_alone' => '金融1号',
    'yuegang' => '粤港贷',
    'yuegang_alone' => '粤港贷',
    'yingshi' => '影视宝',
    'yingshi_alone' => '影视宝',
    'xjd' => '新疆贷',
    'caiyitong' => '云图生活',
    'qianguiyouxi' => '钱柜游戏',
    'LinYouMoments' => '邻友圈旺旺财神',
    );

$env_conf['SITE_TILE'] = $site_title;

$host_conf = array(
    'www.firstp2p.com'=>array(
        'APP_SITE'                => 'firstp2p',
        'SITE_LIST_TITLE'            => $site_title,
        'SITE_DOMAIN'            => $siteDomains,
    ),
    'www.firstp2p.cn'=>array(
        'APP_SITE'           => 'firstp2p',
        'STATIC_DOMAIN_ROOT' => '.firstp2p.cn',
        'SITE_DOMAIN'        => $siteDomains,
    ),
    'www.wangxinlicai.com'=>array(
        'APP_SITE'                => 'firstp2p',
        'SITE_LIST_TITLE'            => $site_title,
        'SITE_DOMAIN'            => $siteDomains,
        'STATIC_DOMAIN_ROOT' => '.wangxinlicai.com'
    ),
    "www.ncfwx.com"=>array(
        'APP_SITE'                => 'firstp2p',
        'SITE_LIST_TITLE'            => $site_title,
        'SITE_DOMAIN'            => $siteDomains,
        'STATIC_DOMAIN_ROOT' => '.ncfwx.com',
    ),
    'fangdai.firstp2p.com'=>array(
        'APP_SITE'                => 'diyifangdai',
        'SITE_LIST_TITLE'            => $site_title,
        'SITE_DOMAIN'            => $siteDomains,
        'TPL_HEADER' => 'web/views/custom_site/diyifangdai/header.html',
        'TPL_LOGIN' => 'web/views/custom_site/diyifangdai/login.html',
        'TPL_REGISTER' => 'web/views/custom_site/diyifangdai/register.html',
    ),
    'mulandai.jinrongnvlang.com'=>array(
        'APP_SITE'                => 'mulandai',
        'SITE_LIST_TITLE'            => $site_title,
        'SITE_DOMAIN'            => $siteDomains,
    ),
    'www.mulandai.cn'=>array(
        'APP_SITE'                => 'mulandaicn',
        'SITE_LIST_TITLE'            => $site_title,
        'SITE_DOMAIN'            => $siteDomains,
    ),
    'www.yhp2p.cn'=>array(
        'APP_SITE'                => 'yhp2p',
        'SITE_LIST_TITLE'            => $site_title,
        'SITE_DOMAIN'            => $siteDomains,
    ),
    'yh.firstp2p.com'=>array(
        'APP_SITE'                => 'yhp2p',
        'SITE_LIST_TITLE'            => $site_title,
        'SITE_DOMAIN'            => $siteDomains,
    ),
    'an.firstp2p.com'=>array(
        'APP_SITE'                => 'wangailicai',
        'SITE_LIST_TITLE'            => $site_title,
        'SITE_DOMAIN'            => $siteDomains,
        'TPL_HEADER' => 'web/views/custom_site/wangailicai/header.html',
    ),
    'e.unitedmoney.com'=>array(
        'APP_SITE'                => 'unitedmoney',
        'SITE_LIST_TITLE'            => $site_title,
        'SITE_DOMAIN'            => $siteDomains,
    ),
    'um.firstp2p.com'=>array(
        'APP_SITE'                => 'unitedmoney',
        'SITE_LIST_TITLE'            => $site_title,
        'SITE_DOMAIN'            => $siteDomains,
    ),
    'zsz.firstp2p.com'=>array(
        'APP_SITE'                => 'zsz',
        'SITE_LIST_TITLE'            => $site_title,
        'SITE_DOMAIN'            => $siteDomains,
        'TPL_HEADER' => 'web/views/custom_site/zsz/header.html',
    ),
    'quanfeng.firstp2p.com' => array(
        'APP_SITE' => 'quanfeng',
        'SITE_LIST_TITLE'            => $site_title,
        'SITE_DOMAIN' => $siteDomains,
    ),
    'quanfeng-esp2p.firstp2p.com' => array(
        'APP_SITE' => 'quanfeng',
        'SITE_LIST_TITLE'            => $site_title,
        'SITE_DOMAIN' => $siteDomains,
    ),
    'fortest.firstp2p.com' => array(
        'APP_SITE' => 'fortest',
        'SITE_LIST_TITLE'            => $site_title,
        'SITE_DOMAIN' => $siteDomains,
    ),
    'chedai.firstp2p.com' => array(
        'APP_SITE' => 'chedai',
        'SITE_LIST_TITLE'            => $site_title,
        'SITE_DOMAIN' => $siteDomains,
        'TPL_HEADER' => 'web/views/custom_site/chedai/header.html',
        'TPL_LOGIN' => 'web/views/custom_site/chedai/login.html',
        'TPL_REGISTER' => 'web/views/custom_site/chedai/register.html',
    ),
    'cnpawn.firstp2p.com' => array(
        'APP_SITE' => 'diandang',
        'SITE_LIST_TITLE'            => $site_title,
        'SITE_DOMAIN' => $siteDomains,
        'TPL_HEADER' => 'web/views/custom_site/diandang/header.html',
    ),
    'tianjindai.firstp2p.com' => array(
        'APP_SITE' => 'tianjindai',
        'SITE_LIST_TITLE'       => $site_title,
        'SITE_DOMAIN' => $siteDomains,
        'TPL_HEADER' => 'web/views/custom_site/tianjindai/header.html',
        'TPL_LOGIN' => 'web/views/custom_site/login.html',
        'TPL_REGISTER' => 'web/views/custom_site/register.html',
    ),
    'shenyangdai.firstp2p.com' => array(
        'APP_SITE' => 'shenyangdai',
        'SITE_LIST_TITLE'       => $site_title,
        'SITE_DOMAIN' => $siteDomains,
        'TPL_HEADER' => 'web/views/custom_site/shenyangdai/header.html',
    ),
    'cnp2p.firstp2p.com' => array(
        'APP_SITE' => 'shandongdai',
        'SITE_LIST_TITLE'       => $site_title,
        'SITE_DOMAIN' => $siteDomains,
        'TPL_HEADER' => 'web/views/custom_site/shandongdai/header.html',
        'TPL_LOGIN' => 'web/views/custom_site/login.html',
        'TPL_REGISTER' => 'web/views/custom_site/register.html',
    ),
    'daliandai.firstp2p.com' => array(
        'APP_SITE' => 'daliandai',
        'SITE_LIST_TITLE'       => $site_title,
        'SITE_DOMAIN' => $siteDomains,
        'TPL_HEADER' => 'web/views/custom_site/daliandai/header.html',
    ),
    'creditzj.firstp2p.com' => array(
        'APP_SITE' => 'creditzj',
        'SITE_LIST_TITLE'       => $site_title,
        'SITE_DOMAIN' => $siteDomains,
        'TPL_HEADER' => 'web/views/custom_site/creditzj/header.html',
        'TPL_LOGIN' => 'web/views/custom_site/login.html',
        'TPL_REGISTER' => 'web/views/custom_site/register.html',
    ),
    'esp2p.firstp2p.com' => array(
        'APP_SITE' => 'esp2p',
        'SITE_LIST_TITLE'       => $site_title,
        'SITE_DOMAIN' => $siteDomains,
        'TPL_HEADER' => 'web/views/custom_site/esp2p/header.html',
    ),
    'rxh.firstp2p.com' => array(
        'APP_SITE' => 'ronghua',
        'SITE_LIST_TITLE'       => $site_title,
        'SITE_DOMAIN' => $siteDomains,
        'TPL_HEADER' => 'web/views/custom_site/ronghua/header.html',
    ),
    'jinxind.firstp2p.com' => array(
        'APP_SITE' => 'jinxind',
        'SITE_LIST_TITLE'       => $site_title,
        'SITE_DOMAIN' => $siteDomains,
    ),
    'xianfengwuliucaifu.firstp2p.com' => array(
        'APP_SITE' => 'dongguandai',
        'SITE_LIST_TITLE'       => $site_title,
        'SITE_DOMAIN' => $siteDomains,
    ),
    'yijinrong.wangxinlicai.com' => array(
        'APP_SITE' => 'yijinrong',
        'SITE_LIST_TITLE'       => $site_title,
        'SITE_DOMAIN' => $siteDomains,
        'TPL_HEADER' => 'web/views/custom_site/yijinrong/header.html',
        'TPL_LOGIN' => 'web/views/custom_site/login.html',
        'TPL_REGISTER' => 'web/views/custom_site/register.html',
    ),
    'cunyindai.firstp2p.com' => array(
        'APP_SITE' => 'cunyindai',
        'SITE_LIST_TITLE'       => $site_title,
        'SITE_DOMAIN' => $siteDomains,
    ),
    'chanrongdai.firstp2p.com' => array(
        'APP_SITE' => 'chanrongdai',
        'SITE_LIST_TITLE'       => $site_title,
        'SITE_DOMAIN' => $siteDomains,
    ),
    'shanghaidai.firstp2p.com' => array(
        'APP_SITE' => 'shanghaidai',
        'SITE_LIST_TITLE'       => $site_title,
        'TPL_HEADER' => 'web/views/custom_site/shanghaidai/header.html',
        'SITE_DOMAIN' => $siteDomains,
    ),
    'shtcapital.wangxinlicai.com' => array(
        'APP_SITE' => 'shtcapital',
        'SITE_LIST_TITLE'       => $site_title,
        'TPL_HEADER' => 'web/views/custom_site/shtcapital/header.html',
        'TPL_LOGIN' => 'web/views/custom_site/shtcapital/login.html',
        'TPL_REGISTER' => 'web/views/custom_site/shtcapital/register.html',
        'SITE_DOMAIN' => $siteDomains,
    ),
    'yuegang.firstp2p.com'=>array(
        'APP_SITE'                => 'yuegang',
        'SITE_LIST_TITLE'            => $site_title,
        'TPL_HEADER' => 'web/views/custom_site/yuegang/header.html',
        'SITE_DOMAIN'            => $siteDomains,
    ),
    'yingshi.firstp2p.com'=>array(
        'APP_SITE'                => 'yingshi',
        'SITE_LIST_TITLE'            => $site_title,
        'SITE_DOMAIN'            => $siteDomains,
    ),
    "xjd.firstp2p.com" => array(
        'APP_SITE' => 'xjd',
        'SITE_LIST_TITLE'       => $site_title,
        'SITE_DOMAIN' => $siteDomains,
        'TPL_HEADER' => 'web/views/custom_site/xjd/header.html',
    ),
    "caiyitong.wangxinlicai.com" => array(
        'APP_SITE' => 'caiyitong',
        'SITE_LIST_TITLE'       => $site_title,
        'SITE_DOMAIN' => $siteDomains,
        'TPL_LOGIN' => 'web/views/custom_site/caiyitong/login.html',
        'TPL_REGISTER' => 'web/views/custom_site/caiyitong/register.html',
    ),
    "7y.firstp2p.com" => array(
        'APP_SITE' => 'qianguiyouxi',
        'SITE_LIST_TITLE'       => $site_title,
        'SITE_DOMAIN' => $siteDomains,
    ),
);

list($host_default) = array_values($host_conf);
return array_merge($env_conf, isset($host_conf[APP_HOST]) ? $host_conf[APP_HOST] : $host_default);


function get_https()
{
    return (isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) != 'off')) ? 'https://' : 'http://';
}
