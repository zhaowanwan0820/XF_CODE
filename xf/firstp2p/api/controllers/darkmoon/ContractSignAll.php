<?php

namespace api\controllers\darkmoon;

use libs\rpc\Rpc;
use libs\web\Form;
use api\conf\Error;
use core\dao\darkmoon\DarkmoonDealLoadModel;
use core\service\darkmoon\ContractService;
use libs\utils\Logger;
use core\dao\darkmoon\DarkmoonDealModel;
use core\service\darkmoon\DarkMoonService;
use libs\utils\Alarm;
use core\service\UserService;
/**
 * 签署合同
 */
class ContractSignAll extends ContractBaseAction {

    public function init() {
        parent::init();
        $this->localform = new Form();
        $this->localform->rules = array(
            'email' => array(
                'filter' => 'email',
                'message' => 'ERR_SIGNUP_PARAM_EMAIL',
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

        $dealId = $this->deal_id;
        $user = $this->userInfo;
        $userId = $user['id'];
        $email = $data['email'];

        if (empty($email)){
            $this->setErr('ERR_SIGNUP_PARAM_EMAIL');
            return false;
        }
        $retEmail = $this->updateEmailSub($userId,$user['email_sub'],$email);
        
        if ($retEmail === -1){
            $this->setErr('ERR_SIGNUP_EMAIL_UNIQUE');
            return false;
        }
        if ($retEmail == false){
            $this->setErr('ERR_DARKMOON_UPDATE_EMAIL_FAIL');
            return false;
        }

        // 查询标信息
        $dealModel = new DarkmoonDealModel();
        $dealInfo = $dealModel->getInfoById($dealId,'user_id');
        if (empty($dealInfo)){
            $this->setErr('ERR_DARKMOON_DEAL_NOT_EXIST');
            return false;
        }

        $darkmoonDealLoadModel = new DarkmoonDealLoadModel();
        if ($userId != $dealInfo['user_id']) {
            $dealloadInfo = $darkmoonDealLoadModel->getInfoByIdnoDealId($user['idno'], $dealId, 'id,user_id');
            if (empty($dealloadInfo)) {
                $this->setErr('ERR_DARKMOON_DEAL_NOT_EXIST');
                return false;
            }
        }
        $darkmoonService = new DarkMoonService();
        //  如果投资用户id 为空做更新
        if (empty($dealloadInfo['user_id']) && $userId != $dealInfo['user_id']){
            $ret = $darkmoonService->updateBatchUserId($user['id'],$user['idno'],$dealId);
            if (empty($ret)){
                $this->setErr('ERR_DARKMOON_UDPATE_DEAL_LOAD_FAIL');
                return false;
            }
        }

        // 签署合同
        $contractService = new ContractService();
        $is_borrow = 0;
        if ($userId == $dealInfo['user_id']){
            $is_borrow = 1;
            //获取未签署合同的投资记录,只有投资人签收完成之后，借款人在能签
            $count = $darkmoonDealLoadModel->getUnsignCount($dealId);
            if(intval($count) != 0){
                $this->setErr('ERR_DARKMOON_CANNOT_SIGNED');
                return false;
            }
            $ret = $contractService->sign($dealId,1,$dealInfo['user_id']);
        }else{
            $ret = $darkmoonService->sendConsumeUserSignContract($user['id'],$user['idno'],$dealId);
        }

        if (empty($ret)){
            $this->setErr('ERR_DARKMOON_UDPATE_DEAL_LOAD_FAIL');
            return false;
        }

        if ($is_borrow){
            $dealModel = new DarkmoonDealModel();
            $r = $dealModel->updateSignStatus($dealId);
            if (empty($r)) {
                Logger::error(__CLASS__ . ' ' . __FUNCTION__ . ' deal ' . json_encode($dealInfo) . ' uid ' . $user['id']);
                Alarm::push('DARKMOON','更新标签署状态失败',$dealId.' '.$userId .' ContractSignAll ');
            }
        }
        $this->json_data = 1;
    }

    /**
     * 更新邮箱
     * @param $id
     * @param $oldEmail
     * @param $email
     * @return bool|int
     */
    private function updateEmailSub($id,$oldEmail,$email){

        if ($oldEmail == $email){
            return true;
        }
        $isExist = $this->rpc->local('UserService\checkEmailSubExist', array($email));
        
        if($isExist){
            return -1;
        }
        $emailData = array('id' => $id, 'email_sub' => $email);
        if ($this->rpc->local('UserService\updateInfo', array($emailData))) {
            return true;
        } else {
            Alarm::push('DARKMOON','更新副邮箱失败',' '.$id .' '.$oldEmail.' '.$email);
            return false;
        }

        return false;
    }
}
