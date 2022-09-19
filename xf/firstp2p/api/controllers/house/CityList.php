<?php
/**
 * 网信房贷 城市选择列表页面
 * @author sunxuefeng sunxuefeng@ucfgroup.com
 * @data 2017.11.04
 */

namespace api\controllers\house;

use libs\web\Form;
use api\controllers\AppBaseAction;

class CityList extends AppBaseAction {

    const IS_H5 = true;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_TOKEN_ERROR'),
            'selectedCity' => array('filter' => 'string', 'option' => array('optional' => true)),
            'id' => array('filter' => 'int', 'option' => array('optional' => true))
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
        $conf = $this->rpc->local('HouseService\getHouseConf', array(), 'house');
        foreach ($conf['cityList'] as $item) {
            $cities[] = $item['city'];
        }

        if (isset($data['selectedCity'])) {
            $this->tpl->assign('selectedCity', $data['selectedCity']);
        }
        $this->tpl->assign('token', $data['token']);
        $this->tpl->assign('id', $data['id']);
        $this->tpl->assign('cities', $cities);
        $this->template = $this->getTemplate('loan_city');
    }
}
