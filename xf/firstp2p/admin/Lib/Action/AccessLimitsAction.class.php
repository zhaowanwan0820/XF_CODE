<?php
/**
 * Created by PhpStorm.
 * User: Yihui
 * Date: 2015/10/31
 * Time: 16:32
 */

FP::import("libs.common.site");
//FP::import("libs.utils.Block");

class AccessLimitsAction extends CommonAction {

    public function __construct() {
        parent::__construct();
    }

    public function index()
    {
        $userIds = \libs\utils\Block::getAllSpecialUsers();

        $userData = array();
        $result = MI('User')->where('id IN ('.implode(',', $userIds).')')->findAll();
        foreach ($result as $item) {
            $userData[$item['id']] = $item;
        }

        $concurrencyRules = \libs\utils\Block::getAllConcurrencyRules();
        $frequencyRules = \libs\utils\Block::getAllFrequencyRules();

        $this->assign('userIds', $userIds);
        $this->assign('userData', $userData);
        $this->assign('concurrencyRules', $concurrencyRules);
        $this->assign('frequencyRules', $frequencyRules);

        $this->display();
    }

    public function add_user() {
        $userId = addslashes(trim($_REQUEST['key']));
        if (!$userId) {
            $this->ajaxReturn("userId不可为空", "", 1);
        }
        if(\libs\utils\Block::addSpecialUser($userId) === false) {
            $this->ajaxReturn("操作失败", "", 1);
        } else {
            $this->ajaxReturn("操作成功", "", 1);
        }
    }

    public function delete_user() {
        $userId = addslashes(trim($_REQUEST['key']));
        if (!$userId) {
            $this->ajaxReturn("userId不可为空", "", 1);
        }
        if (\libs\utils\Block::delSpecialUser($userId) === false) {
            $this->ajaxReturn("操作失败", "", 1);
        } else {
            $this->ajaxReturn("操作成功", "", 1);
        }
    }

    public function query_user() {
        $userId = addslashes(trim($_REQUEST['key']));
        if (!$userId) {
            $this->ajaxReturn("userId不可为空", "", 1);
        }
        $value = \libs\utils\Block::isSpecialUser($userId);
        if (!$value) {
            $this->ajaxReturn("用户{$userId}不在限制名单中", "获取失败", 1);
        } else {
            $this->ajaxReturn("用户{$userId}在限制名单中", "获取成功", 1);
        }
    }

    public function add_concurrency() {
        $rule = addslashes(trim($_REQUEST['key']));
        $count = addslashes(trim($_REQUEST['value']));
        if (!$rule) {
            $this->ajaxReturn("限制并发的接口不可为空", "", 1);
        }
        \libs\utils\Block::addConcurrencyRule($rule, $count);
        $this->ajaxReturn("操作成功", "", 1);
    }

    public function delete_concurrency() {
        $rule = addslashes(trim($_REQUEST['key']));
        if (!$rule) {
            $this->ajaxReturn("限制并发的接口不可为空", "", 1);
        }
        \libs\utils\Block::delConcurrencyRule($rule);
        $this->ajaxReturn("操作成功", "", 1);
    }

    public function query_concurrency() {
        $rule = addslashes(trim($_REQUEST['key']));
        if (!$rule) {
            $this->ajaxReturn("限制并发的接口不可为空", "", 1);
        }
        $value = \libs\utils\Block::getConcurrencyRule($rule);
        if ($value === false || is_null($value)) {
            $this->ajaxReturn("{$rule}接口没有被限制", "", 1);
        } else {
            $this->ajaxReturn("{$rule}允许{$value}个并发访问", "获取成功", 1);
        }
    }


    public function add_frequency() {
        $rule = addslashes(trim($_REQUEST['key']));
        $count = addslashes(trim($_REQUEST['value']));

        if (!$rule) {
            $this->ajaxReturn("限制访问频率的接口不可为空", "", 1);
        }
        \libs\utils\Block::addFrequencyRule($rule, $count);
        $this->ajaxReturn("操作成功", "", 1);
    }

    public function delete_frequency() {
        $rule = addslashes(trim($_REQUEST['key']));
        if (!$rule) {
            $this->ajaxReturn("限制访问频率的接口不可为空", "", 1);
        }
        \libs\utils\Block::delFrequencyRule($rule);
        $this->ajaxReturn("操作成功", "", 1);
    }

    public function query_frequency() {
        $rule = addslashes(trim($_REQUEST['key']));
        if (!$rule) {
            $this->ajaxReturn("限制访问频率的接口不可为空", "", 1);
        }
        $value = \libs\utils\Block::getFrequencyRule($rule);
        if ($value === false || is_null($value)) {
            $this->ajaxReturn("{$rule}接口没有被限制", "", 1);
        } else {
            $this->ajaxReturn("{$rule}允许每分钟访问{$value}次", "获取成功", 1);
        }
    }

}
