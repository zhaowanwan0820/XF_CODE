<?php
/**
 * 网信房贷 保存用户房产信息
 * @author sunxuefeng sunxuefeng@ucfgroup
 * @data 2017.9.29
 */

namespace api\controllers\house;

use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\Logger;

class SaveHouse extends AppBaseAction
{

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_TOKEN_ERROR'),
            'house_city' => array('filter' => 'string', 'option' => array('optional' => true)),
            'house_district' => array('filter' => 'string', 'option' => array('optional' => true)),
            'house_address' => array('filter' => 'string', 'option' => array('optional' => true)),
            'house_deed_first' => array('filter' => 'string', 'option' => array('optional' => true)),
            'house_deed_second' => array('filter' => 'string', 'option' => array('optional' => true)),
            'id' => array('filter' => 'int', 'option' => array('optional' => true))
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

        $result = $this->rpc->local('HouseService\saveUserHouse', array($data, $loginUser['id']), 'house');
        if ($result === false) {
            $msg = $this->rpc->local('HouseService\getErrorMsg', array(), 'house');
            $this->setErr('ERR_MANUAL_REASON', $msg);
            return false;
        }

        $this->json_data = $result;
    }
}
