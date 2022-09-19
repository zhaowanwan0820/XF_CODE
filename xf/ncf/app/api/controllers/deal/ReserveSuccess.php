<?php
/**
 * 短期标预约-预约成功页面
 *
 * @date 2016-11-18
 * @author guofeng@ucfgroup.com
 */

namespace api\controllers\deal;

use libs\web\Form;
use api\controllers\ReserveBaseAction;
use core\enum\ReserveEnum;

class ReserveSuccess extends ReserveBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'pid' => array('filter' => 'required', 'message' => 'pid is required'),
        );
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
        if (empty($userInfo))
        {
            // 登录token失效，跳到App登录页
            header('Location:' . $this->getAppScheme('native', array('name'=>'login')));
            return false;
        }
        $data = $this->form->data;

        $this->json_data = array(
            'token' => !empty($this->_userRedisInfo['token']) ? $this->_userRedisInfo['token'] : '',
            'userClientKey' => $data['userClientKey'],
        );

    }
}
