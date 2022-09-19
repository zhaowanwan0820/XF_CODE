<?php
/**
 * 合同列表
 */
namespace api\controllers\darkmoon;

use libs\web\Form;
use core\service\darkmoon\ContractService;
use core\dao\darkmoon\DarkmoonDealModel;
use core\service\darkmoon\DarkMoonService;
use core\dao\darkmoon\DarkmoonDealLoadModel;

class ContractList extends ContractBaseAction {


    public function init() {
        parent::init();
        $this->localform = new Form();
        $this->localform->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'id' => array('filter' => 'required', 'message' => 'ERR_DEAL_NOT_EXIST'),
            'page' => array(
                        'filter' => 'int',
                        'message' => 'page must int',
                        'option' => array('optional' => true)
                ),
        );
        if (!$this->localform->validate()) {
            $this->setErr($this->localform->getErrorMsg());
            return false;
        }
    }

    public function invoke() {

        $data = $this->localform->data;

        $userId = $this->userInfo['id'];
        $dealId = $this->deal_id;

        $p = empty($data['page']) ?  1 : intval($data['page']);

        $dealModel = new DarkmoonDealModel();
        $dealInfo = $dealModel->getInfoById($dealId,'user_id');
        if (empty($dealInfo)){
            $this->setErr('ERR_DARKMOON_DEAL_NOT_EXIST');
            return false;
        }

        $contractService = new ContractService();
        // 投资人
        $role = 2;
        //  借款人
        if ($userId == $dealInfo['user_id']){
            $list = $contractService->getBorrowUserContratList($dealId,$userId,$p);
        }else{
            //  如果投资用户id 为空做更新
            $darkmoonService = new DarkMoonService();
            $rs = $darkmoonService->updateBatchUserId($userId,$this->userInfo['idno'],$dealId);
            if (empty($rs)){
                $this->setErr('ERR_DARKMOON_UDPATE_DEAL_LOAD_FAIL');
                return false;
            }

            $data_ret = $contractService->getContractList($dealId,true);
            $deal_load_model = new DarkmoonDealLoadModel();
            $deal_load_list = $deal_load_model->getByDealIdUserId($dealId,$userId);
            $deal_load_list_count = count($deal_load_list);
            $data_array = array();
            foreach($data_ret as $v) {
                foreach ($deal_load_list as $key => $deaLoadInfo) {
                    if ($deaLoadInfo['status'] != DarkmoonDealLoadModel::SIGN_WAIT_STATUS) continue;
                    $v['deal_load_id'] = $deaLoadInfo['id'];
                    $data_array[] = $v;
                }
            }
            $data_array = array_slice($data_array,($p-1)*10,10);
            $list['list'] = $data_array;
            $list['count']['num'] = count($data_ret)*$deal_load_list_count;
        }


        if (empty($list)){
            $this->setErr('ERR_DARKMOON_DEAL_NOT_EXIST');
            return false;
        }
        $url = "/exchange/contract_datail?id=$dealId";
        $ret['list'] = array();
        $ret['count'] = $list['count']['num'];
        foreach($list['list'] as $key => $v){
            $ret['list'][$key] = array(
                'nameSrc' => $v['title'],
                'name' => urlencode($v['title']),
                'url' => urlencode($url . '&tplId='.$v['id'].'&did='.$v['deal_load_id']),
            );
        }
        
        $this->json_data = $ret;


    }

}
