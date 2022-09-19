<?php
/**
 * BaseAction class file.
 *
 * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
 **/
namespace web\controllers;

use NCFGroup\Common\Extensions\Base\SimpleRequestBase;
use NCFGroup\Task\Services\TaskService AS GTaskService;
use core\event\RiskCheckEvent;
use libs\rpc\Rpc;
use libs\utils\Site;
use libs\utils\PaymentApi;
use libs\web\Action;
use libs\web\Url;
use libs\web\Open;
use libs\web\Bind;
use libs\common\ErrCode;
use core\service\PassportService;
use core\service\UserService;
use core\service\vip\VipService;
use core\service\OpenService;
use core\service\BwlistService;
use libs\utils\LoggerBusiness;
/**
 * BaseAction class
 *
 * @packaged default
 * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
 **/
class BaseAction extends Action
{
    private $_notLogin = array(
        'account'=>array('bonusAsync'),
        'index'=>array('cate'),
        'user'=>array('login','doLogin','register','doRegister','forgetPassword','ccGetVip'),
    );

    private $_p2p_allow_controller = array(
        'index',
        'deals',
        'deal',
        'help',
        'article',
        'feedback',
        'finplan',
        'app',
        'agreement',
        'news',
        'adv',
    );


    public $v2tpls = array(
        'web/controllers/index/index',
        'web/controllers/index/cate',
        'web/controllers/deals/index',
        'web/controllers/jijin/index',
        'web/controllers/landingpage/index',
        'web/controllers/user/register',
        'web/controllers/user/registercompany',
        'web/controllers/user/doregister',
        'web/controllers/account/addbank',
        'web/controllers/account/discount',
        'web/controllers/account/addemail',
        'web/controllers/account/baseinfocompany',
        'web/controllers/event/mcdonalds',
        'web/controllers/event/game',
        'web/controllers/user/editmb',
        'web/controllers/user/editpwd',
        'web/controllers/user/forgetpwd',
        'web/controllers/user/renewpwd',
        'web/controllers/user/resetpwd',
        'web/controllers/deal/promptcompany',
        'web/controllers/user/forgetpwdcompany',
        'web/controllers/user/doforgetpwd',
        'web/controllers/user/forgetpwdidno',
        'web/controllers/payment/yeepay',
        'web/controllers/payment/yeepayvalidate',
        'web/controllers/payment/yeepaybindcard',
        'web/controllers/payment/yeepayconfirmpay'
    );

    public $rpc;
    public $appInfo  = array();
    public $openSets = array();
    public $appAdvs = array();
    public $is_wxlc;
    public $is_firstp2p;
    public $isEnterprise;
    public $log = array();
    public $businessLog = array();

    const IS_H5 = true;

    // 是否检查未完成充值订单
    const IS_CHECK_CHARGE = 1;

    public function __construct()
    {
        parent::__construct();

        // 给日志参数默认值
        $this->log['process'] = microtime(true);
        $this->businessLog['req_time'] = time();
        $this->log['level'] = 'info';

        $class_path = strtolower(str_replace('\\', '/', get_class($this)));
        $arr_path = explode("/", $class_path);
        if ($arr_path[2] == 'hongbao' && $arr_path[3] == 'grab') {
            // 红包授权页面不走open逻辑，不进行跳转
        } else {
            $this->loadOpenData();
        }


        // p2p逻辑拆分
        $this->is_wxlc = is_wxlc();
        $this->is_firstp2p = is_firstp2p();

        $this->rpc = new Rpc();
        $this->template = $this->getTemplate(); //覆盖phoenix框架的模板路径
        $this->setTpl();
        $this->initCommon();

        $this->sessionShare();
        $this->setOpenData2Tpl();

        if ($this->is_firstp2p) {
            $class_path = strtolower(str_replace('\\', '/', get_class($this)));
            $arr_path = explode("/", $class_path);
            //存管开关
            if (!empty($this->isSvOpen)) {
                array_push($this->_p2p_allow_controller, 'account', 'user', 'payment', 'supervision', 'message');
            }
            // 条件包含session打通特例
            if (!in_array($arr_path[2], $this->_p2p_allow_controller) && !('user' == $arr_path[2] && 'session' == $arr_path[3]) && !('user' == $arr_path[2] && 'transferconfirm' == $arr_path[3]) && !('activity' == $arr_path[2] && 'newuserp2p' == $arr_path[3])
            && !('activity' == $arr_path[2] && 'newuserpage' == $arr_path[3])) {
                header(sprintf('location://%s%s', $this->getWxlcDomain(), $_SERVER['REQUEST_URI']));
                exit;
            }
        }

    }


    /**
     * 设置模板类
     *
     * @return void
     * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
     **/
    private function setTpl()
    {
        $this->tpl = Open::getTemplateEngine();
        $this->tpl->asset = \SiteApp::init()->asset;
        $this->tpl->cache_dir    = APP_RUNTIME_PATH.'app/tpl_caches';
        $this->tpl->compile_dir  = APP_RUNTIME_PATH.'app/tpl_compiled';
        $this->tpl->template_dir = APP_ROOT_PATH;
        $class_path = strtolower(str_replace('\\', '/', get_class($this)));
        $arr_path = explode("/", $class_path);

        // 检测是否需要跳转到https
        $this->headerHttps($arr_path[2],$arr_path[3]);

        $this->tpl->assign("MODULE_NAME", $arr_path[2]);
        $this->tpl->assign("ACTION_NAME", $arr_path[3]);
        $this->tpl->assign("LANG", $GLOBALS['lang']);
        // 同时支持http和https
        $this->tpl->assign("APP_SKIN_PATH",str_replace("http://", "//", APP_SKIN_PATH));
        $tmpl_new_path = APP_ROOT."/static/".app_conf("STATIC_FILE_VERSION");
        $this->tpl->assign("APP_ROOT", APP_ROOT);
        $company_name = '';
        if ($this->is_firstp2p){
            $company_name = '北京东方联合投资管理有限公司';
        }
        if ($this->is_wxlc){
            $company_name = '北京经讯时代科技有限公司';
        }
        $this->tpl->assign("COMPANY_NAME",$company_name);
        $this->tpl->assign('bonus_coupon_trun_on',app_conf("COUPON_REREFER_REBATE_BY_RED_TRUN_ON"));
        $this->tpl->assign("TMPL_NEW",$tmpl_new_path);
        $GLOBALS['tmpl']->assign("help_title",  (APP_SITE == 'firstp2p' ? "新手指南" : "帮助"));
        //增加商家标志
        $isSeller = \es_session::get('isSeller');
        $this->tpl->assign("isSeller", $isSeller);
        if(isset($_GET['euid']) && $this->is_wxlc){
            $url = app_conf('FIRSTP2P_CN_DOMAIN').'?euid='.$_GET['euid'];
            $this->tpl->assign('cross_euid', $url);
        }
        // 红包币名称全局变量
        $this->tpl->assign('new_bonus_title', app_conf('NEW_BONUS_TITLE'));
        $this->tpl->assign('new_bonus_unit', app_conf('NEW_BONUS_UNIT'));

        //账户类型名称
        $accountInfo = $this->rpc->local('ApiConfService\getAccountNameConf');
        $this->tpl->assign('wxAccountConfig', $accountInfo[0]);
        $this->tpl->assign('p2pAccountConfig', $accountInfo[1]);
        $this->tpl->assign('is_nongdan', is_nongdan_site());
    }

    private function sessionShare() {
        $this->tpl->assign("is_wxlc", $this->is_wxlc);
        $this->tpl->assign("is_firstp2p", $this->is_firstp2p);
        $this->tpl->assign("wxlc_domain", $this->getWxlcDomain());
        $this->tpl->assign("firstp2p_cn_domain", app_conf('FIRSTP2P_CN_DOMAIN'));

        //给普惠的参数
        $options = isset($this->appInfo['inviteCode']) && !empty($this->appInfo['inviteCode']) ? ['cn' => $this->appInfo['inviteCode']] : [];
        $params  = \libs\web\Open::getFenzhanParams($options);
        $params_to_p2pcn = empty($params) ? '' : '&' . http_build_query($params);
        $this->tpl->assign('params_to_p2pcn', $params_to_p2pcn);

        //随机码
        $code = md5(uniqid() . rand(100000, 999999) . microtime(true));
        $this->tpl->assign("code", $code);

        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $redis->setex($code, 30, session_id());
    }

    /**
     * 初始化action的公用页面变量
     * @return void
     */
    private function initCommon() {

        // 通行证登录，且不是本地账号，跳过强制修改密码逻辑
        $passportService = new PassportService();
        if (\es_session::get('ppId') && $passportService->isThirdPassport($GLOBALS['user_info']['mobile'], false)) {
            $GLOBALS['user_info']['force_new_passwd'] = 0;
            \es_session::set("user_info", $GLOBALS['user_info']);
        }

        // 是否是企业用户判断
        $this->isEnterprise = (isset($GLOBALS['user_info']['user_type']) && $GLOBALS['user_info']['user_type'] == \core\dao\UserModel::USER_TYPE_ENTERPRISE) || (!empty($GLOBALS['user_info']['mobile']) && substr($GLOBALS['user_info']['mobile'], 0, 1) == 6) ? 1 : 0;

        $this->tpl->assign('isEnterprise', $this->isEnterprise);
        $this->tpl->assign('isEnterpriseSite', is_qiye_site());

        //输出导航菜单
        $nav_list= $this->_initNavList(get_nav_list(), true);
        foreach($nav_list as $k=>$v){
            $nav_list[$k]['sub_nav'] = $this->_initNavList($v['sub_nav'], false);
            if($GLOBALS['sys_config']['APP_SITE'] == 'firstp2p') continue;

            if(strpos($nav_list[$k]['url'],'/app') !== false){
                unset($nav_list[$k]);
                continue;
            }
            if(strpos($nav_list[$k]['url'],'/jijin') !== false){
                unset($nav_list[$k]);
                continue;
            }
            if(strpos($nav_list[$k]['url'],'/finplan/lists') !== false){
                unset($nav_list[$k]);
                continue;
            }
        }

        // 投资劵开关
        $siteId = Site::getId();
        $this->isDiscountAvaliable = $this->rpc->local('DiscountService\siteSwitch', [$siteId]);
        $this->tpl->assign("isDiscountAvaliable", $this->isDiscountAvaliable);

        // 新手专区开关
        $user_info = $GLOBALS['user_info'];
        $user_id = isset($user_info['id']) ? $user_info['id'] : '';
        $user_reg_time = isset($user_info['create_time']) ? $user_info['create_time'] : '';
        $eventIntroHidden = isset($_REQUEST['event_intro_hidden']) ? intval($_REQUEST['event_intro_hidden']) : '';
        if (empty($eventIntroHidden)) {
            $eventIntroHidden = \es_cookie::get('event_intro_hidden');
        }
        $this->isNewUser = $eventIntroHidden ? 0 : $this->rpc->local('NewUserPageService\isNewUser',array($user_id,$user_reg_time));
        $this->tpl->assign("isNewUser", $this->isNewUser);

        // 过滤导航中的投资券
        if (!$this->isDiscountAvaliable)
            $nav_list = $this->filterNavList($nav_list, '投资券');

        $this->tpl->assign("nav_list",$nav_list);

        // 页面关键字、标题、描述
        $this->tpl->assign("site_info", get_site_info());


        if(!empty($user_info)) {
            if((int)app_conf('USER_JXSD_TRANSFER_SWITCH') !== 1) {
                $user_info['is_dflh'] = 0;
            }else{
                $user_info['is_dflh'] = intval($user_info['is_dflh']);
            }

            //强制新密码与弹出冲突 所以强制改密码的时候补弹窗
            if($GLOBALS['user_info']['force_new_passwd'] == 1) {
                $user_info['is_dflh'] = 0;
            }
            $GLOBALS['user_info'] = $user_info;
            //合规用户黑名单
            $mobile = $GLOBALS['user_info']['mobile'] ? $GLOBALS['user_info']['mobile'] : 0;
            $GLOBALS['user_info']['isCompliantUser'] = intval(BwlistService::inList('COMPLIANCE_BLACK', $GLOBALS['user_info']['id']) || BwlistService::inList('COMPLIANCE_BLACK', $mobile));
        }
        if (!empty($user_info['id'])) {
            $userBankcardInfo = $this->rpc->local('UserBankcardService\getBankcard', array($user_info['id']));
        }
        //存管开关
        $this->isSvOpen = $this->rpc->local('SupervisionBaseService\isSupervisionOpen');
        $this->tpl->assign("isSvOpen", $this->isSvOpen);
        // 设置用户信息
        $this->tpl->assign("user_info", $user_info);
        // 网信账户是否已授权
        //$this->tpl->assign('isWxFreepayment', (in_array($siteId, [1, 100]) && $this->isSvOpen && !empty($userBankcardInfo['bankcard'])) ? (int)$user_info['wx_freepayment'] : 1);
        $this->tpl->assign('isWxFreepayment', 1);
        // 模板设置企业用户状态
        if ($this->isEnterprise) {
            $userService = new \core\service\UserService($GLOBALS['user_info']['id']);
            $enterpriseInfo = $userService->getEnterpriseInfo();
            $this->tpl->assign('enterpriseInfo', $enterpriseInfo);
        }

        //全局日志id
        $this->tpl->assign('logId', \libs\utils\Logger::getLogId());

        // 设置前端上传限制
        $this->tpl->assign('max_image_size', 1.5);

        //TODO: 新手用戶注册后，第一次登陆首页弹出勋章新手任务的弹窗。
        $medalBeginner = false;
        $url = $_SERVER['REQUEST_URI'];
        if(isset($GLOBALS['user_info']['id']) && ($url == "/" || $url == "")) {
            $redis = \SiteApp::init()->dataCache->getRedisInstance();
            if($redis) {
                $key = sprintf(\core\service\MedalService::MEDAL_BEGINNER_HINT_FORMAT, $GLOBALS['user_info']['id']);
                $result = $redis->rPop($key);
                if(intval($result)) {
                    $medalBeginner = true;
                }
            }
        }
        $this->tpl->assign("medal_beginner", $medalBeginner);
        // 增加vip等级字段
        $vipGradeInfo = array();
        if (!empty($user_info['id'])) {
            $vipService = new VipService();
            $vipGradeInfo = $vipService->getVipGrade($user_info['id']);
        }
        $this->tpl->assign("vipGradeInfo", $vipGradeInfo);
        // 默认检查未完成充值订单
        $this->tpl->assign("isCheckCharge", self::IS_CHECK_CHARGE);

        $haveServiceEntrance = $this->rpc->local('CouponService\haveServiceEntrance', array($user_id));
        $this->tpl->assign('haveServiceEntrance', $haveServiceEntrance);

    }

    /**
     * 导航过滤
     * @param  [type] $nav_list
     * @param  [type] $filterKey
     * @return [type]
     */
    private function filterNavList($nav_list, $filterKey)
    {
        return array_filter($nav_list, function(&$item) use ($filterKey) {
            if (isset($item['sub_nav'])) {
                $item['sub_nav'] = $this->filterNavList($item['sub_nav'], $filterKey);
            }
            if ($item['name'] == $filterKey) return false;
            return true;
        });
    }

    /**
     * 获取模板文件路径, 默认存放在与controllers平级的views目录下
     *
     * @return string 模板文件路径
     **/
    public function getTemplate()
    {
        $class_path = strtolower(str_replace('\\', '/', get_class($this)));
        return str_replace('/controllers/', '/views/', $class_path).'.html';
    }

    /**
     * invoke的前置工作, 初始化form数据验证，session配置，登录状态等
     *
     * @return void
     **/
    public function init(){
    }

    public function _before_invoke()
    {
        //特殊用户处理
        $userId = isset($GLOBALS['user_info']['id']) ? $GLOBALS['user_info']['id'] : 0;
        if (\libs\utils\Block::isSpecialUser($userId)) {
            define('SPECIAL_USER_ACCESS', true);
            if (\libs\utils\Block::checkAccessLimit($userId) === false) {
                throw new \Exception('刷新过于频繁，请稍后再试', SHOW_EXCEPTION_MESSAGE_CODE);
            }
        }

        //增加监控点，类似WEB_CONTROLLERS_USER_LOGIN
        \libs\utils\Monitor::add(strtoupper(str_replace('\\', '_', get_called_class())));

        // 网贷相关回调，记录日志并报错
        $class_path = strtolower(str_replace('\\', '/', get_class($this)));
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($class_path, 'supervision') !== false && strpos($class_path, 'notify') !== false) {
            PaymentApi::log(sprintf('NcfWxSupervisionCallbackError. method:%s, action:%s, params:%s', $_SERVER['REQUEST_METHOD'], $class_path, json_encode($_POST)));
            $supervisionObj = new \core\service\SupervisionBaseService();
            $errorData = $supervisionObj->responseFailure(ErrCode::getCode('ERR_SERVICE'), ErrCode::getMsg('ERR_SERVICE'));
            $requestData = $supervisionObj->getApi()->response($errorData);
            return false;
        }

        return true;
    }

    /**
     * 检查用户是否登录，如果没有登录，跳转至登录页面
     */
    protected function check_login() {
        if (!$GLOBALS ['user_info']) {
            $current_url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
            $location_url = !empty($current_url) ? "user/login?backurl=" . $current_url : "user/login";
            $this->_set_login_refer();
            return app_redirect(url($location_url));
        }
        //强制新密码
        if($GLOBALS['user_info']['force_new_passwd'] == 1 && $_REQUEST[2] != 'editpwd' && $_REQUEST[2] != 'savepwd' && $_REQUEST[2] != 'savepwdp2p' && $_REQUEST[2] != 'DoModifyPwd') {
            return app_redirect(url("user/editpwd"));
        }
        return true;
    }

    /**
     * ajax接口检查用户是否登录
     */
    protected function ajax_checklogin() {
        if (!isset($GLOBALS['user_info']) || empty($GLOBALS['user_info'])) {
            return false;
        }
        return true;
    }

    /**
     * 设置面包屑
     * @param string|array $text
     */
    public function set_nav($text) {
        if (!is_array($text)) {
            $arr = array(
                "text" => $text,
            );
            $nav = array($arr);
        } else {
            foreach ($text as $k => $v) {
                if (is_numeric($k)) {
                    $nav[] = array("text" => $v);
                } else {
                    $nav[] = array("url" => $v, "text" => $k);
                }
            }
        }
        $this->tpl->assign("nav", $nav);
    }

    /**
     * 初始化首部导航信息
     * @param $nav_list
     * @param $isroot
     * @return mixed
     */
    private function _initNavList($nav_list, $isroot) {
        $class_path = strtolower(str_replace('\\', '/', get_class($this)));
        $arr_path = explode("/", $class_path);

        $u_param = "";
        foreach($_GET as $k=>$v)
        {
            if(strtolower($k)!="ctl"&&strtolower($k)!="act"&&strtolower($k)!="city")
            {
                $u_param.=$k."=".$v."&";
            }
        }
        if(substr($u_param,-1,1)=='&')
            $u_param = substr($u_param,0,-1);
        foreach($nav_list as $k=>$v)
        {
            if($v['url']=='')
            {
                $route = $v['u_module'];
                if($v['u_action']!='')
                    $route.="#".$v['u_action'];

                $app_index = $v['app_index'];

                if($v['u_module']=='index')
                {
                    $route="index";
                    $v['u_module'] = "index";
                }

                if($v['u_action']=='')
                    $v["u_action"] = "index";

                $str = "u:".$app_index."|".$route."|".$v['u_param'];
                $nav_list[$k]['url'] =  parse_url_tag($str);

                if ($isroot) {
                    if($v['u_module']=='deals' && $arr_path[2]=='tool') {
                        $nav_list[$k]['current'] = 1;
                    } elseif ($arr_path[2]==$v['u_module']) {
                        $nav_list[$k]['current'] = 1;
                    } elseif ($v['u_module'] == 'uc_center' && (strpos($arr_path[2], "uc_") === 0 || $arr_path[2] == "account")) {
                        // HACK(jiankangzhang): 现在UCCENTER下面有很多tab不在nav的config里面，现在将uc_开头的module归属到uc的tab下面.
                        $nav_list[$k]['current'] = 1;
                    }
                } else {
                    if ($v['u_module'] == 'help') {
                        if ($arr_path[2] == 'help') {
                            $menu_id = substr($v['u_param'], strpos($v['u_param'], '=') + 1);
                            if (in_array($menu_id, array('9', '12', '27'))) {
                                if ($menu_id == $_REQUEST['id']) {
                                    $nav_list[$k]['current'] = 1;
                                }
                            } else {
                                if (!in_array($_REQUEST['id'], array('9', '12', '27'))) {
                                    $nav_list[$k]['current'] = 1;
                                }
                            }
                        }
                    } else {
                        if ($arr_path[3]==$v['u_action']&&$arr_path[2]==$v['u_module']) {
                            $nav_list[$k]['current'] = 1;
                        }
                    }
                }
            }
        }
        return $nav_list;
    }

     /**
     * 根据后台添加的导航链接，判断是否需要https
     * @param string $url 不包含域名的 例如 account/load
     * @param int $type 1为静态路径前缀2为附件前缀
     */
    private  function _navhttpTohttps($url,$type=1){
        if (empty($url) || empty($type)){
            return '';
        }
        $ret_url = Url::getDomain().$url;
        $action_url_array = explode('/', $url);
        $control =  strtolower($action_url_array[1]);
        $action = empty($action_url_array[2])? strtolower('index') : strtolower($action_url_array[2]);

        $is_https = getIsHttps($control, $action, $type);
        if (!empty($is_https['protocol'])){
            $domain= Url::getDomain().$url;
            $ret_url = str_replace('http://', 'https://', $domain);
        }
       return $ret_url;
    }
    /**
     * 根据server变量以及后台配置，来判断是否跳转https或者http
     * @param string $module 模块
     * @param string $action 方法
     * @param int $type
     * @return bool | 直接header location
     */
    private function headerHttps($module,$action,$type=1){
        if (empty($module) || empty($action)){
            return false;
        }
        $codeArr = array(
                100=>'Continue',
                101=>'Switching Protocols',
                200=>'OK',
                201=>'Created',
                202=>'Accepted',
                203=>'Non-Authoritative Information',
                204=>'No Content',
                205=>'Reset Content',
                206=>'Partial Content',
                300=>'Multiple Choices',
                302=>'302 Moved Permanently',
                303=>'See Other',
                304=>'Not Modified',
                305=>'Use Proxy',
                307=>'Temporary Redirect',
                400=>'Bad Request',
                401=>'Unauthorized',
                402=>'Payment Required',
                403=>'Forbidden',
                404=>'Not Found',
                405=>'Method Not Allowed',
                406=>'Not Acceptable',
                407=>'Proxy Authentication Required',
                408=>'Request Timeout',
                409=>'Conflict',
                410=>'Gone',
                411=>'Length Required',
                412=>'Precondition Failed',
                413=>'Request Entity Too Large',
                414=>'Request-URI Too Long',
                415=>'Unsupported Media Type',
                416=>'Requested Range Not Satisfiable',
                417=>'Expectation Failed',
                500=>'Internal Server Error',
                501=>'Not Implemented',
                502=>'Bad Gateway',
                503=>'Service Unavailable',
                504=>'Gateway Timeout',
                505=>'HTTP Version Not Supported',
        );
        // 服务器端http的时候并没有设置这个变量
        /* if (!isset($_SERVER['HTTP_XHTTPS'])){
            return false;
        } */
        $swtich_cn_domain = empty($GLOBALS['sys_config']['TURN_ON_CN_DOMAIN']) ? 1 : intval($GLOBALS['sys_config']['TURN_ON_CN_DOMAIN']);
        // 不启用https
        if ($swtich_cn_domain == 0 && $this->is_firstp2p){
            return false;
        }

        // 读取开关
        $switch_https = empty($GLOBALS['sys_config']['IS_HTTPS']) ? 0 : intval($GLOBALS['sys_config']['IS_HTTPS']);
        // 读取支付https开关
        $payment_https = empty($GLOBALS['sys_config']['TURN_ON_PAYMENT_HTTPS']) ? 0 : intval($GLOBALS['sys_config']['TURN_ON_PAYMENT_HTTPS']);

        $server_http = empty($_SERVER['HTTP_XHTTPS']) ? 0 : 1;
        $request_uri = $_SERVER['REQUEST_URI'];
        $jumpurl = Url::getDomain().$request_uri;

        // 检测同时支持http和https的模块
        $not_is_https = getNotIsHttps($module,$action);
        if ($not_is_https===true){
            return true;
        }
        // 全站强制开启https
        if ($switch_https == 3 && $server_http == 0){
            header('HTTP/1.1'.$codeArr[302],true,302);
            $jumpurl = str_replace('http://', 'https://', $jumpurl);
            header('Location:'.$jumpurl);
            return;
        }
        // ssl_pages
        $is_https = getIsHttps($module, $action,$type);

        /**
         *  需要服务器端 设置server变量"HTTP_XHTTPS"
         *  如果服务器传过来的变量值是错误的，将会循环重定向
         */
        if (!empty($is_https['protocol']) && $server_http===0){

            // 是https，但传过来不是的情况
            //if (stripos($jumpurl,"https://") === false){
                header('HTTP/1.1'.$codeArr[302],true,302);
                $jumpurl = str_replace('http://', 'https://', $jumpurl);
                header('Location:'.$jumpurl);
                return;
            //}
        }
        if (empty($is_https['protocol']) && $server_http===1){

            // 不是https，但是传过来的是的情况
            //if (stripos($jumpurl,"https://") !== false){
            if (($switch_https == 1 || $switch_https == 0)  &&  $payment_https == 0) {
                header('HTTP/1.1' . $codeArr[302], true, 302);
                $jumpurl = str_replace('https://', 'http://', $jumpurl);
                header('Location:' . $jumpurl);
                return;
            }
            //}
        }
        // 没有https 走http
        if (empty($is_https['protocol']) && $server_http===0){
            /* if (stripos($jumpurl,"https://") !== false){
                header('HTTP/1.1'.$codeArr[301],true,301);
                $jumpurl = str_replace('https://', 'http://', $jumpurl);
                header('Location:'.$jumpurl);
            } */
        }
        return true;
    }

    public function _after_invoke()
    {
        $class = get_called_class();
        if (false === $class::IS_H5) {
            return $this->ajax_return();
        }

        $class_path = strtolower(str_replace('\\', '/', get_class($this)));
        $arr_path = explode("/", $class_path);

        // TODO 部分跳转新版页面, 分站全部加载分站目录
        if (!empty($this->template)) {
            if (APP_SITE !== 'firstp2p') {
                $this->template = str_replace('web/views', 'web/views/fenzhan', $this->template);
            } else if (in_array($class_path, $this->v2tpls)) {
                $this->template = str_replace('web/views', 'web/views/v2', $this->template);
            }
        }

        if (isset($this->tpl->_var['inc_file'])) {
            if (APP_SITE !== 'firstp2p' && strpos($class_path, 'account') !== false) {
                $this->tpl->_var['inc_file'] = str_replace('web/views', 'web/views/fenzhan', $this->tpl->_var['inc_file']);
            }
        }

       // key存在的话，不存储登录跳转的url
        if (isset($this->_notLogin[$arr_path[2]])){
            parent::_after_invoke();
            return true;
        }
        $this->_set_login_refer();

        parent::_after_invoke();
    }

    private function _set_login_refer(){
        $class_path = strtolower(str_replace('\\', '/', get_class($this)));
        $arr_path = explode("/", $class_path);
        if(array_key_exists($arr_path[2],$this->_notLogin) === FALSE){
            set_gopreview();
        }else{
            $total = count($this->_notLogin[$arr_path[2]]);
            $this->_notLogin[$arr_path[2]][$total] = $arr_path[3];
            for($i=0; $arr_path[3] !== strtolower($this->_notLogin[$arr_path[2]][$i]); $i++);
            if($i>= $total) set_gopreview();
        }
    }

    /**
     * 显示错误
     *
     * @param $msg 消息内容
     * @param int $ajax
     * @param string $jump 调整链接
     * @param int $stay 是否停留不跳转
     * @param int $time 跳转等待时间
     * @param int $status //前端js判断状态
     */
    public function show_error($msg, $title = '', $ajax = 0, $stay = 0, $jump = '', $refresh_time = 3, $status = 0)
    {
        $this->businessLog['busi_msg'] = $msg;
        if($ajax == 1)
        {
            $result['status'] = $status;
            $result['info'] = $msg;
            $result['jump'] = $jump;
            header("Content-type: application/json; charset=utf-8");
            echo(json_encode($result));
            exit;
        }
        else
        {
            $title = empty($title) ? $GLOBALS['lang']['ERROR_TITLE'] : $title;
            $this->tpl->assign('page_title',$title);
            $this->tpl->assign('error_title',$title);
            $this->tpl->assign('error_msg',$msg);
            $this->tpl->assign('show_qrcode',$title === '请下载网信APP');


            if($jump==''){
                $jump = $_SERVER['HTTP_REFERER'];
            }
            if(!$jump&&$jump==''){
                $jump = APP_ROOT."/";
            }

            $this->tpl->assign('jump',$jump);
            $this->tpl->assign("stay",$stay);
            $this->tpl->assign("refresh_time",$refresh_time);
            $this->tpl->display("web/views/error.html");
            $this->template = null;

        }
        setLog(
                array('output' => array('ajax' => $ajax, 'jump' => $jump, 'msg'=> $msg ))
        );
        return false;
    }

    /**
     *  异步返回错误 可以返回多个变量
     * @param string $msg
     * @param string $jump
     * @param array $data
     * @return string
     */
    public function show_error_data_ajax($msg,$jump = '',$data = array()){
        $result['status'] = 0;
        $result['info'] = $msg;
        $result['jump'] = $jump;
        if(!empty($data)){
            $result['data'] = $data;
        }
        header("Content-type: application/json; charset=utf-8");
        echo(json_encode($result));
        setLog(
            array('output' => array('ajax' => 0, 'jump' => $jump, 'msg'=> $msg ))
        );
        return false;
    }

    /**
     * 显示异常
     */
    public function show_exception(\Exception $e)
    {
        \libs\utils\Logger::error('ControllerException. message:'.$e->getMessage().', file:'.$e->getFile().', line:'.$e->getLine());

        $tips = '不好意思，'.app_conf('SHOP_TITLE').'君打盹了';
        if ($e->getCode() === SHOW_EXCEPTION_MESSAGE_CODE) {
            $tips = $e->getMessage();
        }

        $this->tpl->assign('tips', $tips);
        $this->tpl->assign('message', $e->getMessage());
        $this->tpl->assign('code', $e->getCode());
        $this->tpl->assign('env', app_conf('ENV_FLAG'));
        $this->tpl->assign('logId', \libs\utils\Logger::getLogId());

        $this->tpl->display('web/views/exception.html');
        $this->template = null;
    }


    /**
     * 显示实名认证验卡操作提示
     */
    public function show_payment_tips($msg, $title = '', $ajax = 0, $stay = 0, $jump = '/account', $refresh_time = 3)
    {
        $this->tipsTemplate = 'web/views/v2/account/rna_fail.html';
        $this->template = '';
        $tips = '';
        $formSubmit = false;
        if ($msg == '请先进行实名认证')
        {
            $tips = '系统将在<span id="second">%d</span>秒后自动跳转到实名认证页面， 点击 <a href="'.$jump.'" class="blue textd">这里</a> 立即跳转';
        }
        else if ($msg == '请先绑定银行卡' || $msg == '请先验证银行卡')
        {
            $formSubmit = true;
            $tips = '系统将在<span id="second">%d</span>秒后自动跳转到先锋支付， 点击 <a href="javascript:void(0)" class="blue textd" id="directGo">这里</a> 立即跳转';
            $bindCardForm = $this->rpc->local('PaymentService\getBindCardForm', [['token' => base64_encode(microtime(true))], true, false, 'bindCardForm']);
            $this->tpl->assign('bindCardForm', $bindCardForm);
        }
        $this->tpl->assign('tipsMessage', $tips);
        $this->tpl->assign('formSubmit', $formSubmit);
        return $this->show_tips($msg, $title, $ajax, $stay, $jump, $refresh_time);
    }

    /**
     * 显示提示
     *
     * @param $msg 消息内容
     * @param int $ajax
     * @param string $jump 调整链接
     * @param int $stay 是否停留不跳转
     * @param int $time 跳转等待时间
     */
    public function show_tips($msg, $title = '', $ajax = 0, $stay = 0, $jump = '/account', $refresh_time = 3)
    {
        $this->businessLog['busi_msg'] = $msg;
        if($ajax == 1)
        {
            $result['status'] = 0;
            $result['info'] = $msg;
            $result['jump'] = $jump;
            header("Content-type: application/json; charset=utf-8");
            echo(json_encode($result));
        }
        else
        {
            $title = empty($title) ? $GLOBALS['lang']['ERROR_TITLE'] : $title;
            $this->tpl->assign('page_title',$title);
            $this->tpl->assign('error_title',$title);
            $this->tpl->assign('error_msg',$msg);

            if($jump==''){
                $jump = $_SERVER['HTTP_REFERER'];
            }
            if(!$jump&&$jump==''){
                $jump = APP_ROOT."/";
            }

            $this->tpl->assign('jump',$jump);
            $this->tpl->assign("stay",$stay);
            $this->tpl->assign("refresh_time",$refresh_time);

            $template = $this->tipsTemplate ?: 'web/views/being.html';
            $this->tpl->display($template);
        }

        setLog(array('output' => array('ajax' => 0, 'jump' => $jump, 'msg'=> $msg )));
        return false;
    }

    //显示成功
    public function show_success($msg, $title = '', $ajax = 0, $stay = 0, $jump = '',  $data = array(), $refresh_time = 3)
    {
        $this->businessLog['busi_msg'] = $msg;
        if($ajax==1)
        {
            $result['status'] = 1;
            $result['info'] = $msg;
            $result['jump'] = $jump;
            if(!empty($data)){
                $result['data'] = $data;
            }
            header("Content-type: application/json; charset=utf-8");
            echo(json_encode($result));
            exit;
        }
        else
        {
            $title = empty($title) ? $GLOBALS['lang']['SUCCESS_TITLE'] : $title;
            $this->tpl->assign('page_title',$title);
            $this->tpl->assign('success_title',$title);
            $this->tpl->assign('success_msg',$msg);

            if($jump==''){
                $jump = $_SERVER['HTTP_REFERER'];
            }
            if(!$jump&&$jump==''){
                $jump = APP_ROOT."/";
            }

            $this->tpl->assign('jump',$jump);
            $this->tpl->assign("stay",$stay);
            $this->tpl->assign("refresh_time",$refresh_time);
            $this->tpl->display("web/views/success.html");
            $this->template = null;
        }

        setLog(array('output' => array('ajax' => 0, 'jump' => $jump, 'msg'=> $msg )));
        return false;
    }

    protected function logInit(){

        //print_r(debug_backtrace());
        $post = $_POST;
        $post = cleanSensitiveField($post);

        $this->log = array(
            'level' => 'STATS',
            'platform' => 'web',
            'errno' => '',
            'errmsg' => '',
            'ip' =>  get_client_ip(),
            'host' => isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '',
            'uri' => $_SERVER['REQUEST_URI'],
            'query' => $_SERVER['QUERY_STRING'],
            'referer' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '',
            'cookie' => $_COOKIE,
            'method' => $_SERVER['REQUEST_METHOD'],
            'process' => microtime(1),
            'params' => $post,
            'uid' => isset($GLOBALS['user_info']['id'])?$GLOBALS['user_info']['id']:'',
            'output' => '',
            'analyze' => '',
        );

    }


    protected function log(){
        if (app_conf('DEBUG_MYSQL_LOG_OPEN')) {
            setLog(array('sql' => $GLOBALS['db']->queryLog));
        }
        $this->log['process'] = sprintf("%d", (microtime(1) - $this->log['process'])*1000);
        $this->businessLog['resp_time'] = $this->log['process'];
        $this->log['site_id'] = $this->getSiteId();
        $level = $this->log['level'];
        unset($this->log['level']);
        $_log = getLog();
        if(is_array($_log)){
            $this->log = array_merge($this->log, $_log);
        }
        $jsonLog = json_encode($this->log, JSON_UNESCAPED_UNICODE );
        $jsonLog = str_replace('\/', '/', $jsonLog);
        call_user_func('\libs\utils\Logger::'.$level, $jsonLog);
        LoggerBusiness::write('web',$this->businessLog);
    }

    public function setLog($k, $v){
        if(!empty($k)){
            $this->log[$k] = $v;
        }
    }

    /**
     * 获取site_id
     */
    public function getSiteId() {
        if (!empty($this->appInfo['id'])) {
            return $this->appInfo['id'];
        }

        if (isset($_REQUEST['client_id'])) {
            $clientId = trim($_REQUEST['client_id']);
            $oauthConf = $GLOBALS['sys_config']['OAUTH_SERVER_CONF'];
            if (isset($oauthConf[$clientId]['site_id'])) {
                return $oauthConf[$clientId]['site_id'];
            }
        }

        if (isset($_REQUEST['site_id'])) {
            $siteId = intval(trim($_REQUEST['site_id']));
            if ($siteId > 0) {
                return $siteId;
            }
        }

        return 1;
    }

    /**
     * 加载开放平台数据
     */
    public function loadOpenData() {
       if (!Open::checkOpenSwitch()) {
            return true;
        }

       $host = Open::wapRegistDomain() ? : get_host(false);
        if (!$siteId = Open::getSiteIdByDomain($host)) { //找不到域名
            //更换域名时，旧域名跳转新域名
            $openService = new OpenService();
            $returnUrl = $openService->getNewFzUrl($host);
            if(!empty($returnUrl)) {
                $parseUrl = parse_url($_SERVER["REQUEST_URI"]);
                $returnUrl = $openService->getTotalFzUrl($returnUrl);
                header("location:http://" . $returnUrl);
                exit;
            }

            header("location:https://" . $this->getWxlcDomain());
            exit;
        }

        jump_to_https(true); // 分站，http跳转https

        if (!$appInfo = Open::getAppBySiteId($siteId)) { //找不到app信息
            header("location:https://" . $this->getWxlcDomain());
            exit;
        }
        if (!(4 & intval($appInfo['onlineStatus']))) { // 4 表示 pc 在线
            header("location:https://" . $this->getWxlcDomain()); //未审核通过
            exit;
        }

        //判断是否是preview --> siteId
        if (isset($_REQUEST['force'])) {
            $siteId = intval($_REQUEST['pre_site_id']);
        }

        $this->appInfo = $appInfo;
        $this->appAdvs = (array) Open::getSiteAdvBySiteId($siteId);

        Open::coverSiteId();
        Open::coverSiteInfo($appInfo);

        if (isset($_REQUEST['force'])) {
            $appConf = Open::getPreviewData($siteId, 3);
        } else {
            $appConf = Open::getSiteConfBySiteId($siteId);
        }

        $this->openSets = (array) $appConf['confInfo'];
    }

    /**
     * 设置模板值
     */
    public function setOpenData2Tpl() {
        if (!Open::checkOpenSwitch()) {
            return true;
        }

        $setParmas = isset($this->appInfo['setParams']) ? (array) json_decode($this->appInfo['setParams'], true) : [];
        $this->tpl->assign('setParmas', $setParmas);
        $this->tpl->assign('appInfo', $this->appInfo);

        $tplData = Open::getWebTplData($this->openSets, array('advs' => $this->appAdvs));
        foreach ($tplData as $key => $val) {
            $this->tpl->assign($key, $val);
        }
    }

    /**
     * 是否模态窗口登录/注册
     */
    public function isModal() {
        return !empty($_REQUEST['modal']);
    }


    /**
     * 获取UA信息
     */
    protected function getUserAgent() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        $from = "";
        if(preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i',$userAgent)
            ||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($userAgent,0,4))) {
            $from = 'mobile';
        } else {
            $from = 'web';
        }

        if (strpos($userAgent, 'MicroMessenger') !== false) {
            $from = "weixin";
        }

        $os = "";
        if (preg_match('/iPhone|iPad/', $userAgent)) {
            $os = "ios";
        }

        if (preg_match('/Android|Linux/', $userAgent)) {
            $os = "android";
        }
        return array("from" => $from, 'os' => $os);
    }

    protected function getWxlcDomain(){
        return app_conf('WXLC_DOMAIN');
    }

    /**
     * 分站广告，跳转指定网址
     * 条件：分站，分站标签AdOn打开，用户未登录状态
     */
    protected function checkAdRedirect(){
        //for test
        //$this->appInfo['setParams'] = '{"UserOp":"1","AdOn":"http://www.baidu.com","DealOp":"1", "CouponOp":"1", "CodeNotHidden":"1","ArtOp":"1", "AllowJS":"0", "APPOp":"1"}';
        if(!empty($this->appInfo) && empty($GLOBALS['user_info'])){
            $setParams = json_decode($this->appInfo['setParams'],true);
            if(!empty($setParams['AdOn'])){
                $backUrl = UrlEncode(get_domain().$_SERVER['REQUEST_URI']);
                $arr = parse_url($setParams['AdOn']);
                if(!isset($arr['query'])){
                    header(sprintf("Location:%s?back_url=%s", $setParams['AdOn'], $backUrl));
                }else{
                    header(sprintf("Location:%s&back_url=%s", $setParams['AdOn'], $backUrl));
                }
                exit;
            }
        }
    }

    /**
     * 存管系统-web端的json结构
     */
    protected function ajax_return() {
        $arr_result = array();
        if (empty($this->error)) {
            $arr_result['errno'] = 0;
            $arr_result['error'] = '';
            $arr_result['data'] = $this->json_data;
        } else {
            $arr_result['errno'] = $this->errno;
            $arr_result['error'] = $this->error;
            $arr_result['data'] = '';
        }
        header('Content-type: application/json;charset=UTF-8');
        echo json_encode($arr_result, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 设置ajax错误
     * @param int $err
     * @param string $error
     */
    public function setErr($errno = 0, $error = '') {
        $this->errno = empty($errno) ? ErrCode::getCode('ERR_UNKNOWN') : $errno;
        $this->error = empty($error) ? ErrCode::getMsg('ERR_UNKNOWN') : $error;
    }

    public function getEuid($userId = null) {
        $userInfo = \es_session::get("user_info");
        if (empty($userId)) {
            $userId = $userInfo['id'];
        }

        $setParams = isset($this->appInfo['setParams']) ? (array) json_decode($this->appInfo['setParams'], true) : '';
        $euidLevel = isset($setParams['euidLevel']) ? intval($setParams['euidLevel']) : 0;

        $currEuid = htmlspecialchars(trim($_REQUEST['euid']));
        if (empty($currEuid)) {
            $currEuid = \es_cookie::get('euid');
        }
        if ($euidLevel <= 0 || empty($currEuid)) {
            return empty($userId) ? '' : numTo32($userId);
        }

        $euidSlice = explode('_', $currEuid);
        if ($euidLevel == 1) {
            return array_shift($euidSlice);
        }

        $euidNodes = array_slice($euidSlice, 0, $euidLevel - 1);
        if (!empty($userId)) {
            $euidNodes[] = numTo32($userId);
        }
        return implode('_', $euidNodes);
    }

    /**
     * 主站跳转普惠公用方法
     */
    public function redirectToP2P($url) {
        // 用户未登录
        if (empty($GLOBALS['user_info'])) { // || $GLOBALS['user_info']['supervision_user_id'] > 0) {
            return header('Location:' . $url);
        }
        // 验证用户卡状态
        $userService = new UserService($GLOBALS['user_info']['id']);
        $userCheck = $userService->isBindBankCard();
        if ($userCheck['ret'] !== true)
        {
            // 企业用户给提示
            if ($userService->isEnterprise() && ($userCheck['respCode'] == UserService::STATUS_BINDCARD_UNBIND || $userCheck['respCode'] == UserService::STATUS_BINDCARD_UNVALID))
            {
                return app_redirect(Url::gene('deal','promptCompany'));
            }

            $siteId = \libs\utils\Site::getId();
            $hasPassport = $this->rpc->local('AccountService\hasPassport', array($GLOBALS['user_info']['id']));
            // 白名单中的分站 大陆用户和已绑卡未验证的港澳台用户跳转到先锋支付绑卡/验卡
            if (($siteId == 1 || \libs\web\Open::checkOpenSwitch()) && (empty($hasPassport) || (!empty($hasPassport) && $userCheck['respCode'] == UserService::STATUS_BINDCARD_UNVALID)))
            {
                return $this->show_payment_tips($userCheck['respMsg'], '操作失败', 0, 0, '/account/addbank', 3);
            }
            return $this->show_error($userCheck['respMsg'], '操作失败', 0, 0, '/account/addbank', 3);
        }
        // 默认正常跳转
        return header('Location:' . $url);
    }

    /**
     * 跳转到站点登陆
     */
    protected function redirectToLogin($host) {
        if (empty($GLOBALS['user_info'])) {
            $current_url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
            $location_url = get_http() . $host . '/' . "user/login?backurl=" . $current_url;
            return app_redirect($location_url);
        }
        return false;
    }

} // END class BaseAction
