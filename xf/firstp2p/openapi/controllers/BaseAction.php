<?php

namespace openapi\controllers;

use NCFGroup\Protos\Ptp\ProtoAccessToken;
use NCFGroup\Protos\Ptp\RPCErrorCode;
use NCFGroup\Protos\Ptp\ProtoUser;
use libs\rpc\Rpc;
use openapi\lib\OpenAction;
use openapi\conf\Error;
use openapi\conf\ConstDefine;
use openapi\conf\OauthConf;
use libs\web\Form;
use libs\utils\Logger;
use openapi\lib\Tools;
use NCFGroup\Task\Services\TaskService AS GTaskService;
use core\event\RiskCheckEvent;
use libs\web\Open;
use NCFGroup\Protos\Ptp\Enum\DeviceEnum;
use core\service\SupervisionService;
use core\dao\EnterpriseModel;

class BaseAction extends OpenAction {

    const IS_H5 = false;

    protected $current_token_user = false;
    public $errorCode = null;
    public $errorMsg = null;
    public $excCode = null;//异常Code
    public $excMsg = null;//异常Msg
    public $rpc;
    public $device = null;
    public $sys_param_rules = array(
        "client_id" => array("filter" => "string"),
        "timestamp" => array("filter" => "string"),
        "format" => array("filter" => "string"),
        "v" => array("filter" => "string"),
        "sign" => array("filter" => "string"),
        "oauth_token" => array("filter" => "string"),
        "access_token" => array("filter" => "string"),
        "openId" => array("filter" => "string"),
        "from_platform" => array("filter" => "string"),
        "site_id" => array("filter" => 'string', "option" => array("optional" => true)),
        "device" => array("filter" => 'string', "option" => array("optional" => true)),
    );
    public $json_data_err = '';
    protected $_user_id = null;
    protected $_client_id = '';
    public $clientConf = array();
    // 默认关键数据不打码，其他数据需要大妈
    protected $_mosaic = array();
    protected $svStatus = 0;  //存管开关
    protected $timeout = 600;  //链接超时

    public function __construct() {
        parent::__construct();

        jump_to_https(); // 如果非https，那么尝试跳转https

        $this->coverSiteId();
        $this->rpc = new Rpc();
        $this->setTpl();
    }

    /**
     * 获取端标识信息
     * @return int
     */
    private function _getDevice() {
        $device = isset($this->form->data['device']) ? $this->form->data['device'] : null;
        $deviceFlag = DeviceEnum::DEVICE_WAP;
        switch ($device) {
            case 'web':
                $deviceFlag = DeviceEnum::DEVICE_WEB;
                break;

            case 'android':
                $deviceFlag = DeviceEnum::DEVICE_ANDROID;
                break;

            case 'ios':
                $deviceFlag = DeviceEnum::DEVICE_IOS;
                break;

            case 'wap':
                $deviceFlag = DeviceEnum::DEVICE_WAP;
                break;

            default:
                $deviceFlag = DeviceEnum::DEVICE_WAP;
                break;
        }
        return $deviceFlag;
    }

    /**
     * 获取基类
     *
     * return string
     * */
    public function getRoot() {
        return __CLASS__;
    }

    /**
     * 设置模板类
     * r
     * @return void
     * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
     * */
    private function setTpl() {
        $this->tpl = new \AppTemplate();
        $this->tpl->template_dir = APP_ROOT_PATH;
        $this->tpl->asset = \SiteApp::init()->asset;
        $static_path = APP_ROOT . "/static";
        $this->tpl->assign("STATIC_PATH", $static_path);
        $this->tpl->assign("IS_APP", isset($_COOKIE['APPTOKEN']));
    }

    /**
     * 获取模板文件路径, 默认存放在与controllers平级的views目录下
     * controllers驼峰命名对应下划线分割的模板名
     *
     * @return string 模板文件路径
     * */
    public function getTemplate() {
        $class_path = str_replace('\\', '/', get_class($this));
        //controllers驼峰命名转换下划线分割的模板名
        $class_path_array = explode('/', $class_path);
        $class_name = array_pop($class_path_array); //类名
        $tpl_name = ""; //模板名
        for ($i = 0; $i < strlen($class_name); $i++) {
            $char_lower = strtolower($class_name{$i});
            if ($i > 0 && $class_name{$i} != $char_lower) {
                $tpl_name .= "_";
            }
            $tpl_name .= $char_lower;
        }
        array_push($class_path_array, $tpl_name);
        $class_path = implode('/', $class_path_array);
        return str_replace('/controllers/', '/views/', $class_path) . '.html';
    }

    /**
     * invoke的前置工作, 初始化form数据验证，session配置，登录状态等
     *
     * @return void
     * */
    public function init() {

    }

    /**
     * 如果出错，允许表层设置错误
     */
    public function setErr($err, $error = "") {
        $arr = Error::get($err);
        $this->errorCode = $arr["errorCode"];
        $this->errorMsg = empty($error) ? $arr["errorMsg"] : $error;
    }

    /*
     * 获取client配置
     */

    private function _getClientConf() {
        //$req = $this->form->data;
        if (!get_magic_quotes_gpc()) {
            $req['client_id'] = isset($_REQUEST['client_id']) ? addslashes($_REQUEST['client_id']) : '';
        } else {
            $req['client_id'] = isset($_REQUEST['client_id']) ? trim($_REQUEST['client_id']) : '';
        }
        $this->_client_id = trim($req['client_id']);
        $client_conf = $GLOBALS['sys_config']['OAUTH_SERVER_CONF'];
        if (isset($client_conf[$this->_client_id])) {
            $this->clientConf = $client_conf[$this->_client_id];
            if (isset(OauthConf::$defaultCoupon[$this->_client_id])) {
                $this->clientConf['default_coupon'] = OauthConf::$defaultCoupon[$this->_client_id];
            }
            if (isset($client_conf[$this->_client_id]['mosaic']) && is_array($client_conf[$this->_client_id]['mosaic'])) {
                $this->_mosaic = $client_conf[$this->_client_id]['mosaic'];
            }
        }
    }

    private function _getUserIdByOpenID() {
        $req = $this->form->data;
        $openId = isset($req['openId']) ? $req['openId'] : (isset($req['open_id']) ? $req['open_id']: '');
        if (!empty($openId)) {
            $this->_user_id = Tools::getUserIdByOpenID($openId);
            if (!$this->_user_id) {
                throw new \Exception('ERR_OPEN_ID');
            }
        }
    }

    /**
     * 继承父类 _before_invoke
     */
    public function _before_invoke() {
        $this->_getClientConf();
        $this->_getUserIdByOpenID();
        $this->device = $this->_getDevice();
        //增加监控点，类似WEB_CONTROLLERS_USER_LOGIN
        \libs\utils\Monitor::add(strtoupper(str_replace('\\', '_', get_called_class())));
        return true;
    }

    /**
     * 继承父类_after_invoke，实现数据展示
     */
    public function _after_invoke() {
        $class = get_called_class();
        if ($class::IS_H5 == true) {
            if ($this->errorCode != 0) {
                $this->show_error($this->errorMsg, '', 0, 1);
            } else {
                $this->tpl->display($this->template);
            }
        } else {
            $arr_result = array();
            if ($this->errorCode == 0) {
                $this->dataFilter();
                $arr_result["errorCode"] = 0;
                $arr_result["errorMsg"] = "";
                $arr_result["data"] = $this->json_data;
            } else {
                $arr_result["errorCode"] = $this->errorCode;
                $arr_result["errorMsg"] = $this->errorMsg;
                $arr_result["data"] = $this->json_data_err;
            }

            if (isset($_REQUEST['debug']) && $_REQUEST['debug'] == 1) {
                var_export($arr_result);
            } else {
                echo json_encode($arr_result);
            }
        }
    }

    /**
     * 根据配置过滤一些数据
     */
    protected function dataFilter() {
        $action = $this->getClassAction();
        if (isset(OauthConf::$dataFilter[$this->_client_id][$action])) {
            $data_filter = OauthConf::$dataFilter[$this->_client_id][$action];
            $field = isset($data_filter['field']) ? $data_filter['field'] : array();
            $type = isset($data_filter['type']) ? $data_filter['type'] : 'include';
            $this->json_data = Tools::dataFilter($this->json_data, $field, $type);
        }
    }

    protected function getClassAction() {
        $class_dir_arr = explode('/', str_replace('\\', '/', get_class($this)));
        $class_name = array_pop($class_dir_arr);
        $class_action = end($class_dir_arr) . '/' . $class_name;
        return $class_action;
    }

    /**
     * 根据token获取登陆用户ID
     * @param bool $need_err
     * @return bool|array
     */
    protected function getUserIdByAccessToken() {
        $accessToken = $this->form->data['oauth_token'];
        if (empty($accessToken)) {
            return false;
        }
        $request = new ProtoAccessToken();
        $request->setAccessToken($accessToken);
        $userIdResponse = $GLOBALS['rpc']->callByObject(array(
            'service' => 'NCFGroup\Ptp\services\PtpUser',
            'method' => 'getUserIdByAccessToken',
            'args' => $request
        ));
        if ($userIdResponse->resCode === RPCErrorCode::SUCCESS) {
            $this->current_token_user = $userIdResponse->getUserId();
            $GLOBALS['user_info']['id'] = $this->current_token_user;
            return $userIdResponse;
        }
        return FALSE;
    }

    /**
     * 根据token获取登陆用户信息
     * @param bool $need_err
     * @return bool|array
     */
    protected function getUserByAccessToken($tm = false) {
        if ($this->_user_id) {
            $userIdResponse = new ProtoUser();
            $userIdResponse->setUserId(intval($this->_user_id));
        } else {
            $userIdResponse = $this->getUserIdByAccessToken();
        }
        if (!empty($userIdResponse)) {
            if ($tm === true) {
                $userIdResponse->setIsTm(0);
            }
            $userResponse = $GLOBALS['rpc']->callByObject(array(
                'service' => 'NCFGroup\Ptp\services\PtpUser',
                'method' => 'getUserInfoById',
                'args' => $userIdResponse
            ));
            if ($userResponse->resCode === RPCErrorCode::SUCCESS) {
                $GLOBALS['user_info'] = [];
                $GLOBALS['user_info']['group_id'] = $userResponse->getGroupId();
                $GLOBALS['user_info']['id'] = $userResponse->getUserId();
                return $userResponse;
            }
        }

        return false;
    }

    /**
     * 根据token获取登陆client_id
     * @param bool $need_err
     * @return bool|array
     */
    protected function getClientIdByAccessToken() {
        $accessToken = $this->form->data['access_token'];
        if (empty($accessToken)) {
            return false;
        }

        $request = new ProtoAccessToken();
        $request->setAccessToken($accessToken);
        $clientIdResponse = $GLOBALS['rpc']->callByObject(array(
            'service' => 'NCFGroup\Ptp\services\PtpUser',
            'method' => 'getClientIdByAccessToken',
            'args' => $request
        ));
        return $clientIdResponse;
    }

    protected function logInit() {

        //print_r(debug_backtrace());

        $post = $_POST;
        $post = cleanSensitiveField($post);

        $this->log = array(
            'level' => Logger::STATS,
            'platform' => 'openapi',
            'device' => isset($_SERVER['HTTP_OS']) ? $_SERVER['HTTP_OS'] : "APP",
            'errno' => '',
            'errmsg' => '',
            'ip' => get_client_ip(),
            'host' => $_SERVER['HTTP_HOST'],
            'uri' => $_SERVER['REQUEST_URI'],
            'query' => $_SERVER['QUERY_STRING'],
            'referer' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '-',
            'cookie' => $_COOKIE,
            'method' => $_SERVER['REQUEST_METHOD'],
            'process' => microtime(1),
            'postParams' => $post,
            'getParams' => $_GET,
            'uid' => isset($GLOBALS['user_info']['id']) ? $GLOBALS['user_info']['id'] : '',
            'output' => '',
            'analyze' => '',
        );
    }

    protected function log() {
        $siteId = $this->getSiteId();
        $paramKey = !empty($this->log['getParams']) ? 'getParams' : 'postParams';
        if (!isset($this->log[$paramKey]['site_id'])) {
            $this->log[$paramKey]['site_id'] = $siteId;
        }

        $this->log['errno'] = $this->errorCode;
        $this->log['errmsg'] = $this->errorMsg;
        $tmJsonData = cleanSensitiveField($this->json_data);
        $this->log['output'] = substr(json_encode($tmJsonData), 0, 200);
        $this->log['process'] = sprintf("%d", (microtime(1) - $this->log['process']) * 1000);
        $level = $this->log['level'];
        $this->log['uid'] = isset($this->current_token_user) ? $this->current_token_user : '';
        unset($this->log['level']);
        $this->log['excCode'] = $this->excCode;
        $this->log['excMsg'] = $this->excMsg;
        $_log = getLog();
        if (is_array($_log)) {
            $this->log = array_merge($this->log, $_log);
        }
        $jsonLog = json_encode($this->log, JSON_UNESCAPED_UNICODE);
        $jsonLog = str_replace('\/', '/', $jsonLog);
        call_user_func('\libs\utils\Logger::' . "STATS", $jsonLog);
    }

    /**
     * 鉴权认证
     */
    public function authCheck() {
        if ($this->checkIsInnerRequest()) {
            return true;
        }
        if (!$this->checkActionPermission()) {
            throw new \Exception('ERR_SYSTEM_ACTION_PERMISSION');
        }
        if (!$this->form instanceof Form) {
            $this->form = new Form();
            $this->form->rules = $this->sys_param_rules;
        }
        if (!$this->form->validate()) {
            throw new \Exception('ERR_SYSTEM');
        }
        if (!$this->clientConf) {
            $this->_getClientConf();
        }
        if (empty($this->_client_id) || (!$this->clientConf)) {
            throw new \Exception('ERR_SYSTEM_CLIENTID');
        }
        $req = $this->form->data;

        // 调试时间为10小时过期，online为10分钟
        $timeout = (app_conf('ENV_FLAG') && in_array(app_conf('ENV_FLAG'), array('dev', 'test'))) ? 10 * 3600 : $this->timeout;
        $timestamp = is_numeric($req['timestamp']) ? $req['timestamp'] : strtotime($req['timestamp']);
        if (empty($timestamp) || abs($timestamp - time()) > $timeout) {
            $requestUri = $_SERVER['REQUEST_URI'];
            $urlInfo = parse_url($requestUri);
            $matches = array();
            preg_match("/^\/(\w+)\/(\w+)$/", $urlInfo['path'], $matches);
            $action = end($matches);
            $class = get_called_class();
            if ($class::IS_H5 == true) {
                $back_uri = isset($this->clientConf['error_back_uri']) ? $this->clientConf['error_back_uri'] : "";
                $this->tpl->assign("back_uri", $back_uri);
                $this->tpl->display('openapi/views/time_out_err.html');
                $this->log();
                exit;
            } else {
                throw new \Exception('ERR_SYSTEM_TIME');
            }
        }
        if (empty($req['sign'])) {
            throw new \Exception('ERR_SYSTEM_SIGN_NULL');
        }
        if (is_object($req)) {
            $req = (array) $req;
        }
        $sign_md5 = $this->getSign($req);
        $sign = $req['sign'];
        $this->log['expectedSign'] = $sign_md5;
        if (in_array(app_conf('ENV_FLAG'), array('dev', 'test'))) {
            $this->log['sign'] = 'sign_req:' . $sign;
            $this->log['sign_md5'] = 'sign_md5:' . $sign_md5;
        }
        if (strcasecmp($sign, $sign_md5) !== 0) {
            throw new \Exception('ERR_SYSTEM_SIGN');
        }

        return true;
    }

    /**
     * 校验访问接口权限
     * @return boolean
     */
    public function checkActionPermission() {
        $matches = explode('\\', get_class($this));
        $action = end($matches);
        if (array_key_exists($action, OauthConf::$actionWhiteList)) {
            if (!in_array($this->_client_id, OauthConf::$actionWhiteList[$action])) {
                return false;
            }
        }
        $action = $matches[2].'/'.$matches[3];
        if (isset(OauthConf::$actionBlackList[$this->_client_id])) {
            if (in_array($action, OauthConf::$actionBlackList[$this->_client_id])) {
                return false;
            }
        }
        return true;
    }

    /**
     * 获取自定义模板的名称
     * @param type $default 默认模板
     * @param type $tplKey  tpl设置key
     * @return type
     */
    public function getCustomTpl($default, $tplKey) {
        if (empty($default) || empty($tplKey)) {
            return false;
        }
        if (isset($this->clientConf['tpl'][$tplKey])) {
            return $this->clientConf['tpl'][$tplKey];
        }
        return $default;
    }

    /**
     * 获取根据auth_token设置在redis中的验证码的key
     * @param string auth_token
     * @return string 存放在redis中的key
     */
    public function authTokenCaptchaKey($authToken) {
        return "auth:token:captcha:$authToken";
    }

    /**
     * 获取site_id
     */
    public function getSiteId() {
        if (isset($this->clientConf['site_id'])) {
            return $this->clientConf['site_id'];
        }

        $data = $this->form->data;
        $siteId = isset($data['site_id']) ? intval(trim($data['site_id'])) : 1;
        if (!empty($siteId)) {
            return $siteId;
        }

        return 1;
    }

    public function checkIsInnerRequest() {
        if (isset($_SERVER['HTTP_LET_ME_THROUGH']) && $_SERVER['HTTP_LET_ME_THROUGH'] == 'OK') {
            if (preg_match('/^(172\.|10\.20)/i', $_SERVER['REMOTE_ADDR'])) {
                return true;
            }
        }
        return false;
    }

    public function risk_warning($event_type, $build_params) {
        if (empty($event_type) || !is_array($build_params))
            return false;
        $risk_res = risk_check();
        $risk_res = ($risk_res == false) ? 0 : $risk_res;
        $commit_params['@type'] = 'cn.com.bsfit.frms.obj.AuditObject';
        $commit_params['frms_biz_code'] = $event_type;
        $commit_params['frms_finger_print'] = $risk_res;
        $commit_params['frms_from'] = $build_params['from_source'];
        switch ($event_type) {
            case 'PAY.REG':
                $commit_params['frms_order_id'] = $build_params['user_id'];
                $commit_params['frms_phone_no'] = $build_params['mobile'];
                $commit_params['frms_create_time'] = time() * 1000;
                $commit_params['frms_invitation_code'] = $build_params['invite'];
                break;
            case 'PAY.SIGNED':
                $commit_params['frms_order_id'] = $build_params['user_id'];
                $commit_params['frms_phone_no'] = $build_params['mobile'];
                $commit_params['frms_create_time'] = time() * 1000;
                $commit_params['frms_card_no'] = $build_params['card_no'];
                $commit_params['frms_id_no'] = $build_params['id_no'];
                $commit_params['frms_user_name'] = urlencode($build_params['user_name']);
                break;
        }
        $event = new RiskCheckEvent($commit_params);
        $task_service = new GTaskService();
        $task_service->doBackground($event, 1);
    }

    /**
     * 显示错误
     *
     * @param $msg 消息内容
     * @param int $ajax
     * @param string $jump 跳转链接
     * @param int $stay 是否停留不跳转(0:跳转1:不跳转)
     * @param int $time 跳转等待时间
     * @param int $status 前端js判断状态
     */
    public function show_error($msg, $title = '', $ajax = 0, $stay = 0, $jump = '', $refresh_time = 3, $status = 0) {
        if ($ajax == 1) {
            $result = array();
            $result['status'] = $status;
            $result['info'] = $msg;
            $result['jump'] = $jump;
            header("Content-type: application/json; charset=utf-8");
            echo(json_encode($result));
        } else {
            $title = empty($title) ? $GLOBALS['lang']['ERROR_TITLE'] : $title;
            $this->tpl->assign('page_title', $title);
            $this->tpl->assign('error_title', $title);
            $this->tpl->assign('error_msg', $msg);

            $jump = empty($jump) ? $_SERVER['HTTP_REFERER'] : $jump;
            $jump = empty($jump) ? APP_ROOT . '/' : $jump;
            $lang_jump_tip = sprintf($GLOBALS['lang']['NEW_JUMP_TIP'], $refresh_time, $jump);

            $this->tpl->assign('jump', $jump);
            $this->tpl->assign('stay', $stay);
            $this->tpl->assign('refresh_time', $refresh_time);
            $this->tpl->assign('lang_jump_tip', $lang_jump_tip);
            $this->tpl->display('openapi/views/error.html');
            $this->template = null;
        }
        setLog(
                array('output' => array('ajax' => $ajax, 'jump' => $jump, 'msg' => $msg))
        );
        exit;
    }

    /**
      -     * GetCouponAccessToken 根据token获取用户优惠券入口的accessToken
      -     *
      -     * @author liguizhi <liguizhi@ucfgroup.com>
      -     * @date 2016-04-05
      -     * @param mixed $oauth_token
      -     * @param mixed $user_id
      -     * @access public
      -     * @return void
      +     * 加载开放平台数据
     */
    public function GetCouponAccessToken($oauth_token, $user_id) {
        try {
            $redis = \SiteApp::init()->dataCache->getRedisInstance();
            $couponAccessToken = md5($oauth_token . time() . $user_id);
            $redis->setex($couponAccessToken, 300, $user_id);
            return $couponAccessToken;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 加载开放平台数据
     */
    public function loadOpenData() {

        if ($this->_client_id != '7b9bd46617b3f47950687351' && $this->_client_id != 'db6c30dddd42e4343c82713e') {
            return null;
        }

        $siteId = $this->getSiteId();

        if (!Open::checkOpenSwitch()) {
            return array();
        }

        if (!$appInfo = Open::getAppBySiteId($siteId)) { //找不到app信息
            return array();
        }

        if ($appInfo['status'] != 1) {
            return array();
        }
        $appConf = Open::getSiteConfBySiteId($siteId);
        $data = array();
        $data['confInfo'] = Open::getWapTplData($appConf['confInfo']);
        $data['siteId'] = $appInfo['id'];
        $data['siteName'] = $appInfo['appName'];

        return $data;
    }

    /**
     * @一键成标、代理人校验
     * @param aray $info_list
     * @return int
     * @author:liuzhenpeng
     */

    public function checkCreditUser($info_list, $is_installment)
    {
        $request = new ProtoUser();
        $user_id = 0;
        if (!empty($info_list['wx_open_id']) && $info_list['user_types'] == 2) {
            $wx_user_id = Tools::getUserIdByOpenID($info_list['wx_open_id']);
            $request->setUserId(intval($wx_user_id));
            $userResponse = $GLOBALS['rpc']->callByObject(array(
                'service' => 'NCFGroup\Ptp\services\PtpUser',
                'method' => 'getUserInfoById',
                'args' => $request
            ));
            if ($userResponse->resCode === RPCErrorCode::SUCCESS) {
                if (
                    $userResponse->idno == $info_list['idno'] &&
                    $userResponse->realName == $info_list['real_name']
                ) {
                    $user_id = $wx_user_id;
                }
            }
        } else {
            $request->setIdno($info_list['idno']);
            $request->setMobile($info_list['mobile']);
            $request->setRealName($info_list['real_name']);
            $request->setUserTypes($info_list['user_types']);
            $request->setUserName($info_list['user_name']);

            $userResponse = $GLOBALS['rpc']->callByObject(array('service' => 'NCFGroup\Ptp\services\PtpUser', 'method' => 'getUserInfoByINM', 'args' => $request));

            if($userResponse->resCode) return -1;
            $user_id = $userResponse->getUserId();
        }

        unset($userResponse->resCode);

        if($is_installment == false){
            $userBankResponse = $GLOBALS['rpc']->callByObject(array('service' => 'NCFGroup\Ptp\services\PtpUser', 'method' => 'getBankInfoByUserid', 'args' => $userResponse));
            if($userBankResponse->resCode) return -2;
        }
        //p2p标的校验存管是否开户
        if ($user_id && isset($info_list['deal_type']) && $info_list['deal_type'] == 0) {
            $suvService = new SupervisionService();
            $svInfo = $suvService->svInfo($user_id, $needPurpose = 1);
            //如果存管开关打开并且该用户未开户，则返回错误
            if ($svInfo['status'] == 1 && $svInfo['isSvUser'] == 0) {
                return -3;
            } elseif (!$this->isBorrower($svInfo['userPurpose'])) {
                return -5;
            }
            $this->svStatus = intval($isSvUser['status']);
        }

        //校验借款用户的在途借款金额
        if (isset($info_list['deal_type']) && $info_list['deal_type'] == 0 && $user_id) {
            $uids = ($info_list['user_types'] == 2) ? array($user_id) : $userResponse->getAllUserId();
            $request->setAllUserId($uids);
            try {
                $borrowAmount = 0;
                if (isset($info_list['borrowAmount'])) {
                    $borrowAmount = doubleval($info_list['borrowAmount']);
                } else {
                    $borrowAmount = doubleval($info_list['borrow_amount']);
                }
                $otherBorrow = doubleval($info_list['otherBorrowing']);

                //本次借款和其他平台借款
                $otherTotalMoney = bcadd($borrowAmount, $otherBorrow, 5);
                $comRes = ($info_list['user_types'] == 2) ?  bccomp($otherTotalMoney, ConstDefine::LOAN_LIMIT_PER_TOTAL, 5) : bccomp($otherTotalMoney, ConstDefine::LOAN_LIMIT_ENT_TOTAL, 5);
                if ($comRes > 0 ) {
                    return -6;
                }

                $userMoney = $GLOBALS['rpc']->callByObject(array('service' => 'NCFGroup\Ptp\services\PtpUser', 'method' => 'getUnrepayP2pMoneyByUids', 'args' => $request));

                //本次借款和本平台借款
                $localTotalMoney = bcadd($borrowAmount, $userMoney, 5);
                $comRes = ($info_list['user_types'] == 2) ?  bccomp($localTotalMoney, ConstDefine::LOAN_LIMIT_PER, 5) : bccomp($localTotalMoney, ConstDefine::LOAN_LIMIT_ENT, 5);
                if ($comRes > 0 ) {
                    return -4;
                }

                //本次借款和本平台借款和其他平台借款
                $totalMoney = bcadd($localTotalMoney, $otherBorrow, 5);
                $comRes =($info_list['user_types'] == 2) ?  bccomp($totalMoney, ConstDefine::LOAN_LIMIT_PER_TOTAL, 5) : bccomp($totalMoney, ConstDefine::LOAN_LIMIT_ENT_TOTAL, 5);
                if ($comRes > 0 ) {
                    return -6;
                }

            } catch (\Exception $e) {
                Logger::error('checkLoanLimit Error:'.$e->getMessage());
                return -4;
            }

        }
        return $user_id ?: -1;
    }

    public function coverSiteId() {
        if (!isset($_REQUEST['site_id'])) {
            return true;
        }
        $siteId = trim($_REQUEST['site_id']);
        if ($siteId > 1) {
            \libs\web\Open::coverSiteId();
            $appInfo = \libs\web\Open::getAppBySiteId($siteId);
            \libs\web\Open::coverSiteInfo($appInfo);
        }

        return true;
    }
    /**
     * 校验用户是否开通存管用户
     * @param unknown $uid
     * @return number
     */
    public function checkSupervision($uid) {
        //校验用户是否开通存管户
        $userIds = explode(',',$uid);
        $result = array();
        foreach ($userIds as $value) {
            if ($value) {
                $suvService = new SupervisionService();
                $isSvUser = $suvService->svInfo(intval($value), 1);
                if ($isSvUser['status'] == 1 && $isSvUser['isSvUser'] == 1) {
                    $result[$value] = 1;//开通存管借款户
                } else {
                    $result[$value] = 0;//未开通存管借款户
                }
            }
        }
        return $result;
    }

    private function isBorrower($userPurpose) {
        return in_array(intval($userPurpose), [EnterpriseModel::COMPANY_PURPOSE_FINANCE, EnterpriseModel::COMPANY_PURPOSE_MIX]);
    }

    protected function checkBorrowAuth($typeId, $userArr) {
        $result = ['code' => 0, 'msg' => ''];
        //首山资产消费贷必须授权
        if (empty($typeId)) {
            return $result;
        }
        $typeModel = new \core\dao\DealLoanTypeModel();
        $typeTag = $typeModel->getLoanTagByTypeId($typeId);
        Logger::info('checkBorrowAuth.typeTag:'.$typeTag.',userArr:'.json_encode($userArr));
        if (in_array($typeTag, ['XFD','XJDGFD']) && is_array($userArr)) {
            foreach ($userArr as $k => $v) {
                $checkInfo = (new SupervisionService())->checkAuth($v, SupervisionService::GRANT_TYPE_BORROW);
                if ($checkInfo) {
                    $result['code'] = 1;
                    $result['msg'] = $k.'('.$v.'):'.$checkInfo['grantMsg'];
                }
            }
        }
        return $result;
    }

    protected function getSign($req) {
        unset($req['sign']);
        $sortedReq = $this->clientConf['client_secret'];
        ksort($req);
        reset($req);
        while (list ($key, $val) = each($req)) {
            if (!is_null($val)) {
                $sortedReq .= $key . $val;
            }
        }
        $sortedReq .= $this->clientConf['client_secret'];
        $this->log['sortedReq'] = $sortedReq;
        return strtoupper(md5($sortedReq));
    }

    protected function getOpenapiUrl($req) {
        $req['client_id'] = $this->_client_id;
        $req['timestamp'] = time();
        $req['sign'] = $this->getSign($req);
        return http_build_query($req);
    }

    protected function getHost() {
        $http = app_conf('ENV_FLAG') == 'online' ? 'https://' : 'http://';
        return $http . $_SERVER['HTTP_HOST'];
    }

}

// END class BaseAction
