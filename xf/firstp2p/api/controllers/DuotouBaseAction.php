<?php

namespace api\controllers;

use api\controllers\NcfphRedirect;

/**
 * DuotouBaseAction
 *
 * @author zhaohui3@ucfgroup.com
 */

class DuotouBaseAction extends NcfphRedirect {
    protected $par_validate = true;
    protected $status = array(
            '1' => '申请中',
            '2' => '持有中',
            '3' => '转让/退出中',
            '4' => '已转让/退出',
            '5' => '已结清',
            '6' => '已取消',
    );

    /*
    public function _before_invoke() {
        if(!isset($_REQUEST['is_allow_access']) || intval($_REQUEST['is_allow_access']) != 1){
            return $this->assignError("ERR_SYSTEM","系统维护中，请稍后再试！");
        }
        return parent::_before_invoke();
    }
    */

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
    public function assignError($err,$error,$template = '') {
        $class = get_called_class();
        parent::setErr($err,$error);
        if ($this->isWapCall()) {
            return;
        }
        if ($class::IS_H5) {
            $this->tpl->assign("error", $this->error);
            if ($template) {
                $this->template = $template;
            }
        }
        return false;
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
//
//    /**
//     * 计算在投天数
//     * @param time
//     * @return int
//     */
//    public function getInvestDay($startTime, $endTime) {
//        $createTime = strtotime(date('Y-m-d 00:00:00',$startTime));
//        $currentTime = strtotime(date('Y-m-d 00:00:00',$endTime));
//        $ownDay = floor(abs($currentTime-$createTime)/86400) - 1;
//        return $ownDay < 0 ? 0 : $ownDay;
//    }

}
