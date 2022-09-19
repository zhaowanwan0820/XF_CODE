<?php
/**
 * 网信房贷 配置项获取
 * @author sunxuefeng sunxuefeng@ucfgroup
 * @data 2017.9.28
 */

namespace api\controllers\house;

use libs\web\Form;
use api\controllers\AppBaseAction;
use NCFGroup\Protos\Ptp\Enum\HouseEnum;
use libs\utils\Logger;

class PreApply extends AppBaseAction
{

    const IS_H5 = true;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_TOKEN_ERROR'),
            'house_id' => array('filter' => 'int', 'option' => array('optional' => true)),
            'agree' => array('filter' => 'int', 'option' => array('optional' => true)),
            'date_number' => array('filter' => 'string', 'option' => array('optional' => true)),
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
        // new house user, update user status
        if (!empty($data['agree'])) {
            $result = $this->rpc->local('HouseService\updateUserStatus', array($loginUser['id']), 'house');
            if (empty($result)) {
                $this->setErr('ERR_MANUAL_REASON', '更新用户状态失败');
                return false;
            }
        }

        // get config by service
        $conf = $this->rpc->local('HouseService\getHouseConf', array(), 'house');
        $conf['downMoney'] = $conf['downMoney'] / 10000;
        $conf['upMoney'] = $conf['upMoney'] / 10000;
        $conf['paybackModeInfo'] = HouseEnum::$REPAYMENT_MODES[$conf['paybackModes']];

        if (isset($data['selectedCity'])) {
            $selectCityConf = $this->rpc->local('HouseService\getHouseConfByCity', array($data['selectedCity']), 'house');
            $conf['annualized'] = $selectCityConf['annualized'];
            $conf['annualizedDesc'] = $selectCityConf['annualized'];
        } else {
            $conf['annualized'] = $this->rpc->local('HouseService\getHouseConfAnnualizedLimit', array(), 'house');
            $conf['annualizedDesc'] = '';
        }
        // get house information by house id
        $house = array();
        if (!empty($data['house_id'])) {
            $house = $this->rpc->local('HouseService\getHouse', array($data['house_id'], $data['token']), 'house');
        }

        $this->tpl->assign('selectedCity', isset($data['selectedCity']) ? $data['selectedCity'] : '');
        $this->tpl->assign('house', $house);
        $this->tpl->assign('date_number', isset($data['date_number']) ? $data['date_number'] : '');
        $this->tpl->assign('token', $data['token']);
        $this->tpl->assign('conf', $conf);

        $this->template = $this->getTemplate('net_loan');
    }
}
