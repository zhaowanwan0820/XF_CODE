<?php
/**
 * 网信房贷 编辑房产
 * @author sunxuefeng sunxuefeng@ucfgroup
 * @data 2017.9.28
 */

namespace api\controllers\house;

use libs\web\Form;
use api\controllers\AppBaseAction;

class EditHouse extends AppBaseAction {

    const IS_H5 = true;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_TOKEN_ERROR'),
            'id' => array('filter' => 'int', 'option' => array('optional' => true)),
            'selectedCity' => array('filter' => 'string', 'option' => array('optional' => true))
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
        $cities = $this->rpc->local('HouseService\getHouseConfCities', array(), 'house');

        $house = $this->rpc->local('HouseService\getHouse', array($data['id'], $data['token']), 'house');
        if (isset($data['selectedCity'])) {
            $selectedCity = $data['selectedCity'];
        } else {
            $selectedCity = $house['city'];
        }
        $districtList = $this->rpc->local('HouseService\getDistrictListByCity', array($selectedCity), 'house');

        // set value to view
        $this->tpl->assign('selectedCity', $selectedCity);
        $this->tpl->assign('districtList', $districtList);
        $this->tpl->assign('cities', $cities);
        $this->tpl->assign('house', $house);
        $this->tpl->assign('token', $data['token']);
        $this->template = $this->getTemplate('add_new_house_property');
    }
}
