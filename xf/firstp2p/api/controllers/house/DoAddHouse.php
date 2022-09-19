<?php
/**
 * 网信房贷 添加用户房产信息
 * @author sunxuefeng sunxuefeng@ucfgroup
 * @data 2017.9.30
 */

namespace api\controllers\house;

use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\Logger;

class DoAddHouse extends AppBaseAction {

    const IS_H5 = true;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_TOKEN_ERROR'),
            'house_city' => array('filter' => 'string', 'option' => array('optional' => true)),
            'house_district' => array('filter' => 'string', 'option' => array('optional' => true)),
            'house_address' => array('filter' => 'string', 'option' => array('optional' => true)),
            'house_deed_first' => array('filter' => 'string', 'option' => array('optional' => true)),
            'house_deed_second' => array('filter' => 'string', 'option' => array('optional' => true))
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

        // test data
        $user['id'] = 1001;
        $data['house_city'] = '北京';
        $data['house_district'] = '朝阳区';
        $data['house_address'] = '霄云路30号院1号楼9单元101';
        $data['house_deed_first'] = 'house_deed_first1';
        $data['house_deed_second'] = 'house_deed_second2';

        $result = $this->rpc->local('HouseService\addUserHouse', array($data, $loginUser['id']), 'house');
        $this->json_data = $result;
    }
}
