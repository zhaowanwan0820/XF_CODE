<?php
/**
 * 短期标预约-提交预约页面的“委托合同和协议”
 *
 * @date 2016-11-17
 * @author guofeng@ucfgroup.com
 */

namespace api\controllers\deal;

use libs\web\Form;
use api\controllers\ReserveBaseAction;
use core\service\ReservationConfService;
use core\dao\ReservationConfModel;

class ReserveContract extends ReserveBaseAction {

    const IS_H5 = true;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array();
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);

        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        if (!$this->isOpenReserve()) {
            return false;
        }

        $userInfo = $this->getUserBaseInfo();
        $appLoginUrl = $this->getAppScheme('native', array('name'=>'login'));
        if (empty($userInfo))
        {
            // 登录token失效，跳到App登录页
            header('Location:' . $appLoginUrl);
            return false;
        }

        $data = $this->form->data;
        $this->tpl->assign('realName', $userInfo['real_name']);
        $this->tpl->assign('gender', ($userInfo['sex'] == 1 ? '男' : '女'));
        $this->tpl->assign('idno', $userInfo['idno']);
        $this->tpl->assign('year', date('Y'));
        $this->tpl->assign('month', date('m'));
        $this->tpl->assign('day', date('d'));
        return true;
    }
}