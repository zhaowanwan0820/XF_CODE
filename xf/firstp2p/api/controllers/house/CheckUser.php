<?php
/**
 * 网信房贷 个人信息填写
 * @author sunxuefeng sunxuefeng@ucfgroup
 * @data 2017.9.28
 */

namespace api\controllers\house;

use libs\web\Form;
use api\controllers\AppBaseAction;

class CheckUser extends AppBaseAction {

    const IS_H5 = true;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_TOKEN_ERROR'),
            'borrow_money' => array('filter' => 'float', 'option' => array('optional' => true)),
            'borrow_deadline_type' => array('filter' => 'int', 'option' => array('optional' => true)),
            'payback_mode' => array('filter' => 'int', 'option' => array('optional' => true)),
            'house_id' => array('filter' => 'int', 'option' => array('optional' => true)),
            'annualized' => array('filter' => 'float', 'option' => array('optional' => true))
        );

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL',$this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $houseUser = $this->rpc->local('HouseService\getUserInfo', array($loginUser['id'], $data['token']), 'house');
        $userInfo = array(
            'real_name' => $loginUser['real_name'],
            'phone' => $loginUser['mobile'],
            'usercard_id' => $loginUser['idno'],
            'usercard_front' => $houseUser['usercard_front'],
            'usercard_back' => $houseUser['usercard_back'],
            'usercard_front_id' => $houseUser['usercard_front_id'],
            'usercard_back_id' => $houseUser['usercard_back_id']
        );
        // 格式化手机号和身份证号码
        $userInfo['phone'] = $userInfo['phone'] ? format_mobile($userInfo['phone']) : $userInfo['phone'];
        $userInfo['usercard_id'] = $userInfo['usercard_id'] ?
            msubstr_replace($userInfo['usercard_id'], "*********", 6, 9) :
            $userInfo['usercard_id'];
        $this->tpl->assign('token', $data['token']);
        $this->tpl->assign('apply_info', $data);
        $this->tpl->assign('user_info', $userInfo);
        $this->template = $this->getTemplate('personal_information_filling');
    }
}
