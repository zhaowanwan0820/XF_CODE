<?php

namespace api\controllers\payment;

use libs\web\Form;
use api\conf\ConstDefine;
use core\service\BankService;
use api\controllers\AppBaseAction;

class AddBank extends AppBaseAction {
    public function init() {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => '登录过期，重新登录'),
            'type' => array('filter' => 'int', 'option' => array('optional' => true)),
            'bankcard' => array('filter' => 'required', 'message' => '银行卡号不能为空'), // 银行卡号
            'bank_id' => array('filter' => 'int', 'message' => '银行ID必须为数字'), // 所属银行ID
            'card_name' => array('filter' => 'string', 'option' => array('optional' => true)), // 用户名
            'region_lv1' => array('filter' => 'int', 'option' => array('optional' => true)),
            'region_lv2' => array('filter' => 'int'),
            'region_lv3' => array('filter' => 'int'),
            'bankzone' => array('filter' => 'required', 'message' => '支行信息不能为空'),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $bankService = new BankService;
        $data = $bankService->bankInfoXssFilter($data);

        if (empty($data['bankcard']) || empty($data['bank_id']) || empty($data['bankzone']) || empty($data['region_lv2']) || empty($data['region_lv3'])) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', '银行信息不能为空');
            return false;
        }
        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        //$loginUser = $this->rpc->local('UserService\getUser', array(666));
        //$GLOBALS['user_info'] = $loginUser;

        $len = strlen($data['bankcard']);
        if(!in_array($len, array(12,15,16,17,18,19))) {
         $this->setErr('ERR_MANUAL_REASON', "银行卡号长度不正确");
            return false;
        }
        $type = isset($data['type']) ? $data['type'] : 0;
        if ($type == 1) {
            $isnew = 1;
        } else {
            $bankcard = $this->rpc->local("UserBankcardService\getBankcard", array("user_id"=>$loginUser['id']));
            if (!empty($bankcard) && !empty($bankcard['bankcard'])) {
                $isnew = 0;
            } else {
                $isnew = 1;
            }
        }
        $ret = $this->rpc->local("BankService\canBankcardBind", array($data['bankcard'], $loginUser['id']));
        if ($ret === false) {
         $this->setErr('ERR_MANUAL_REASON', "该银行卡号已被其他用户绑定");
            return false;
        }

        $user_bank_card['bank_id'] = $data['bank_id'];
        $user_bank_card['bankcard'] = $data['bankcard'];
        $user_bank_card['bankzone'] = $data['bankzone'];
        $user_bank_card['user_id'] = $loginUser['id'];
        $user_bank_card['region_lv1'] = empty($data['region_lv1']) ? 1 : $data['region_lv1'];
        $user_bank_card['region_lv2'] = $data['region_lv2'];
        $user_bank_card['region_lv3'] = $data['region_lv3'];
        $user_bank_card['status'] = 1;
        $user_bank_card['card_name'] = empty($data['card_name']) ? $loginUser['real_name'] : $data['card_name'];

        $region_check = $this->rpc->local('DeliveryRegionService\checkRegions', array($user_bank_card['region_lv1'], $user_bank_card['region_lv2'], $user_bank_card['region_lv3']));
        if (!$region_check) {
            $this->setErr(0, "地区关系不正确！");
            return false;
        }
        $rs = $this->rpc->local('BankService\saveBank',array('data' => $user_bank_card, 'isnew' => $isnew));
        if ($rs) {
            $bankcard = $this->rpc->local("UserBankcardService\getBankcard", array("user_id"=>$loginUser['id']));
            if (!empty($bankcard)) {
                $bank = $this->rpc->local("BankService\getBank", array('bank_id'=>$bankcard['bank_id']));
                $bank_no = !empty($bankcard['bankcard']) ? formatBankcard($bankcard['bankcard']) : '无';
                $bank_name = $bank['name'];
                $attachment = $this->rpc->local("AttachmentService\getAttachment", array('id' => $bank['img']));
                $bank_icon = empty($attachment['attachment']) ? "" : 'http:'.$GLOBALS['sys_config']['STATIC_HOST'].'/'.$attachment['attachment'];
            } else {
                $bank_no = '无';
                $bank_name = '';
                $bank_icon = '';
            }
            $result = array("success" => ConstDefine::RESULT_SUCCESS);
            $result['bank'] = array(
                                'bank_no' => $bank_no,
                                'bank' => $bank_name,
                                'bank_icon' => $bank_icon,
                            );
            $result['isnew'] = $isnew;
            if ($isnew) {
                $result['msg'] = '银行卡信息设置成功';
            } else {
                $result['msg'] = '银行卡信息设置成功';
                //$result['msg'] = '网信理财将在3个工作日内完成信息审核。审核结果将以短信、站内信或电子邮件等方式通知您';
            }
        } else {
            $msg = '银行卡修改失败';
            $this->setErr('ERR_MANUAL_REASON', $msg);
            return false;
        }

        $this->json_data = $result;
    }
}
