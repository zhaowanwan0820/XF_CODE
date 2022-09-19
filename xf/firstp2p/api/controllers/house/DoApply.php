<?php
/**
 * 网信房贷 申请
 * @author sunxuefeng sunxuefeng@ucfgroup
 * @data 2017.9.28
 */

namespace api\controllers\house;


use libs\web\Form;
use api\controllers\AppBaseAction;
use NCFGroup\Protos\Ptp\Enum\HouseEnum;

class DoApply extends AppBaseAction {
    /**
     * true：输出到模版
     * false：返回json结果(default)
     */
    // const IS_H5 = true;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_TOKEN_ERROR'),
            'borrow_money' => array('filter' => 'float', 'option' => array('optional' => true)),
            'borrow_deadline_type' => array('filter' => 'int', 'option' => array('optional' => true)),
            'payback_mode' => array('filter' => 'int', 'option' => array('optional' => true)),
            'house_id' => array('filter' => 'required', 'message' => 'house id is empty'),
            'annualized' => array('filter' => 'float', 'option' => array('optional' => true)),
            'usercard_front' => array('filter' => 'string', 'option' => array('optional' => true)),
            'usercard_back' => array('filter' => 'string', 'option' => array('optional' => true))
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
        // 房产信息中 正反面身份证信息
        $user_info = array(
            'usercard_front' =>$data['usercard_front'],
            'usercard_back' =>$data['usercard_back']
        );
        // other information
        $isNcfStaff = $this->rpc->local('HouseService\isNcfStaff', array($loginUser['group_id']), 'house');
        $isAgain = $this->rpc->local('HouseService\isAgainLoan', array($loginUser['id']), 'house');
        // 申请记录信息
        $apply_info = array(
            'user_id' => $loginUser['id'],
            'house_id' => $data['house_id'],
            'borrow_money' => $data['borrow_money'] * 10000,
            'borrow_deadline_type' => $data['borrow_deadline_type'],
            'payback_mode' => $data['payback_mode'],
            'create_time' => time(),
            'supplier' => HouseEnum::COOPERATION_YI_FANG,
            'status' => HouseEnum::STATUS_CHECKING,
            'expect_annualized' => $data['annualized'],
            'is_ncf_staff' => $isNcfStaff,
            'is_again' => $isAgain
        );
        $apply_info['update_time'] = $apply_info['create_time'];
        $other_info = array(
            'real_name' => $loginUser['real_name'],
            'phone' => $loginUser['mobile'],
            'usercard_id' => $loginUser['idno'],
            'is_ncf_staff' => $isNcfStaff
        );
        $commitInfo = array(
            'user_info' => $user_info,
            'apply_info' => $apply_info,
            'other_info' => $other_info,
            'token' => $data['token']
        );
        $result = $this->rpc->local('HouseService\commitApply', array($commitInfo), 'house');
        if ($result === false) {
            $error = $this->rpc->local('HouseService\getErrorMsg', array(), 'house');
            $this->setErr('ERR_MANUAL_REASON', $error);
            return false;
        }

        $this->json_data = $result ? $data['borrow_money'] : 0;
//        $this->tpl->assign('token', $data['token']);
//        $this->tpl->assign('result', $result ? $data['borrow_money'] : false);
//        $this->template = $this->getTemplate('loan_status');
    }
}
