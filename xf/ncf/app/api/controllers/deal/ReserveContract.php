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
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $data = $this->form->data;

        $this->json_data = array(
            'realName' => $userInfo['real_name'],
            'gender' => $userInfo['sex'] == 1 ? '男' : '女',
            'idno' => $userInfo['idno'],
            'year' => date('Y'),
            'month' => date('m'),
            'day' => date('d')
        );
    }
}
