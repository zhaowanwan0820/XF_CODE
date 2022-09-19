<?php
/**
 * 多投项目后台基类相关
 * User: wangzhen
 * Date: 2018/7/7 13:58
 */
use core\service\duotou\DuotouService;

class DtCommonAction extends CommonAction {

    protected function callByObject($object) {
        return DuotouService::callByObject($object);
    }
}