<?php

/**
 * BaseAction class file.
 *
 * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
 * */

namespace api\controllers;

use libs\rpc\Rpc;
use libs\web\Action;
use api\conf\Error;
use libs\utils\Logger;
use libs\utils\LoggerBusiness;

/**
 * BaseAction class
 *
 * @packaged default
 * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
 * */
class BaseAction extends Action {

    protected $current_token_user = false;
    public $errorCode = null;
    public $rpc;
    protected $view_version = '_v10';

    protected $useSession = false;

    const IS_H5 = false;
    public $businessLog = array();

    public function __construct() {
        parent::__construct();

        jump_to_https(); // 如果非https，那么尝试跳转https

        $this->rpc = new Rpc();
        $this->template = $this->getTemplate(); //覆盖phoenix框架的模板路径
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
        $staticPath = APP_ROOT . "/static";
        $staticSite = $staticPath;
        $this->tpl->assign('APP_HOST', $this->getHost());
        $this->tpl->assign("STATIC_PATH", $staticPath);
        $this->tpl->assign("STATIC_SITE", $staticSite);
        // $this->tpl->assign("STATIC_SITE", 'http://guoxinzhuo.firstp2plocal.com/static');
        // 红包币名称全局变量
        $this->tpl->assign('new_bonus_title', app_conf('NEW_BONUS_TITLE'));
        $this->tpl->assign('new_bonus_unit', app_conf('NEW_BONUS_UNIT'));

        $site_id = \libs\utils\Site::getId();
        $this->tpl->assign('site_id', $site_id);
        $this->tpl->assign('isFirstp2p', $site_id == 100 ? 1 : 0);
    }

    /**
     * 获取模板文件路径, 默认存放在与controllers平级的views目录下
     * controllers驼峰命名对应下划线分割的模板名
     *
     * @return string 模板文件路径
     * */
    public function getTemplate($template_name = null) {
        $class_path = str_replace('\\', '/', get_class($this));
        //controllers驼峰命名转换下划线分割的模板名
        $class_path_array = explode('/', $class_path);
        $class_name = array_pop($class_path_array); //类名
        $tpl_name = ""; //模板名
        if (!$template_name) {
            for ($i = 0; $i < strlen($class_name); $i++) {
                $char_lower = strtolower($class_name{$i});
                if ($i > 0 && $class_name{$i} != $char_lower) {
                    $tpl_name .= "_";
                }
                $tpl_name .= $char_lower;
            }
        } else {
            $tpl_name = trim(strtolower($template_name));
        }
        array_push($class_path_array, $tpl_name);
        $class_path = implode('/', $class_path_array);
        $view_dir = '/views/' . ($this->view_version ? $this->view_version . '/' : '');
        return str_replace('/controllers/', $view_dir, $class_path) . '.html';
    }

    /**
     * invoke的前置工作, 初始化form数据验证，session配置，登录状态等
     *
     * @return void
     * */
    public function init() {
        //增加监控点，类似WEB_CONTROLLERS_USER_LOGIN
        \libs\utils\Monitor::add(strtoupper(str_replace('\\', '_', get_called_class())));
        if (true === $this->useSession) {
            \es_session::start();
        }
    }

    /**重写父类execute**/
    public function execute()
    {
        try {
            parent::execute();
        } catch (\Exception $e) {
            Logger::info('ApiException:'.$e->getCode().'-'.$e->getMessage());
            $this->errno = $e->getCode() ?: -1;
            $this->error = $e->getMessage();
            $this->_after_invoke();
        }
    }

    /**
     * 如果出错，允许表层设置错误
     */
    public function setErr($err, $error = "") {
        $arr = Error::get($err);
        $errno = $arr["errno"] ?: -1;
        $error = empty($error) ? $arr["errmsg"] : $error;
        throw new \Exception($error, $errno);
    }

    /**
     * 显示异常
     */
    public function show_exception(\Exception $e) {
        Logger::error('AppControllerException. message:' . $e->getMessage() . ', file:' . $e->getFile() . ', line:' . $e->getLine());
        \libs\utils\PaymentApi::log('show_exception:' . $e->getMessage());

        // 设置错误信息
        $this->error = $e->getMessage();
        $this->errno = 1001;

        $this->_after_invoke();
    }

    /**
     * 继承父类_after_invoke，实现数据展示
     */
    public function _after_invoke() {
        $class = get_called_class();
        $is_h5 = $this->tpl->get_template_vars('is_h5');

        if ($class::IS_H5 || $is_h5) {
            if ($this->errno != 0) {
                $this->tpl->assign('logId', Logger::getLogId());
                $this->tpl->assign('error', $this->error);
                $this->tpl->assign('errno', $this->errno);
                $this->template = 'api/views/error/error.html';
                Logger::error('_after_invoke error:' . $this->error . '  logId:' .Logger::getLogId());
            }
            $this->tpl->display($this->template);
        } else {
            $arr_result = array();
            if ($this->errno == 0) {
                $arr_result["errno"] = 0;
                $arr_result["error"] = "";
                $arr_result["data"] = $this->json_data;
            } else {
                $arr_result["errno"] = $this->errno;
                $arr_result["error"] = $this->error;
                $arr_result["timestamp"] = time();
                $arr_result["data"] = "";
            }
            if (isset($_REQUEST['debug']) && $_REQUEST['debug'] == 1) {
                var_export($arr_result);
            } else {
                header('Content-type: application/json;charset=UTF-8');
                echo json_encode($arr_result, JSON_UNESCAPED_UNICODE);
            }
        }
    }

    /**
     * 根据token获取登陆用户信息
     * @param bool $need_err
     * @param string $token
     * @return bool|array
     */
    protected function getUserByToken($need_err = true, $token = '') {
        if (empty($this->current_token_user)) {
            $token = isset($this->form->data['token']) ? $this->form->data['token'] : (!empty($token) ? $token : '');
            if (empty($token)) {
                return false;
            }
            $token_info = $this->rpc->local('UserTokenService\getUserByToken', array($token));
            if (!empty($token_info['code'])) {
                Logger::error('登录获取token失败'.json_encode($token_info));
                if ($need_err == true) {
                    if ($token_info['code'] == 309) {
                        //$this->rpc->local('UserTokenService\deleteToken', array($token));
                        $this->setErr('ERR_USER_KICKED');
                    } else {
                        $this->setErr('ERR_GET_USER_FAIL');
                    }
                }
                return false;
            }
            $this->current_token_user = $token_info['user'];
            $GLOBALS['user_info'] = $token_info['user'];
            \core\service\UserService::setLoginUser($token_info['user']);
        }

        return $this->current_token_user;
    }

    /**
     * 通过token获取用户信息-APP的H5页面
     * @param string $token
     * @param boolean $needError
     */
    protected function getUserByTokenForH5($token, $needError = true) {
        if (empty($token))
            return false;
        return $this->getUserByToken($needError, $token);
    }

    protected function logInit() {
        $post = $_POST;
        $post = cleanSensitiveField($post);

        $this->log = array(
            'level' => Logger::STATS,
            'platform' => 'api',
            'device' => isset($_SERVER['HTTP_OS']) ? $_SERVER['HTTP_OS'] : "",
            'errno' => '',
            'errmsg' => '',
            'ip' => get_client_ip(),
            'host' => $_SERVER['HTTP_HOST'],
            'appVersion' => !empty($_SERVER['HTTP_VERSION']) ? $_SERVER['HTTP_VERSION'] : '',
            'uri' => $_SERVER['REQUEST_URI'],
            'query' => $_SERVER['QUERY_STRING'],
            'referer' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '-',
            'cookie' => $_COOKIE,
            'method' => $_SERVER['REQUEST_METHOD'],
            'process' => microtime(1),
            'postParams' => $post,
            'getParams' => $_GET,
            'output' => '',
            'analyze' => isset($_SERVER['HTTP_CHANNEL']) ? $_SERVER['HTTP_CHANNEL'] : '',
        );
        $this->businessLog['req_time'] = time();
    }

    protected function log() {
        $this->log['errno'] = $this->errno;
        $this->log['errmsg'] = $this->error;
        $this->businessLog['busi_msg'] = $this->error;
        $tmJsonData = cleanSensitiveField($this->json_data);
        $this->log['output'] = substr(json_encode($tmJsonData), 0, 900);
        $this->log['process'] = sprintf("%d", (microtime(1) - $this->log['process']) * 1000);
        $this->businessLog['resp_time'] = $this->log['process'];
        $this->log['uid'] = isset($GLOBALS['user_info']['id']) ? $GLOBALS['user_info']['id'] : '';
        $_log = getLog();
        if (is_array($_log)) {
            $this->log = array_merge($this->log, $_log);
        }
        $jsonLog = json_encode($this->log, JSON_UNESCAPED_UNICODE);
        $jsonLog = str_replace('\/', '/', $jsonLog);
        call_user_func('\libs\utils\Logger::' . "STATS", $jsonLog);
        if (isset($this->businessLog['req_time'])) {//http重定向https的情况下不写businesslog
            LoggerBusiness::write('api',$this->businessLog);
        }
    }

    /**
     * 获取APP里面，通用Scheme的相关定义
     * @param string $type
     * @param array $params
     * @return string
     * @author 郭峰<guofeng3@ucfgroup.com>
     * @see http://wiki.corp.ncfgroup.com/pages/viewpage.action?pageId=13697048
     * @example
     * 1、跳转到H5页面：firstp2p://api?type=webview&url=http%3A%2F%2Fwei2.p2pdev.ncfgroup.com%2Recharge
     * 2.1、跳转到源生native页面-跳转到登录页：firstp2p://api?type=native&name=login
     * 2.2、跳转到源生native页面-跳转到home页：firstp2p://api?type=native&name=home
     * 2.3、跳转到源生native页面-跳转到"我的"页面：firstp2p://api?type=native&name=mine
     * 3.1、本地的一些操作-弹一个对话框：firstp2p://api?type=local&action=showdialog&title=%e6%8f%90%e7%a4%ba&message=%e4%bd%a0%e5%a5%bd
     * 3.2、本地的一些操作-弹一个气泡：firstp2p://api?type=local&action=toast&message=%e4%bd%a0%e5%a5%bd
     * 3.3、本地的一些操作-本地重新刷新：firstp2p://api?type=local&action=refresh
     * $this->getAppScheme('webview', array('url'=>'http://m.firstp2p.com'));
     * $this->getAppScheme('native', array('name'=>'login'));
     * $this->getAppScheme('native', array('name'=>'home'));
     * $this->getAppScheme('native', array('name'=>'mine'));
     * $this->getAppScheme('local', array('action'=>'showdialog', 'title'=>'提示', 'message'=>'对话框提示消息'));
     * $this->getAppScheme('local', array('action'=>'toast', 'message'=>'气泡提示消息'));
     * $this->getAppScheme('local', array('action'=>'refresh'));
     * $this->getAppScheme('closeall');
     */
    public function getAppScheme($type = 'webview', $params = array()) {
        $scheme = '';
        switch ($type) {
            case 'webview': // 跳转到H5页面
                $scheme = isset($params['url']) ? sprintf('firstp2p://api?type=%s&url=%s', $type, urlencode($params['url'])) : '';
                break;
            case 'native': // 跳转到源生native页面
                $scheme = sprintf('firstp2p://api?type=%s&name=%s', $type, isset($params['name']) ? $params['name'] : 'home');
                break;
            case 'local': // 本地的一些操作
                if (isset($params['action'])) {
                    switch ($params['action']) {
                        case 'showdialog': // 弹对话框，所需参数action、title、message
                            $scheme = sprintf('firstp2p://api?type=%s&action=%s&title=%s&message=%s', $type, $params['action'], isset($params['title']) ? urlencode($params['title']) : '提示', urlencode($params['message']));
                            break;
                        case 'toast': // 弹气泡，所需参数action、message
                            $scheme = sprintf('firstp2p://api?type=%s&action=%s&message=%s', $type, $params['action'], urlencode($params['message']));
                            break;
                        case 'refresh':
                            $scheme = sprintf('firstp2p://api?type=%s&action=%s', $type, $params['action']);
                            break;
                        default:
                            $scheme = '';
                            break;
                    }
                }
                break;
            case 'closeall': // 关闭当前所有的H5页面
                $scheme = 'firstp2p://api?type=closeall';
                break;
            default:
                $scheme = '';
                break;
        }
        return $scheme;
    }

    /**
     * 获取Api域名
     */
    protected function getHost()
    {
        $http = app_conf('ENV_FLAG') == 'online' ? 'https://' : 'http://';
        return $http . $_SERVER['HTTP_HOST'];
    }

}

// END class BaseAction
