<?php
/**
 * 查看合同内容
 *
 **/

namespace api\controllers\darkmoon;

use libs\web\Form;
use core\service\darkmoon\ContractService;
use core\dao\darkmoon\DarkmoonDealLoadModel;
use core\dao\darkmoon\DarkmoonDealModel;

class Contractpre extends ContractBaseAction {

    public function init() {

        parent::init();
        $this->localform = new Form();
        $this->localform->rules = array(
            "tplId" => array("filter"=>"int", "message"=>"合同参数错误"),
            "did" => array("filter"=>"int", "message"=>"参数错误"),
        );

        if (!$this->localform->validate()) {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {

        $data = $this->localform->data;

        $tpl_id = intval($data['tplId']);
        $userId = $this->userInfo['id'];
        $deal_load_id = intval($data['did']);
        if (empty($tpl_id)){
            $this->setErr('ERR_DARKMOON_DEAL_NOT_EXIST');
            return false;
        }

        // 查询标信息
        $dealModel = new DarkmoonDealModel();
        $dealInfo = $dealModel->getInfoById($this->deal_id,'user_id');
        if (empty($dealInfo)){
            $this->setErr('ERR_DARKMOON_DEAL_NOT_EXIST');
            return false;
        }
        $contract_service = new ContractService();
        if ($userId != $dealInfo['user_id']) {
            $darkmoonDealLoadModel = new DarkmoonDealLoadModel();
            $condition = 'id='.intval($deal_load_id);
            $dealloadInfo = $darkmoonDealLoadModel->findBy($condition,'deal_id,idno',array(),true);

            if (empty($dealloadInfo)) {
                $this->setErr('ERR_DARKMOON_DEAL_NOT_EXIST');
                return false;
            }
            if ($dealloadInfo['deal_id'] != $this->deal_id || $dealloadInfo['idno'] != $this->userInfo['idno']){
                $this->setErr('ERR_DARKMOON_DEAL_NOT_EXIST');
                return false;
            }
            $contract_info = $contract_service->viewContract($this->deal_id,$tpl_id,$deal_load_id);
        }else{
            $contract_info = $contract_service->getContract($tpl_id,$this->deal_id);
        }

        $this->json_data = $contract_info['content'];
    }
}
