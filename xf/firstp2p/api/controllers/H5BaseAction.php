<?php
namespace api\controllers;

use api\conf\Error;
use api\conf\ConstDefine;
use api\controllers\BaseAction;
use core\dao\BaseModel;

class H5BaseAction extends BaseAction {

    public $is_firstp2p;
    protected $app_version = 100;
    protected $view_dir = '_v10';

    public function __construct()
    {
        parent::__construct();

        $site_id = \libs\utils\Site::getId();
        $this->is_firstp2p = $site_id == 100 ? true : false;
    }

    public function _before_invoke() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $datas = $_POST;
        } elseif ($_SERVER['REQUEST_METHOD'] == 'GET') {
            $datas = $_GET;
            $datas = array_diff_key($datas, array('act' => '', 'city' => '', 'ctl' => '', '1' => '', '2' => ''));
        } else {
            $this->setErr('ERR_SIGNATURE_FAIL'); // 签名不正确
            return false;
        }
        $apiVersion = isset($_SERVER['HTTP_APIVERSION']) ? $_SERVER['HTTP_APIVERSION'] : 0;

        try {
            $this->app_version = $this->getAppVersion();
            // app版本小于300的,服务端接口直接拒绝(2.x.x版本客户端泄露紧急修复)
            if ($this->app_version < 300) {
                throw new \Exception(Error::getMsg('ERR_VERSION'), Error::getCode('ERR_VERSION'));
            }
            $this->setAutoViewDir();

            $userInfo = $this->getUserByToken(false);
            $userId = isset($userInfo['id']) ? $userInfo['id'] : 0;
            // 特殊用户处理
            if (\libs\utils\Block::isSpecialUser($userId)) {
                define('SPECIAL_USER_ACCESS', true);
                if (\libs\utils\Block::checkAccessLimit($userId) === false) {
                    throw new \Exception('刷新过于频繁，请稍后再试', SHOW_EXCEPTION_MESSAGE_CODE);
                }
            }
        } catch (\Exception $e) {
            $this->errno = $e->getCode();
            $this->error = $e->getMessage();
            return false;
        }
        return true;
    }

    public function _after_invoke() {
        $this->setAutoViewDir();
        $this->afterInvoke();
        parent::_after_invoke();
    }

    /**
     * 设置view下的版本目录
     * @author longbo
     */
    public function setViewVersion($view_dir = '_v10') {
        $this->view_version = $view_dir;
        $this->template = $this->getTemplate();
        return;
    }

    /**
     * 根据app版本号自动匹配模板目录, 找不到向上个版本目录找
     * @author longbo
     */
    public function setAutoViewDir() {
        $is_mached = 0;
        $version_dir = array_reverse(ConstDefine::$version_dir, true);
        foreach ($version_dir as $ver_num => $value) {
            if ($ver_num > 0 && $ver_num <= $this->app_version) {
                if (is_array($value)) {
                    $this->view_version = $value[0];
                    $class_dir_arr = explode('/', str_replace('\\', '/', get_class($this)));
                    $class_name = array_pop($class_dir_arr);
                    $class_key = end($class_dir_arr) . '/' . $class_name;
                    if (isset($value[$class_key])) {
                        $this->template = $this->getTemplate($value[$class_key]);
                        $is_mached = 1;
                        break;
                    }
                } else {
                    $this->view_version = $value;
                    if (!empty($this->template)) {
                        $tplPath = explode('/', $this->template);
                        $tplName = str_replace('.html', '', array_pop($tplPath));
                        $this->template = $this->getTemplate($tplName);
                    } else {
                        $this->template = $this->getTemplate();
                    }
                    if (@is_file(APP_ROOT_PATH . $this->template)) {
                        $is_mached = 1;
                        break;
                    }
                }
            }
        }
        if (!$is_mached) {
            $this->setViewVersion();
        }
        return;
    }

    /**
     * 获取APP的VERSION
     */
    protected function getAppVersion($initVersion = 100) {
        $appVersion = isset($_SERVER['HTTP_VERSION']) ? intval($_SERVER['HTTP_VERSION']) : 0;
        // HEADER里的VERSION大于100才写入cookie
        $appVersion > $initVersion && \es_cookie::set('appVersion', $appVersion);
        // HEADER里读不到时，从cookie获取
        $appVersion <= 0 && $appVersion = \es_cookie::get('appVersion');
        return max($initVersion, intval($appVersion));
    }

    /**
     * 获取Api域名
     */
    protected function getHost()
    {
        $http = app_conf('ENV_FLAG') == 'online' ? 'https://' : 'http://';
        return $http . $_SERVER['HTTP_HOST'];
    }

    /**
     * 获取客户端系统
     */
    protected function getOs()
    {
        $platform = 0;
        $str = isset($_SERVER['HTTP_OS']) ? $_SERVER['HTTP_OS'] : $_SERVER['HTTP_USER_AGENT'];
        if (stripos($str, 'ios') !== false) {
            $platform = 1;
        } else if (stripos($str, 'android') !== false) {
            $platform = 2;
        }

        return $platform;
    }

    public function replaceObjByRow($data) {
        $result = ($data instanceof BaseModel) ? $data->getRow() : $data;
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $value = ($value instanceof BaseModel) ? $value->getRow() : $value;
                $value = $this->replaceObjByRow($value);
                $result[$key] = $value ;
            }
        }

        return $result;
    }

    public function afterInvoke() {
        if ($this->isWapCall()) {
            $data = $this->tpl->get_template_vars();
            unset($data['APP_HOST']);
            unset($data['STATIC_PATH']);
            unset($data['STATIC_SITE']);
            unset($data['data']['token']);
            unset($data['token']);
            $data = $this->replaceObjByRow($data);
            $arrResult = array();
            if ($this->errno == 0) {
                $arrResult["errno"] = 0;
                $arrResult["error"] = "";
                $arrResult["data"] = empty($this->json_data) ? $data : $this->json_data;
            } else {
                $arrResult["errno"] = $this->errno;
                $arrResult["error"] = $this->error;
                $arrResult["timestamp"] = time();
                $arrResult["data"] = "";
            }
            header('Content-type: application/json;charset=UTF-8');
            echo json_encode($arrResult, JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    //wap端调用api接口
    public function isWapCall() {
        return isset($_REQUEST['format']) && 'json' == $_REQUEST['format'];
    }
}