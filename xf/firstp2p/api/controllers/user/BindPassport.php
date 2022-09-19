<?php
/**
 * 通行证绑定
 * @author longbo
 */

namespace api\controllers\user;

use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\Block;
use core\service\LogRegLoginService;
use libs\utils\Logger;
use libs\utils\PaymentApi;
use core\service\risk\RiskServiceFactory;
use libs\utils\Risk;
use libs\utils\Monitor;

class BindPassport extends AppBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form("post");
        $this->form->rules = array(
            "ppID" => array("filter" => "required", "message" => 'PPID is required'),
            "mobile" => array("filter" => "string"),
            "verify" => array("filter" => "string"),
        );

        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $ppID = $data['ppID'];
        $needVerify = \SiteApp::init()->cache->get("passport_need_verify_" . $ppID);
        if ($needVerify) {
            if (empty($data['verify'])) {
                $this->setErr('ERR_VERIFY_EMPTY');
                return false;
            }
            if(empty($data['mobile'])){
                $this->setErr('ERR_PARAMS_ERROR','请输入手机号');
                return false;
            }
            $vcode = $this->rpc->local('MobileCodeService\getMobilePhoneTimeVcode', array($data['mobile'], 180, 0));
            if($vcode != $data['verify']) {
                $this->setErr('ERR_VERIFY_ILLEGAL');
                return false;
            } else {
                $this->rpc->local('MobileCodeService\delMobileCode', array($data['mobile'], 0));
            }
        }

        $user = $this->rpc->local("UserService\getUserByPPID", array($ppID));
        if (empty($user)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $token = $this->rpc->local("UserTokenService\genAppToken", array($user['id'], $ppID));
        $bankcard = $this->rpc->local("UserBankcardService\getBankcard", array("user_id" => $user['id']));
        if (!empty($bankcard)) {
            $bank = $this->rpc->local("BankService\getBank", array('bank_id' => $bankcard['bank_id']));
            $bank_no = !empty($bankcard['bankcard']) ? formatBankcard($bankcard['bankcard']) : '无';
            $bank_name = $bank['name'];
            $attachment = $this->rpc->local("AttachmentService\getAttachment", array('id' => $bank['img']));
            $bank_icon = empty($attachment['attachment']) ? "" : 'http:'.$GLOBALS['sys_config']['STATIC_HOST'].'/'.$attachment['attachment'];
            $bind_bank = 1;
        } else {
            $bank_no = '无';
            $bank_name = '';
            $bank_icon = '';
            $bind_bank = 0;
        }

        // 记录日志
        $os = isset($_SERVER['HTTP_OS']) ? $_SERVER['HTTP_OS'] : "iOS";
        $channel = isset($_SERVER['HTTP_CHANNEL']) ? $_SERVER['HTTP_CHANNEL'] : 'NO_CHANNEL';
        $apiLog = array(
            'time' => date('Y-m-d H:i:s'),
            'userId' => $user['id'],
            'ip' => get_real_ip(),
            'os' => $os,
            'channel' => $channel,
        );
        logger::wLog("API_BINDPASSPORT_LOGIN:".json_encode($apiLog));
        PaymentApi::log("API_BINDPASSPORT_LOGIN:".json_encode($apiLog), Logger::INFO);

        $bonus = $this->rpc->local('BonusService\get_useable_money', array($user['id']));
        $this->json_data = array(
            "token" => $token,
            "uid" => $user['id'],
            "username" => $user['user_name'],
            "name" => $user['real_name'] ? $user['real_name'] : "无",
            "money" => number_format($user['money'], 2),
            "idno" => $user['idno'],
            "idcard_passed" => $user['idcardpassed'],
            "photo_passed" => $user['photo_passed'],
            "mobile" => !empty($user['mobile']) ? moblieFormat($user['mobile']) : '无',
            "email" => !empty($user['email']) ? mailFormat($user['email']) : '无',
            "bank_no" => $bank_no,
            "bank" => $bank_name,
            "bank_icon" => $bank_icon,
            'bonus' => format_price($bonus['money'], false),
            'force_new_password' => 0,
            'isSeller' => $user['isSeller'],
            'couponUrl' => $user['couponUrl'],
            'isO2oUser' => $user['isO2oUser'],
            'showO2O' => $user['showO2O'],
            'bind_bank' => $bind_bank,
            'ppID' => $ppID,
        );
        return true;
    }

}
