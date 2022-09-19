<?php

/**
 * Created by PhpStorm.
 * User: luzhengshuai
 * Date: 2018/12/03
 * Time: 14:18
 */

namespace libs\utils;

class Upgrade {

    public static $uriInfo;
    public static $tpl;

    /**
     * 整个系统维护-- 如mysql不可用
     */
    public static function system()
    {
    }

    /**
     * 部分功能维护
     */
    public static function partial()
    {
        // 灰度不维护
        if (isP2PRc()) {
            return false;
        }

        if (PHP_SAPI == 'cli' || !app_conf('SYSTEM_PARTIAL_UPGRADE')) {
            return false;
        }

        // 获取当前请求地址
        list($module, $action) = self::parseURI();

        $upgradeGroup = app_conf('UPGRADE_ACTIONS_GROUP');
        $actions = explode(',', app_conf($upgradeGroup));

        if (!self::hitMaintainList($module, $action, $actions)) {
            return false;
        }

        if (APP == 'api' && self::apiIsH5($module, $action)) {
            self::initTemplate()->display("web/views/updating_h5.html");
            exit;
        }

        if (APP == 'task' || APP == 'api') {
            echo json_encode(self::initJson(app_conf('SYSTEM_PARTIAL_UPGRADE_APP_MSG')));
            exit;
        }

        if (APP == 'web' || APP == 'openapi') {
            self::initTemplate()->display("web/views/updating.html");
            exit;
        }
    }

    // 灾备环境
    public static function disasterCheck()
    {
        if (PHP_SAPI == 'cli' || !ENV_IN_DISASTER) {
            return false;
        }
        // 降级的服务
        $GLOBALS['sys_config']['VERIFY_SWITCH'] = 0;  // 阿里滑块降级
        $GLOBALS['sys_config']['RISK_SWITCHS'] = 0;  // 风控降级

        $actions = explode(',', app_conf('BEIJING_BACKUP_WHITELIST'));
        list($module, $action) = self::parseURI();

        if (self::hitMaintainList($module, $action, $actions)) {
            return false;
        }

        if (APP == 'api' && self::apiIsH5($module, $action)) {
            self::initTemplate()->display("web/views/updating_h5_suffer.html");
            exit;
        }

        if (APP == 'task' || APP == 'api') {
            echo json_encode(self::initJson());
            exit;
        }

        if (APP == 'web' || APP == 'openapi') {
            self::initTemplate()->display("web/views/updating_suffer.html");
            exit;
        }
        return true;
    }

    private static function parseURI()
    {
        if (!empty(self::$uriInfo)) {
            return self::$uriInfo;
        }

        $uriPath = explode('?', $_SERVER['REQUEST_URI']);
        $url = $uriPath[0];
        $ret = explode('/', trim($url, '/'));

        if (empty($ret[0])) {
            $ret[0] = 'index'; //首页$url为空
        }
        //适应有些url忽略index的情况
        if (empty($ret[1])) {
            $ret[1] = 'index';
        }
        // 转换为action实际命名
        $ret[1] = str_replace(' ', '', ucwords(str_replace('_', ' ', $ret[1])));
        self::$uriInfo = [$ret[0], $ret[1]];
        return self::$uriInfo;
    }

    private static function hitMaintainList($module, $action, $actionList)
    {
        if (in_array('*', $actionList) || in_array($module . '_*', $actionList)) {
            return true;
        }

        // 兼容Action首字母大小写问题
        foreach($actionList as $matchAction) {
            if (preg_match('/^' . $matchAction . '$/i', $module . '_' . $action )) {
                return true;
            }
        }

        return false;
    }

    private static function initTemplate()
    {
        if (!empty(self::$tpl)) {
            return self::$tpl;
        }

        $tpl = \libs\web\Open::getTemplateEngine();
        $tpl->asset = \SiteApp::init()->asset;
        $tpl->cache_dir = APP_RUNTIME_PATH . 'app/tpl_caches';
        $tpl->compile_dir = APP_RUNTIME_PATH . 'app/tpl_compiled';
        $tpl->template_dir = ROOT_PATH . 'app';
        $tpl->assign('host', APP_HOST);
        self::$tpl = $tpl;
        return $tpl;
    }

    private static function initJson($error = '')
    {
        $result = [];
        $result['errno'] = -1;
        $result['error'] = $error ? $error : '网站正在进行系统维护.期间将暂停服务,给您带来不便,敬请谅解!';
        $result['data'] = '';
        return $result;
    }

    private static function apiIsH5($module, $action)
    {
        $class = 'api\controllers\\' . $module . '\\' . $action;
        if (!class_exists($class)) {
            return false;
        }

        // 存管中转页面，特殊处理
        if ($module == 'payment' && $action == 'Transit') {
            return true;
        }

        $reflection = new \ReflectionClass($class);
        try {
            $isH5 = $reflection->getProperty('redirectWapUrl');
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

}
