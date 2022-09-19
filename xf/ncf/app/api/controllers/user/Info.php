<?php
/**
 * 通过token获取用户信息
 * @author 王一鸣<wangyiming@ucfgroup.com>
 */

namespace api\controllers\user;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\user\UserService;

class Info extends AppBaseAction {
    public function init() {
        parent::init();

        $this->form = new Form("post");
        $this->form->rules = array(
            'token' => array(
                'filter'=>'required',
                'message'=> 'ERR_AUTH_FAIL'
            ),
        );

        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
        }
    }

    public function invoke() {
        $loginUser = $this->user;
        // 这里需要完善普惠的数据获取逻辑
        $result = UserService::userInfo($loginUser['id']);
        if ($result === false) {
            $this->setErr(UserService::getErrorData(), UserService::getErrorMsg());
        }

        $result['uid'] = $loginUser['id'];
        $result['username'] = $loginUser['user_name'];
        $result['name'] = $loginUser['real_name'] ? $loginUser['real_name'] : "无";
        $result['money'] = number_format($loginUser['money'], 2);
        $result['idno'] = $loginUser['idno'];
        $result['idcard_passed'] = $loginUser['idcardpassed'];
        $result['photo_passed'] = $loginUser['photo_passed'];
        $result['mobile'] = !empty($loginUser['mobile']) ? moblieFormat($loginUser['mobile']) : '无';
        $result['email'] = !empty($loginUser['email']) ? mailFormat($loginUser['email']) : '无';

        $this->json_data = $result;
    }
}
