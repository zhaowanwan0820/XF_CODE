<?php

namespace openapi\controllers\deal;

use core\enum\ThirdpartyDkEnum;
use core\enum\JobsEnum;
use core\service\repay\P2pDealRepayService;
use core\service\thirdparty\ThirdpartyDkService;

use libs\utils\DBDes;
use libs\web\Form;
use libs\utils\Logger;
use openapi\controllers\BaseAction;
use openapi\lib\Tools;
use core\dao\thirdparty\ThirdpartyDkModel;
use core\dao\jobs\JobsModel;
use NCFGroup\Common\Library\Idworker;

class Transfer extends BaseAction {

    public function init(){
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'wx_open_id'   => ['filter' => 'required', 'message' => "wx_open_id is error"],
            'out_order_id' => ['filter' => 'required', 'message' => "out_order_id is error"],
            'money'        => ['filter' => 'required', 'message' => "money is error"],
            'notify_url'   => ['filter' => 'string', 'option' => array('optional' => true)],
            'user_name'    => ['filter' => 'required', 'message' => "user_name is error"],
            'id_no'        => ['filter' => 'required', 'message' => "id_no is error"],
            'bank_no'      => ['filter' => 'required', 'message' => "bank_no is error"],
            'mobile'       => ['filter' => 'required', 'message' => "mobile is error"],
        );

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if(!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke(){
        $data         = $this->form->data;
        $wxOpenId     = trim($data['wx_open_id']);
        $outerOrderId = trim($data['out_order_id']);
        $userName     = trim($data['user_name']);
        $idNo         = trim($data['id_no']);
        $bankNo       = trim($data['bank_no']);
        $mobile       = trim($data['mobile']);
        $money        = trim($data['money']);

        $thirdpartyDkService = new ThirdpartyDkService();
        $thirdpartyDkInfo =  $thirdpartyDkService->getThirdPartyByOutOrderId($outerOrderId,$data['client_id']);
        if (isset($thirdpartyDkInfo['outer_order_id'])) { // ??????????????? ???????????????
            // ????????????????????????????????????????????????????????????
            if($thirdpartyDkInfo['type'] != ThirdpartyDkEnum::SERVICE_TYPE_TRANSFER){
                $this->setErr('ERR_USED_ORDER');
                return false;
            }
            $outOrderParams = json_decode($thirdpartyDkInfo['params'],true);
            // ?????????????????????????????????????????????????????????_getDecodeData??????
            // ???????????????????????????????????????
            $outOrderParams = $this->_getDecodeData($outOrderParams,['certNo','bankCardNo','mobile','realName']);
            if($outOrderParams['certNo'] != $idNo || $outOrderParams['bankCardNo'] != $bankNo || $outOrderParams['money'] != $money
                 || $outOrderParams['mobile'] != $mobile || $outOrderParams['realName'] != $userName ){
                $this->setErr('ERR_USED_ORDER');
                return false;
            }

            $this->json_data = ['status' => $thirdpartyDkInfo['status']];
            return true;
        }

        $userId = Tools::getUserIdByOpenID($wxOpenId);
        try{
            $GLOBALS['db']->startTrans();
            $thirdParams = array(
                'realName'   => $userName,
                'certNo'     => $idNo,
                'bankCardNo' => $bankNo,
                'mobile'     => $mobile,
                'money'      => $money,
            ); //????????????

            $now = time();
            $orderId = Idworker::instance()->getId();
            $thirdpartyDkModel = new ThirdpartyDkModel();

            $thirdpartyDkModel->outer_order_id = $outerOrderId;
            $thirdpartyDkModel->order_id       = $orderId;
            $thirdpartyDkModel->client_id      = $data['client_id'];
            $thirdpartyDkModel->status         = ThirdpartyDkEnum::REQUEST_STATUS_WATTING;
            $thirdpartyDkModel->notify_url     = $data['notify_url'];
            $thirdpartyDkModel->create_time    = $now;
            $thirdpartyDkModel->update_time    = $now;
            $thirdpartyDkModel->type           = ThirdpartyDkEnum::SERVICE_TYPE_TRANSFER;
            $thirdpartyDkModel->params         = addslashes(json_encode(DBDes::encryptOrDecryptBySpecifiedFields(['realName', 'certNo', 'bankCardNo', 'mobile'],$thirdParams,true)));

            if ($thirdpartyDkModel->insert() === false) {
                throw new \Exception(sprintf('insert fail: %s'));
            }

            //????????????jobs
            $jobsModel = new JobsModel();
            $expireTime = date('YmdHis', time() + 3600);
            $jobParams = array_merge($thirdParams, [
                'orderId'    => $orderId,
                'userId'     => $userId,
                'dealId'     => 0,
                'repayId'    => 0,
                'money'      => $money,
                'expireTime' => $expireTime,
                'dk_type'    => ThirdpartyDkEnum::SERVICE_TYPE_TRANSFER,
            ]);
            $param = array('params' => $jobParams);
            $function = '\core\service\repay\P2pDealRepayService::dealMulticardDkRepayRequest';
            $jobsModel->priority = JobsEnum::PRIORITY_DEAL_REPAY;
            $jobsRes = $jobsModel->addJob($function, $param);
            if ($jobsRes === false) {
                throw new \Exception("??????jobs??????");
            }

            $GLOBALS['db']->commit();
        }catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"????????????", $e->getMessage(), json_encode($data))));
        }

        $this->json_data = ['status' => ThirdpartyDkEnum::REQUEST_STATUS_WATTING];
        return true;
    }


    /**
     * ???????????????????????????????????????????????????????????????
     * @param array $outOrderParams ????????????
     * @param array $fieldArr ???????????????
     * @return array ??????????????????
    */
    private function _getDecodeData($outOrderParams,$fieldArr){
        foreach($outOrderParams as $key => $value){
            // ????????????????????????????????????
            if(!in_array($key,$fieldArr)){
                continue;
            }
            $decode = DBDes::decryptOneValue($value);
            $outOrderParams[$key] = !empty($decode) ? $decode : $value;
        }
        return $outOrderParams;
    }
}
