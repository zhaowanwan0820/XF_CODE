<?php
/**
 * 合同基类
 */
namespace api\controllers\darkmoon;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\dao\darkmoon\DarkmoonDealModel;
use core\dao\darkmoon\DarkmoonDealLoadModel;

class ContractBaseAction extends AppBaseAction {

    public $userInfo = '';
    public $deal_id = 0;
    public static $token = '';


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

        $this->checkDealUserContractStatus();
    }

    /**
     * 检查标 和用户是否合同签署状态
     */
    public function checkDealUserContractStatus(){

        $data = $this->form->data;
        $dealId = intval($data['id']);

        if (empty($dealId)){
            $this->setErr('ERR_PARAMS_ERROR');
            return false;
        }
        $this->deal_id = $dealId;
        $userInfo = $this->getUserByToken();
        if (empty($userInfo)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        self::$token = $this->form->data['token'];

        $this->userInfo = $userInfo;
        $userId = $userInfo['id'];
        $idno = $userInfo['idno'];

        $deal_model = new DarkmoonDealModel();

        $deal_info = $deal_model->getInfoById($dealId,'user_id,deal_status');
        if (empty($deal_info)){
            $this->setErr('ERR_DARKMOON_DEAL_NOT_EXIST');
            return false;
        }
        // 借款人
        if ($deal_info['user_id'] == $userId){
            switch($deal_info['deal_status']){
                case DarkmoonDealModel::DEAL_SIGNING_STATUS:
                    return true;
                    break;
                case DarkmoonDealModel::STATUS_DEAL_SIGNED:
                case DarkmoonDealModel::STATUS_DEAL_COMPLETE:
                case DarkmoonDealModel::STATUS_DEAL_DISUSE:
                case DarkmoonDealModel::DEAL_WATI_STATUS:
                    return false;
                    break;
            }
        }
        // 检查投资人是否签署
        $dark_moon_deal_load_model = new DarkmoonDealLoadModel();
        $deal_load_is_contract = $dark_moon_deal_load_model->isSignUserContract($idno, $dealId);
        if (empty($deal_load_is_contract)) {
            $this->setErr('ERR_DARKMOON_SIGNED');
            return false;
        }
    }

}
