<?php

/**
 * Created by PhpStorm.
 * User: Devon
 * Date: 2015/12/18
 * Time: 17:52
 */

namespace iauth\components;

use iauth\controllers\AuthAssignmentController;
use iauth\controllers\UserController;
use iauth\helpers\Meta;

class IAuthController extends \DController
{
    //const ITEMID = array(566,567);
    public $isPost = false;
    public $isAjax = false;
    public $expectJson = false;
    public $pageSize = 20;

    /**
     * @var string 定位到 action 的唯一 ID。
     *          e.g. default/user/index
     */
    public $uniqueCode;

    public function init()
    {
        $this->isAjax = \Yii::app()->request->isAjaxRequest;
        $this->isPost = \Yii::app()->request->isPostRequest;
        $this->expectJson = $this->isAjax || strpos($_SERVER['HTTP_ACCEPT'], 'json') !== false;

        parent::init();
    }
    /**
     * 权限检查
     */
    public function checkRole()
    {
        //校验授权
        header("Content-Type:text/html;charset=utf-8");
        // $redis_key_hh = "huanhuanyiwu_youjieccs_status";
        // $api_status = \Yii::app()->rcache->get($redis_key_hh);
        // if(!in_array($api_status, ['on', 'off']) && $_SERVER['SERVER_ADDR'] != '127.0.0.1'){
        //     $auth_url = "https://shopapi.zichanhuayuan.com/hh/platform.auth?from=youjieccs";
        // 	$api_ret = \CurlService::getInstance()->AgRequest($auth_url);
        //     $redis_value = (isset($api_ret['data']['status']) && $api_ret['data']['status'] == 'false') ? "off" : "on";
        //     \Yii::app()->rcache->set($redis_key_hh, $redis_value, 7200);//缓存2小时授权状态
        // }
        //如果授权终止，暂停所有功能
        // $api_status = 'on';
        // if($api_status == 'off'){
        //     echo "<script>alert('授权已终止，目前系统无法使用')</script>";die;
        // }
        //判断登录排除
        if (!in_array($_SERVER["REQUEST_URI"], array('/default/index/loginccs','/default/index/index','/logout','/default/index/welcome','/'))) {
            //是否登录
            $userid = \Yii::app()->user->id;
            if (empty($userid)) {
                if ($this->isAjax === false) {
                    echo "<script>parent.location.reload();</script>";
                    echo "<script>parent.parent.location.reload();</script>";
                    exit;
                } else {
                    $this->echoJson([], 1, "登录超时，请重新登录！");
                }
            }
            //判断是否为超级管理员账号
            $userInfo  = \Yii::app()->user->getState('_user');
            if ($userInfo['username'] == \Yii::app()->iDbAuthManager->admin) {
                return true;
            }
            $defaultUrl = "/default/Index/welcome";//默认加载页面
            $authList = \Yii::app()->user->getState('_auth');
            $authList = $defaultUrl.$authList;
            $thisUrl = $_SERVER['REQUEST_URI'];
            if (strpos($thisUrl, '?')) {
                $thisUrl = substr($thisUrl, 0, strpos($thisUrl, '?'));//去掉传参
            }
            $actionsAdd = $this->checkAllowAction($thisUrl);
            if (!empty($actionsAdd)) {
                $authList = $authList.",".$actionsAdd;
            }
            if (strpos(strtolower($authList), trim(strtolower($thisUrl))) === false) {
                if (\Yii::app()->request->isAjax) {
                    $this->echoJson([], 1, "您没有此操作权限");
                }
                echo "<script>alert('您没有此操作权限')</script>";
                die;
            }
        }
    }

    /**
     * 查询控制器中allowActions
     */
    public function checkAllowAction($thisUrl)
    {
        $actionsAdd = '';
        if (strpos($thisUrl, "/") !== false) {
            $actionArr = explode("/", $thisUrl);
            if (strtolower($actionArr[1]) != "iauth") {
                $action = "\\".ucfirst($actionArr[2]."Controller");
                $object = new $action;
                if (method_exists($object, 'allowActions')) {
                    $actions = $object->allowActions();
                    if (!empty($actions)) {
                        foreach ($actions as $key => $val) {
                            $retAction[] = "/".$actionArr[1]."/".$actionArr[2]."/".$val;
                        }
                        $actionsAdd = implode(",", $retAction);
                    }
                }
            }
        }
        return $actionsAdd;
    }
    public function beforeAction($action)
    {
        //检验权限登录
        $this->checkRole();
        $this->uniqueCode = $this->getUniqueCode($action);
        return parent::beforeAction($action);
    }

    /**
     * @param $Input
     * @return array|string去掉数组两边空格
     */
    public function TrimArray($arr)
    {
        if (!is_array($arr)) {
            return $arr;
        }
        while (list($key, $value) = each($arr)) {
            if (is_array($value)) {
                $arr[$key] = TrimArray($value);
            } else {
                $arr[$key] = trim($value);
            }
        }
        return $arr;
    }
    /**
     * @param \CAction $action
     * @param \CController $controller
     * @return string
     */
    public function getUniqueCode($action, $controller = null)
    {
        if ($controller === null) {
            $controller = $this;
        }
        return sprintf('%s/%s', $controller->getUniqueId(), $action->id);
    }

    /**
     * 记录日志，附上所有 GET POST 参数与 $data
     * @var mixed $data 要记录的信息
     */
    protected function logReqParamsWith($data)
    {
        $msg = " Data " . print_r($data, true);
        $msg .= " Get " . print_r($_GET, true);
        $msg .= " Post " . print_r($_POST, true);
        \Yii::log($msg, \CLogger::LEVEL_WARNING, $this->uniqueCode);
    }

    /**
     * 不受权限系统控制的操作
     * 一般来说，有 2 种情况，
     *  1. 非操作： 即该  action 只是间接提供页面组成信息，如用户信息中的 系统列表json，用于 select 菜单
     *  2. 非页面： 即该  action 没有页面展示，如 ajax 检查用户名是否存在
     * 其余请额外添加注释说明原因。
     * @return array
     */
    public function allowActions()
    {
        return [];
    }

    public function allowActionsOnLogin()
    {
        return [];
    }

    /**
     * 不受权限系统控制的视图
     * 如部分操作需要双因子认证，但操作的render视图与处理逻辑在同一 action 内，
     * 同时又只希望只在 处理逻辑时 需要双因子认证，那么就可以添加到下列方法返回的数组中。
     */
    public function skipViews()
    {
        return [];
    }

    public function filters()
    {
        return ['iAuth'];
    }

    public function filterIAuth($filterChain)
    {
        $filter = new IAuthFilter();
        $filter->filter($filterChain);
    }

    /**
     * 输出 Json 数据
     * @param int $code
     * @param array $data
     */
    public function renderJson($code = Meta::C_SUCCESS, array $data = [])
    {
        $res = Meta::getMeta($code);
        $res['data'] = $data;

        header('Content-type: application/json');
        echo json_encode($res);
    }

    public function accessDenied($message = null)
    {
        if ($message === null) {
            $message = '403 forbidden';
        }

        if ($this->expectJson) {
            $this->renderJson($message);
        } else {
            $user = \Yii::app()->user;
            if ($user->getIsGuest()) {
                $user->loginRequired();
            } else {
                if (is_int($message)) {
                    $message = Meta::getCodeInfo($message);
                }
                throw new \CHttpException(403, $message);
            }
        }
    }
    /**
     * 模拟低版本array_column函数
     * @param $input
     * @param $columnKey
     * @param null $indexKey
     * @return array
     */
    public function array_column_low($input, $columnKey, $indexKey = null)
    {
        $columnKeyIsNumber = (is_numeric($columnKey)) ? true : false;
        $indexKeyIsNull = (is_null($indexKey)) ? true : false;
        $indexKeyIsNumber = (is_numeric($indexKey)) ? true : false;
        $result = array();

        foreach ((array)$input as $key => $row) {
            if ($columnKeyIsNumber) {
                $tmp = array_slice($row, $columnKey, 1);
                $tmp = (is_array($tmp) && !empty($tmp)) ? current($tmp) : null;
            } else {
                $tmp = isset($row[$columnKey]) ? $row[$columnKey] : null;
            }
            if (!$indexKeyIsNull) {
                if ($indexKeyIsNumber) {
                    $key = array_slice($row, $indexKey, 1);
                    $key = (is_array($key) && !empty($key)) ? current($key) : null;
                    $key = is_null($key) ? 0 : $key;
                } else {
                    $key = isset($row[$indexKey]) ? $row[$indexKey] : 0;
                }
            }

            $result[$key] = $tmp;
        }

        return $result;
    }

    /**
     * 脱敏
     */
    protected function strEncrypt($string, $start = 0, $length = 0, $replace = '*')
    {
        if (empty($string) || empty($length) || empty($replace)) {
            return $string;
        }
        $end = $start + $length;
        $str_length = iconv_strlen($string, "UTF-8");
        if ($str_length < $end) {
            return $string;
        }
        $returnStr = '';
        for ($x = 0; $x < $str_length; $x++) {
            if ($x >= $start && $x < $end) {
                $returnStr .= $replace;
            } else {
                $returnStr .= mb_substr($string, $x, 1, 'utf-8');
            }
        }
        return $returnStr;
    }

    public function setPagePlugin($countNum, $maxButtonCount=5)
    {
        //需要分页的
        $pages = new \CPagination($countNum);
        $pages->pageSize = $this->pageSize;
        return $this->widget('CLinkPager', [
            'header' => '',
            'firstPageLabel' => '首页',
            'lastPageLabel' => '末页',
            'prevPageLabel' => '上一页',
            'nextPageLabel' => '下一页',
            'pages' => $pages,
            'maxButtonCount' => $maxButtonCount,
            'cssFile' => false,
            'htmlOptions' => ['class' => 'pagination'],
            'selectedPageCssClass' => 'active',
        ], true);
    }



}
