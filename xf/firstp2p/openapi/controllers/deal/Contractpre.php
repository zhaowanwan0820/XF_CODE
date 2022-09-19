<?php

/**
 * 投资确认页 查看合同协议
 * @author xiaoan@ucfgroup.com
 * */

namespace openapi\controllers\deal;

use libs\web\Form;
use openapi\controllers\BaseAction;
use NCFGroup\Protos\Ptp\RequstDealContractpre;
use libs\utils\Aes;

class Contractpre extends BaseAction {

    const CONT_LOAN = 1; //借款合同
    const CONT_GUARANT = 4; //保证合同
    const CONT_LENDER = 5; //出借人平台服务协议
    const CONT_ASSETS = 7; //资产收益权回购通知
    const CONT_ENTRUST = 8; //委托协议

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            "oauth_token" => array("filter" => "required", "message" => "token不能为空"),
            'money' => array(
                'filter' => 'reg',
                'message' => '金额格式错误，小数点两位',
                'option' => array(
                    'regexp' => '/^\d+(\.\d{1,2})?$/',
                ),
            ),
            "type" => array("filter" => "int", "message" => "合同类型错误 - 改用作合同模板id"),
            "id" => array("filter" => "int", "message" => "id参数错误"),
            "ecid" => array("filter" => "string", "message" => "id参数错误"),
        );
        /*
         * 与父类系统鉴权验证规则合并
         */
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {

        $data = $this->form->data;

        if (isset($data['ecid']) && $data['ecid'] != "") {//有加密数据传入
            $data['id'] = Aes::decryptForDeal($data['ecid']);
        } else {
            $data['id'] = intval($data['id']);
        }

        if ($data['money'] < 0) {
            $this->setErr('ERR_CONTRACT_MONEY');
            return false;
        }

        if ($data['id'] <= 0) {
            $this->setErr('ERR_PARAMS_ERROR', "id参数错误");
            return false;
        }

        $loginUser = $this->getUserByAccessToken();
        if (!$loginUser) {
            $this->setErr('ERR_TOKEN_ERROR');
            return false;
        }

        $request = new RequstDealContractpre();
        $request->setId($data['id']);
        $request->setMoney($data['money']);
        $request->setType(intval($data['type']));
        $request->setUserId($loginUser->UserId);
        $response = $GLOBALS['rpc']->callByObject(array(
            'service' => 'NCFGroup\Ptp\services\PtpContract',
            'method' => 'contractPre',
            'args' => $request
        ));
        if ($response->resCode) {
            $errCode = 'ERR_MANUAL_REASON';
            switch ($response->errorCode) {
                case 23007:
                    $errCode = 'ERR_CONTRACT_TYPE';
                    break;
                case 23005:
                    $errCode = 'ERR_CONTRACT_EMPTY';
                    break;
                case 21003:
                    $errCode = 'ERR_DEAL_NOT_EXIST';
                    break;
                default:
                    break;
            }
            $this->setErr($errCode);
            return false;
        }
        $this->json_data = $response->toArray();
        return true;
    }

}
