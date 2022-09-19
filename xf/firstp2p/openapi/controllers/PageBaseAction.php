<?php
namespace openapi\controllers;

use NCFGroup\Protos\Ptp\ProtoAccessToken;
use NCFGroup\Protos\Ptp\RPCErrorCode;
use NCFGroup\Protos\Ptp\ProtoUser;
use libs\rpc\Rpc;
use openapi\lib\OpenAction;
use openapi\conf\Error;
use openapi\conf\OauthConf;
use libs\web\Form;
use libs\utils\Logger;
use openapi\lib\Tools;
use NCFGroup\Task\Services\TaskService AS GTaskService;
use core\event\RiskCheckEvent;

class PageBaseAction extends OpenAction {

    const IS_H5 = false;

    protected $current_token_user = false;
    public $errorCode = null;
    public $rpc;
    public $sys_param_rules = array(
        "client_id" => array("filter" => "string"),
        "timestamp" => array("filter" => "string"),
        "format" => array("filter" => "string"),
        "v" => array("filter" => "string"),
        "sign" => array("filter" => "string"),
        "oauth_token" => array("filter" => "string"),
        "openId" => array("filter" => "string"),
        "from_platform" => array("filter" => "string"),
        "site_id" => array("filter" => 'string', "option" => array("optional"=>true)),
    );
    public $json_data_err = '';

    protected $_user_id = null;
    protected $_client_id = '';
    public $clientConf = array();

    // 默认关键数据不打码，其他数据需要大妈
    protected $_mosaic = array();

    public function __construct() {
        parent::__construct();
        $this->rpc = new Rpc();
//      $this->template = $this->getTemplate(); //覆盖phoenix框架的模板路径
        $this->setTpl();
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
        if(!get_magic_quotes_gpc()){
            $req['client_id'] = addslashes($_REQUEST['client_id']);
        } else {
            $req['client_id'] = trim($_REQUEST['client_id']);
        }
        $this->_client_id = trim($req['client_id']);
        $client_conf = $GLOBALS['sys_config']['OAUTH_SERVER_CONF'];
        if (isset($client_conf[$this->_client_id])) {
            $this->clientConf = $client_conf[$this->_client_id];
            if (isset(OauthConf::$defaultCoupon[$this->_client_id])) {
                $this->clientConf['default_coupon'] = OauthConf::$defaultCoupon[$this->_client_id];
            }
            if ( isset($client_conf[$this->_client_id]['mosaic']) && is_array($client_conf[$this->_client_id]['mosaic']) ){
                $this->_mosaic = $client_conf[$this->_client_id]['mosaic'];
            }
        }
    }

    private function _getUserIdByOpenID() {
        $req = $this->form->data;
        if (isset($req['openId'])) {
            $this->_user_id = Tools::getUserIdByOpenID($req['openId']);
            if (!$this->_user_id) {
                throw new \Exception('ERR_OPEN_ID');
            }
        }
    }

    /**
     * 继承父类 _before_invoke
     */
    public function _before_invoke() {
#        $this->_getClientConf();
#        $this->_getUserIdByOpenID();
        return true;
    }

    /**
     * 继承父类_after_invoke，实现数据展示
     */
    public function _after_invoke() {
        if ($this->errorCode != 0) {
            $this->template = 'openapi/views/coupon/exchange_fail.html';
            $this->tpl->assign('errMsg', $this->errorMsg);
        }
        $this->tpl->display($this->template);
        /*
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
         */
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
    protected function getUserByAccessToken() {
        if ($this->_user_id) {
            $userIdResponse = new ProtoUser();
            $userIdResponse->setUserId(intval($this->_user_id));
        } else {
            $userIdResponse = $this->getUserIdByAccessToken();
        }
        if (!empty($userIdResponse)) {
            $userResponse = $GLOBALS['rpc']->callByObject(array(
                'service' => 'NCFGroup\Ptp\services\PtpUser',
                'method' => 'getUserInfoById',
                'args' => $userIdResponse
            ));
            if ($userResponse->resCode === RPCErrorCode::SUCCESS) {
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
        $siteId   = $this->getSiteId();
        $paramKey = !empty($this->log['getParams']) ? 'getParams' : 'postParams';
        if (!isset($this->log[$paramKey]['site_id'])) {
            $this->log[$paramKey]['site_id'] = $siteId;
        }

        $this->log['errno'] = $this->errorCode;
        $tmJsonData = cleanSensitiveField($this->json_data);
        $this->log['output'] = substr(json_encode($tmJsonData), 0, 200);
        $this->log['process'] = sprintf("%d", (microtime(1) - $this->log['process']) * 1000);
        $level = $this->log['level'];
        $this->log['uid'] = isset($this->current_token_user) ? $this->current_token_user : '';
        unset($this->log['level']);
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
        $siteId = intval(trim($data['site_id']));
        if (!empty($siteId)) {
            return $siteId;
        }

        return 1;
    }

    public function checkIsInnerRequest() {
        if ($_SERVER['HTTP_LET_ME_THROUGH'] == 'OK') {
            if (preg_match('/^(172\.|10\.20)/i', $_SERVER['REMOTE_ADDR'])) {
                return true;
            }
        }
        return false;
    }

    public function risk_warning($event_type, $build_params)       
    {       
        if(empty($event_type) || !is_array($build_params)) return false;       
        $risk_res = risk_check();       
        $risk_res = ($risk_res == false) ? 0 : $risk_res;
        $commit_params['@type'] = 'cn.com.bsfit.frms.obj.AuditObject';
        $commit_params['frms_biz_code']     = $event_type;
        $commit_params['frms_finger_print'] = $risk_res;
        $commit_params['frms_from']         = $build_params['from_source'];
        switch($event_type){
            case 'PAY.REG':
                $commit_params['frms_order_id']        = $build_params['user_id'];
                $commit_params['frms_phone_no']        = $build_params['mobile'];
                $commit_params['frms_create_time']     = time()*1000;
                $commit_params['frms_invitation_code'] = $build_params['invite'];
            break;
            case 'PAY.SIGNED':
                $commit_params['frms_order_id']   = $build_params['user_id'];
                $commit_params['frms_phone_no']    = $build_params['mobile'];
                $commit_params['frms_create_time'] = time()*1000;
                $commit_params['frms_card_no']     = $build_params['card_no'];
                $commit_params['frms_id_no']       = $build_params['id_no'];
                $commit_params['frms_user_name']   = urlencode($build_params['user_name']);
                break;
        }
        $event = new RiskCheckEvent($commit_params);
        $task_service = new GTaskService();     
        $task_service->doBackground($event, 1);
     }

}

// END class BaseAction
