<?php

namespace libs\utils;
use libs\utils\Logger;


class LoggerBusiness{
    static $webMap = array(
        '/' => '首页',
        '/HONGBAO/GRAB' => '二维码红包活动',
        '/PAYMENT/WITHDRAWNOTIFY' => '提现回调接口',
        '/USER/LOGIN' => '用户登录页',
        '/USER/DOLOGIN' => '用户登录',
        '/PAYMENT/YEEPAYCHARGENOTIFY' => '易宝-充值回调接口',
        '/PAYMENT/CHARGENOTIFY' => '充值回调接口',
        '/PAYMENT/TRANSFERNOTIFY' => '转账回调接口',
        '/PAYMENT/OFFLINECHARGENOTIFY' => '线下充值回调接口',
        '/DEAL/ASYNC' => '异步查询收益率',
        '/DEAL/DOBID' => '投资',
        '/PRODUCT' => '产品页',
        '/ACCOUNT/DOCHARGE' => '充值',
        '/ACCOUNT/SAVECARRY' => '提现',
        '/FINPLAN/DOBID' => '智多新投资确认页',
        '/FINPLAN/BID' => '智多新标的详情页',
        '/DEALS/INDEX' => '标的列表页',
        '/PAYMENT/STARTPAY'=>'个人中心充值页',
        '/PAYMENT/PAYCHECK'=>'充值检查',
        '/PAYMENT/PAY'=>'个人中心充值确认页',
    );

    static $apiMap = array(
        '/DEAL/BID' => '出借',
        '/DEAL/CONFIRM' => '出借确认页',
        '/DEAL/BIDRETURN' => '出借存管回调接口',
        '/DEAL/PREBID' => 'API投资前尝试划转余额',
        '/DEALS/DEALLIST' => '标的列表页',
        '/DEALS/DEALCLASSIFYLIST' => '标的分类列表',
        '/DEALS/APPOINTMENTS' => '随心约预约列表',
        '/DEALS/DETAIL' => '订单详情页',

        '/DEAL/RESERVECONF' => 'wap站随鑫约投标',
        '/DEAL/RESERVE' => '随心约短期标提交预约页',
        '/DEAL/RESERVEINDEX' => '随心约短期标预约首页',
        '/DEAL/RESERVEDETAIL'=>'随心约预约详情页',

        '/ACCOUNT/SUMMARY' => 'APP用户资产详情接口',
        '/ACCOUNT/LOANCALENDAR' => '回款计划日历',

        '/ACTIVITY/CHECKIN'=>'用户签到',

        '/VIP/VIPACCOUNT' => 'VIP首页接口',
        '/USER/SPLASH' => '闪屏接口',
        '/USER/INFO' => '用户信息接口',

        '/PAYMENT/TRANSIT' => '跳转存管系统H5页面',
        '/PAYMENT/QUERYLIMIT' => 'APP充值页面银行限额查询接口',
        '/PAYMENT/CASHOUT' => 'APP提现',
        '/PAYMENT/CREATEORDER' => 'APP创建充值订单',
        '/PAYMENT/OFFLINECHARGE' =>'大额充值',
        '/PAYMENT/START' => 'APP充值先锋支付充值渠道入口页面 ',
        '/PAYMENT/YEEPAYSTARTPAYH5' => 'APP充值易宝充值渠道入口页面 ',
        '/PAYMENT/TRANSFER' => 'App投资划转接口 ',
        '/PAYMENT/APPLY' => '手机充值',
        '/PAYMENT/CHANNELLIST' => '获取当前可用的支付方式列表-接口',
        '/PAYMENT/YEEPAYCONFIRMBINDCARDH5' => '易宝-绑卡确认页面-APP',
        '/PAYMENT/YEEPAYBINDCARDVCODEH5' => '易宝-绑卡-短信验证码页面-APP',
        '/PAYMENT/EDITBANK' => '修改银行卡页面',

        '/USER/SIGNUP' => '注册',
        '/USER/LOGIN' => '登录',
        '/USER/AUTHINFO' => '实名认证',
        '/USER/BINDCARD' => '绑卡',

        '/DUOTOU/INDEX' => '智多新首页标的列表',
        '/DUOTOU/LOADLIST' => '已投资列表接口',
        '/DUOTOU/USERLOANMONEY' => '用户持有资产及收益信息接口',
        '/DUOTOU/DEALDETAIL' => '智多新标的详情页',

        '/CANDY/SUMMARY' => '信宝首页',
        '/CANDYSNATCH/SNATCHPRODUCT' => '信宝夺宝单个商品页',
        '/CANDYSNATCH/SNATCHAUCTION' => '信宝夺宝首页 ',
        '/CANDY/BONUS' => '信宝红包页面',
        '/CANDYSNATCH/SNATCHPASTPERIOD' => '信宝夺宝往期记录',
        '/CANDY/LOG' => '信宝资金记录页面',

        '/SHORTCUTS/USERSHORTCUTS' => '用户快捷入口接口',
    );

    static $log = array();
    static $isZhugeLog = false;

    static function init($platform = '', $busiParam = array(), $isZhugeLog = false)
    {
        self::$log['platform'] = $platform;
        self::$log['channel'] = $platform;
        self::getClient();
        self::getLogInfo($platform, $busiParam);
        self::$isZhugeLog = app_conf('ZHUGE_LOG_SWITCH') || $isZhugeLog;
    }


    /**
     *  获取来源设备信息
     */
    static function getClient(){
        if (isset($_SERVER['HTTP_OS'])) {
            self::$log['channel'] = isset($_SERVER['HTTP_CHANNEL']) ? $_SERVER['HTTP_CHANNEL'] : 'NO_CHANNEL';
            self::$log['device_model'] = $_SERVER['HTTP_OS'];
        } elseif ('api' == self::$log['platform'] && self::isWapCall()) {
            self::$log['channel'] = 'wap';
            self::getOs();
        }
    }

    /**
     * wap设备os获取
     */
    static function getOs(){
        if (stripos($_SERVER['HTTP_USER_AGENT'], 'ios') !== false) {
            self::$log['device_model'] = 'ios';
        } elseif (stripos($_SERVER['HTTP_USER_AGENT'], 'android') !== false) {
            self::$log['device_model'] = 'android';
        }
    }

    /**
     * 获取日志需求信息
     */
    static function getLogInfo($platform, $busiParam){
        $logId = Logger::getLogId();
        //日志基本信息
        $base_info = array(
            'log_id' => $logId,
            'log_time' => date(Logger::$format),
            'level' => 'business',
            'req_server' => $_SERVER['HTTP_HOST'],
            'req_url' => $_SERVER['REQUEST_URI'],
            'req_type' => $_SERVER['REQUEST_METHOD'],
            'ip' => trim(get_real_ip(),"'"),
            'uuid' => md5($logId . microtime() . mt_rand(1,9999)),
            'referer' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '',
            'source_page' => isset($_SERVER['HTTP_REFERER']) ? self::translateUri($_SERVER['HTTP_REFERER'],true) : '',
            'target_page' =>  self::translateUri($_SERVER['REQUEST_URI']),
            'busi_name' => !empty($busiParam['busi_name']) ? $busiParam['busi_name'] : '',
            'user_id' => !empty($GLOBALS['user_info']) ? $GLOBALS['user_info']['id'] : '',
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
        );
        if (self::$log['platform'] == 'api') {
            $base_info['finger_print'] = isset($_SERVER['HTTP_FINGERPRINT']) ? $_SERVER['HTTP_FINGERPRINT'] : '';
            $base_info['app_version'] = isset($_SERVER['HTTP_VERSION']) ? $_SERVER['HTTP_VERSION'] : '';
        }
        //把日志基本信息、外部传入信息合并
        self::$log = array_merge($base_info, self::$log, $busiParam);
    }

    /**
     * 统一页面地址为相对路径
     * @param $page
     * @return mixed
     */
    static function translateUri($url, $isSource=false)
    {
        $urlArr = parse_url($url);
        if (!empty($urlArr['path'])) {
            // 可能没有host 避免notice
            $host = isset($urlArr['host']) ? $urlArr['host'] : '';
            if (!preg_match('/(firstp2p|ncfwx)+/', $host) && $isSource){
                return '外部页面';
            }
            $action = strtoupper($urlArr['path']);
            if (preg_match('/\/D\/.*/', $action)) {
                return '标的详情页';
            }
            if (self::$log['platform'] == 'web') {
                return isset(self::$webMap[$action]) ? self::$webMap[$action] : urldecode($action);
            }
            if (self::$log['platform'] == 'api') {
                return isset(self::$apiMap[$action]) ? self::$apiMap[$action] : urldecode($action);
            }
        }
        return '';
    }
    /**
     * @param $platform 来源
     * @param $userId 用户id
     * @param $log 业务日志
     */
    static function zhugeLog($userId, $jsonLog)
    {
        $eventName = self::$log['busi_name'];
        if('web' == self::$log['channel']){
            $zhugeSource = Zhuge::APP_MOBILE;
        } else {
            $zhugeSource = Zhuge::APP_WEB;
        }
        (new Zhuge($zhugeSource))->event($eventName,$userId, ['busi_log' => $jsonLog]);
    }

    /**
     * wap端调用api接口
     */
    static function isWapCall() {
        return isset($_REQUEST['format']) && 'json' == $_REQUEST['format'];
    }

    /**
     * 写log
     */
    static function write($platform = '' , $busiParam = array()){
        //write log
        self::init($platform,$busiParam);
        $jsonLog = json_encode(self::$log, JSON_UNESCAPED_UNICODE);
        $jsonLog = str_replace('\/', '/', $jsonLog);
        Logger::business($jsonLog);
        if (self::$isZhugeLog) {
            //TODO
            self::zhugeLog(self::$log['user_id'],$jsonLog);
        }
    }
}



