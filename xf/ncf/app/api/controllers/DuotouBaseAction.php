<?php

namespace api\controllers;

use api\controllers\AppBaseAction;
use core\service\duotou\DuotouService;

/**
 * DuotouBaseAction
 *
 * @author zhaohui3@ucfgroup.com
 */
class DuotouBaseAction extends AppBaseAction {
    public $namespace_prefix = "core\service\\";
    protected $par_validate = true;
    protected $status = array(
            '1' => '申请中',
            '2' => '持有中',
            '3' => '转让/退出中',
            '4' => '已转让/退出',
            '5' => '已结清',
            '6' => '已取消',
    );

    public function _before_invoke() {
        if (!$this->dtInvoke()){
            return false;
        }
        return parent::_before_invoke();
    }

    /**
     * Invoke前进行相应的判断
     * @return boolean
     */
    public function dtInvoke() {
        if (!$this->par_validate) {
            return false;
        } elseif(app_conf('DUOTOU_SWITCH') == '0' ) {
            return $this->assignError("ERR_SYSTEM","系统维护中，请稍后再试！");
        } elseif (!is_duotou_inner_user()) {
            return $this->assignError("ERR_SYSTEM","没有权限");
        } else {
            return true;
        }
    }
    /**
     * 返回相应的提示错误
     * @param unknown $err
     * @return boolean
     */
    public function assignError($err, $error, $template = '') {
        $class = get_called_class();
        parent::setErr($err, $error);
    }

    /**
     * 重写setErr，防止错误弹出失败
     */
    public function setErr($err, $error = "") {
        return $this->assignError($err,$error);
    }

    /**
     * 判断是否是开放时间
     * @param time
     * @return boolean
     */
    public function isOpen($startTime, $endTime) {
        $time = time();
        if($time >= $startTime && $time <= $endTime) {
            return true;
        }
        return false;
    }

    protected function callByObject($object) {
        return DuotouService::callByObject($object);
    }


    protected function local($service_name, $args = array(), $module = '')
    {
        list($class_name, $func) = explode('\\', $service_name);
        if (empty($module)) {
            $class_name = $this->namespace_prefix.$class_name;
        } else {
            $class_name = $this->namespace_prefix.$module."\\".$class_name;
        }

        //如果有私有方法，优先调用私有方法，否则调用静态方法
        $classObj = new $class_name();
        if(method_exists($classObj,$func)){
            return call_user_func_array(array($classObj, $func), $args);
        }else{
            return call_user_func_array(array($class_name, $func), $args);
        }     
    }

}
