<?php
/**
 * 通过token获取用户信息
 * @author 王一鸣<wangyiming@ucfgroup.com>
 */

namespace api\controllers\user;

use libs\web\Form;
use api\controllers\AppBaseAction;

class Info extends AppBaseAction {
    public function init() {
        parent::init();
        $this->form = new Form("post");
        $this->form->rules = array(
            'token' => array('filter'=>'required', 'message'=> 'ERR_AUTH_FAIL'),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
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
        $bankcard = $this->rpc->local("UserBankcardService\getBankcard", array("user_id"=>$loginUser['id']));
        $cardVerifyStatus = 0;
        if (!empty($bankcard)) {
            $bank = $this->rpc->local("BankService\getBank", array('bank_id'=>$bankcard['bank_id']));
            $bank_no = !empty($bankcard['bankcard']) ? formatBankcard($bankcard['bankcard']) : '无';
            $bank_name = $bank['name'];
            $attachment = $this->rpc->local("AttachmentService\getAttachment", array('id' => $bank['img']));
            $bank_icon = empty($attachment['attachment']) ? "" : 'http:'.$GLOBALS['sys_config']['STATIC_HOST'].'/'.$attachment['attachment'];
            $bind_bank = 1;
            $cardVerifyStatus = $bankcard['verify_status'];
        } else {
            $bank_no = '无';
            $bind_bank = 0;
            $bank_name = '';
            $bank_icon = '';
        }

        if (!app_conf('BONUS_DISCOUNT_MOMENTS_DISABLE')) {
            $bonus = $this->rpc->local('BonusService\get_useable_money', array($loginUser['id']));
        } else {
            $bonus['money'] = 0;
        }

        $this->json_data = array(
            "uid" => $loginUser['id'],
            "username" => $loginUser['user_name'],
            "name" => $loginUser['real_name'] ? $loginUser['real_name'] : "无",
            "money" => number_format($loginUser['money'], 2),
            "idno" => $loginUser['idno'],
            "idcard_passed" => $loginUser['idcardpassed'],
            "photo_passed" => $loginUser['photo_passed'],
            "mobile" => !empty($loginUser['mobile']) ? moblieFormat($loginUser['mobile']) : '无',
            "email" => !empty($loginUser['email']) ? mailFormat($loginUser['email']) : '无',
            "bank_no" => $bank_no,
            "bind_bank" => $bind_bank,
            "bank" => $bank_name,
            "bank_icon" => $bank_icon,
            "cardVerifyStatus" => $cardVerifyStatus,
            'bonus' => format_price($bonus['money'], false),
            // BEGIN { 增加用户是否商家参数
            'isSeller' => $loginUser['isSeller'],
            'couponUrl' => $loginUser['couponurl'],
            'isO2oUser' => $loginUser['isO2oUser'],
            'showO2O' => $loginUser['showO2O'],
            // } END
        );
        return true;
    }
}
