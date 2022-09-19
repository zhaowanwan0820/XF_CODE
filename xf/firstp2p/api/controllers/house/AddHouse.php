<?php
/**
 * 网信房贷 添加房产
 * @author sunxuefeng sunxuefeng@ucfgroup
 * @data 2017.9.28
 */

namespace api\controllers\house;

use libs\web\Form;
use api\controllers\AppBaseAction;

class AddHouse extends AppBaseAction {
    const IS_H5 = true;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_TOKEN_ERROR'),
            'selectedCity' => array('filter' => 'string', 'option' => array('optional' => true))
        );

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
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

//        $conf = $this->rpc->local('HouseService\getHouseConf', array(), 'house');
//        $cities = explode(';', $conf['city']);
//        $this->tpl->assign('citys', $cities);
        if (isset($data['selectedCity'])) {
            $this->tpl->assign('selectedCity', $data['selectedCity']);
            $districtList = $this->rpc->local('HouseService\getDistrictListByCity', array($data['selectedCity']), 'house');
            $this->tpl->assign('districtList', $districtList);
        }
        $this->tpl->assign('token', $data['token']);
        $this->template = $this->getTemplate('add_new_house_property');
    }
}
