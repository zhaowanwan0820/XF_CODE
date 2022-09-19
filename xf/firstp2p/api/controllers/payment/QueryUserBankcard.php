<?php

namespace api\controllers\payment;

use libs\web\Form;
use api\conf\ConstDefine;
use api\controllers\AppBaseAction;
use core\dao\DeliveryRegionModel;
use core\dao\BankModel;
use core\dao\AttachmentModel;

class QueryUserBankcard extends AppBaseAction {
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => '登录过期，重新登录'),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        //$loginUser = $this->rpc->local('UserService\getUser', array(666));
        //$GLOBALS['user_info'] = $loginUser;

        $data = array();
        $bankcard = $this->rpc->local("UserBankcardService\getBankcard", array("user_id"=>$loginUser['id']));
        if (!empty($bankcard) && !empty($bankcard['bankcard'])) {
            $regions = DeliveryRegionModel::instance()->getRegionsByLevel3($bankcard['region_lv3']);
            $bankInfo = BankModel::instance()->find($bankcard['bank_id']);
            $attachment = AttachmentModel::instance()->find($bankInfo['img']);
            $bankIcon = empty($attachment['attachment']) ? "" : 'http:'.$GLOBALS['sys_config']['STATIC_HOST'].'/'.$attachment['attachment'];
            $data['bankcard'] = $bankcard['bankcard'];
            $data['bank_id'] = $bankcard['bank_id'] > 0 ? $bankcard['bank_id'] : "";
            $data['bank_name'] = $bankInfo['name'];
            $data['bank_icon'] = $bankIcon;
            $data['card_name'] = $bankcard['card_name'];
            $data['region_lv1'] = 1;
            $data['region_lv1_name'] = '中国';
            if (!empty($regions)) {
                $data['region_lv2'] = $bankcard['region_lv2'];
                $data['region_lv2_name'] = $regions['name'];
                $data['region_lv3'] = $bankcard['region_lv3'];
                $data['region_lv3_name'] = $regions['sub_name'];
            }
            $data['bankzone'] = $bankcard['bankzone'];
        }

        $this->json_data = $data;
    }
}
