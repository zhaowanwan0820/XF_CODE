<?php
/**
 * 合同列表
 */
namespace api\controllers\darkmoon;

use api\controllers\AppBaseAction;
use libs\web\Form;
use core\dao\darkmoon\DarkmoonDealLoadModel;
use core\dao\darkmoon\DarkmoonDealModel;

class CheckDealSign extends AppBaseAction {



    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'id' => array('filter' => 'required', 'message' => 'ERR_DEAL_NOT_EXIST'),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {

        $data = $this->form->data;

        $dealId = intval($data['id']);

        if (empty($dealId)){
            $this->setErr('ERR_PARAMS_ERROR');
            return false;
        }
        $userInfo = $this->getUserByToken();
        if (empty($userInfo)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $ret = array('is_darkmoon_contract' => 0);
        $deal_model = new DarkmoonDealModel();

        $deal_info = $deal_model->getInfoById($dealId,'user_id,deal_status');
        if (empty($deal_info)){
            $this->setErr('ERR_DARKMOON_DEAL_NOT_EXIST');
            return false;
        }
        // 借款人
        if ($deal_info['user_id'] == $userInfo['id']){

            switch($deal_info['deal_status']){
                case DarkmoonDealModel::DEAL_SIGNING_STATUS:
                    $ret['is_darkmoon_contract'] = 1;
                    break;
                case DarkmoonDealModel::STATUS_DEAL_SIGNED:
                    $ret['is_darkmoon_contract'] = 2;
                    break;
                case DarkmoonDealModel::STATUS_DEAL_COMPLETE:
                case DarkmoonDealModel::STATUS_DEAL_DISUSE:
                case DarkmoonDealModel::DEAL_WATI_STATUS:
                    $ret['is_darkmoon_contract'] = 0;
                    break;
            }
            $this->json_data = $ret;
            return ;

        }

        $darkmoonDealLoadModel = new DarkmoonDealLoadModel();
        $is_deal_sign = $darkmoonDealLoadModel->isSignUserContract($userInfo['idno'],$dealId);
        $ret['is_darkmoon_contract'] = empty($is_deal_sign) ? 0 : 1;
        if (!empty($is_deal_sign)){
            $this->json_data = $ret;
            return ;
        }

        // 检查是否已签署完毕
        $is_deal_sign = $darkmoonDealLoadModel->isContractSignComplete($userInfo['idno'],$dealId);
        if (!empty($is_deal_sign)){
            $ret['is_darkmoon_contract'] = 2;
            $this->json_data = $ret;
            return ;
        }

        $this->json_data = $ret;
        return ;
    }

}
