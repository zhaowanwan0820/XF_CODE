<?php

/**
 * BaseAction class file.
 */

namespace api\controllers;

use api\conf\Error;
use libs\utils\Logger;
use libs\web\Action;
use libs\rpc\Rpc;
use api\processor\Processor;
use core\service\user\UserService;
use core\dao\BaseModel;

/**
 * BaseAction class
 */
class BaseAction extends Action {
    // 默认的site_id为100，表示普惠
    protected $defaultSiteId = 100;
    // 授权的用户信息
    protected $user = false;
    // 是否启用session
    protected $useSession = false;
    // 是否需要授权
    protected $needAuth = true;
    // 是否采用token验证
    protected $isTokenAuth = false;
    // 特殊处理processor
    protected $processor;
    // 本地rpc的封装
    public $rpc;

    protected $json_data = array();

    public function __construct() {
        parent::__construct();

        jump_to_https(); // 如果非https，那么尝试跳转https

        $this->rpc = new Rpc();
        $this->isTokenAuth = isset($_REQUEST['token']) ? true : false;
    }

    public function _before_invoke() {
        // 增加监控点，类似WEB_CONTROLLERS_USER_LOGIN
        \libs\utils\Monitor::add(strtoupper(str_replace('\\', '_', get_called_class())));
        return true;
    }

    /**
     * 权限检查
     */
    public function authCheck() {
        return true;
    }

    /**
     * 显示异常
     */
    public function show_exception(\Exception $e) {
        Logger::error('AppApiException. message:' . $e->getMessage() . ', file:' . $e->getFile() . ', line:' . $e->getLine());

        // 设置错误信息
        $this->errorMsg = $e->getMessage();
        $this->errorCode = 1001;

        $this->_after_invoke();
    }

    /**
     * 获取基类
     *
     * return string
     */
    public function getRoot() {
        return __CLASS__;
    }

    /**
     * invoke的前置工作, 初始化form数据验证，session配置，登录状态等
     *
     * @return void
     */
    public function init() {
        // 如果传了token值来标识，就不要用session，保证性能，防止session的滥用
        // 开启session
        if (true === $this->useSession || ($this->isWapCall() && !isset($_REQUEST['token']))) {
            // 对于wap的请求，需要session支持
            \es_session::start();
        }

        // 对于wap的api请求，需要进行特殊处理
        if ($this->isWapCall()) {
            $context = $this->getContext();
            $apiName = $context['controller']."_".$context['action'];
            $isPost = $_SERVER['REQUEST_METHOD'] == 'POST' ? true : false;
            $params = $isPost ? $_POST : $_GET;
            $processor = Processor::factory($apiName, $params, $context, $isPost);
            $this->processor = $processor;
            $result = $processor->checkApiParams();
            if ($result === false) {
                return false;
            }
        }

        // 需要登录授权验证
        if ($this->needAuth) {
            $token = isset($_REQUEST['token']) ? $_REQUEST['token'] : '';
            $this->getUserByToken(true, $token);
            // 没有获取到用户信息，初始化失败
            if (empty($this->user)) {
                return false;
            }
        }

        return true;
    }

    /**
     * processor用来获取上下文内容
     */
    private function getContext($lower = true) {
        $calledClass = str_replace('\\', '_', get_called_class());
        $arr = explode('_', $calledClass);
        $action = array_pop($arr);
        $controller = array_pop($arr);
        return array(
           'controller' => $lower ? strtolower($controller) : $controller,
           'action' => $lower ? strtolower($action) : $action
        );
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
     * 继承父类_after_invoke，实现数据展示
     */
    public function _after_invoke() {
        $arr_result = array();
        if ($this->errorCode == 0) {
            $arr_result["errno"] = 0;
            $arr_result["error"] = '';
            // 数据替换处理
            $this->json_data = $this->replaceObjByRow($this->json_data);
            $arr_result["data"] = $this->json_data;
        } else {
            $arr_result["errno"] = $this->errorCode;
            $arr_result["error"] = $this->errorMsg;
            $arr_result["timestamp"] = time();
            $arr_result["data"] = "";
        }

        // 对于processor的处理
        if ($this->errorCode == 0 && $this->processor) {
            $this->processor->checkApiReturn($arr_result);
            $this->processor->afterInvoke();
            $arr_result = $this->processor->getApiReturn();
        }

        header('Content-type: application/json;charset=UTF-8');
        echo json_encode($arr_result, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 将BaseModel的对象方式转化成数组，为了返回json数据
     */
    public function replaceObjByRow($data) {
        // 如果是BaseModel，直接返回结果
        if ($data instanceof BaseModel) {
            return $data->getRow();
        }

        // 非数组，直接返回
        if (!is_array($data)) {
            return $data;
        }

        // 对于数组，循环处理
        $result = $data;
        foreach ($data as $key => $value) {
            $value = ($value instanceof BaseModel) ? $value->getRow() : $value;
            $value = $this->replaceObjByRow($value);
            $result[$key] = $value ;
        }

        return $result;
    }

    // wap端调用api接口，和wap端的约定
    public function isWapCall() {
        return isset($_REQUEST['format']) && 'json' == $_REQUEST['format'];
    }

    public function isAppDevice() {
        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        return preg_match('/wx/i', $ua) ? true : false;
    }

    /**
     * 根据token获取登陆用户信息
     * @param bool $need_err
     * @param string $token
     * @return bool|array
     */
    protected function getUserByToken($need_err = true, $token = '') {
        if (!empty($this->user)) {
            $GLOBALS['user_info'] = $this->user;
            return $this->user;
        }

        if (!$this->isTokenAuth) {
            // 没有token，也不是wap接口请求，则返回false
            if (!$this->isWapCall()) {
                if ($need_err == true && $this->needAuth) {
                    $this->setErr('ERR_GET_USER_FAIL');
                }

                return false;
            }

            $this->user = UserService::getLoginUser();
            $GLOBALS['user_info'] = $this->user;
            return $this->user;
        }

        $token = isset($this->form->data['token']) ? $this->form->data['token'] : (!empty($token) ? $token : '');
        if (empty($token)) {
            if ($need_err == true) {
                $this->setErr('ERR_GET_USER_FAIL');
            }

            return false;
        }

        // 通过token获取用户信息
        try {
            $tokenInfo = UserService::getUserByCode($token);
        } catch (\Exception $ex) {
            if ($need_err == true) {
                $this->setErr('ERR_SYSTEM', $ex->getMessage());
            }

            return false;
        }

        if (!empty($tokenInfo['code'])) {
            if ($need_err == true) {
                $this->setErr($tokenInfo['code'], $tokenInfo['reason']);
            }

            return false;
        }

        $this->user = $tokenInfo['user'];
        UserService::setLoginUser($this->user);
        $GLOBALS['user_info'] = $this->user;
        return $this->user;
    }

    /**
     * 通过token获取用户信息-APP的H5页面
     * @param string $token
     * @param boolean $needError
     */
    protected function getUserByTokenForH5($token, $needError = true) {
        if (empty($token)) return false;

        return $this->getUserByToken($needError, $token);
    }

    protected function logInit() {
        $this->log = array(
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
            'method' => $_SERVER['REQUEST_METHOD'],
            'process' => microtime(true),
            'postParams' => cleanSensitiveField($_POST),
            'getParams' => $_GET,
            'output' => '',
            'analyze' => isset($_SERVER['HTTP_CHANNEL']) ? $_SERVER['HTTP_CHANNEL'] : ''
        );
    }

    protected function log() {
        $this->log['errno'] = $this->errorCode;
        $this->log['errmsg'] = $this->errorMsg;
        $tmJsonData = cleanSensitiveField($this->json_data);
        $this->log['output'] = $tmJsonData;
        // 输出截断
        //$this->log['output'] = substr(json_encode($tmJsonData), 0, 900);
        // 请求处理时间
        $this->log['process'] = sprintf("%d", (microtime(true) - $this->log['process']) * 1000);
        $this->log['uid'] = $this->user ? $this->user['id'] : '';
        $_log = getLog();
        if (is_array($_log)) {
            $this->log = array_merge($this->log, $_log);
        }

        Logger::stats($this->log);
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
    protected function getHost() {
        $http = app_conf('ENV_FLAG') == 'online' ? 'https://' : 'http://';
        return $http . $_SERVER['HTTP_HOST'];
    }
}

// END class BaseAction
