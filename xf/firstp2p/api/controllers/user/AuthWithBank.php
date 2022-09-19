<?php

namespace api\controllers\user;

use libs\web\Form;
use api\controllers\AppBaseAction;
use api\conf\ConstDefine;
use core\service\PaymentService;
use core\service\UserAccessLogService;
use NCFGroup\Protos\Ptp\Enum\UserAccessLogEnum;
use NCFGroup\Protos\Ptp\Enum\DeviceEnum;

/**
 *
 * 用户注册接口
 *
 * @uses BaseAction
 * @package
 * @version $id$
 * @copyright 1997-2005 The PHP Group
 * @author wangjiansong@ucfgroup.com
 * @license PHP Version 4 & 5 {@link http://www.php.net/license/3_01.txt}
 */
class AuthWithBank extends AppBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form('post');
        //$this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'name' => array('filter' => 'required'),
            'idno' => array('filter' => 'required'),
            'bankcard' => array('filter' => 'required'),
            'bank_id' => array('filter' => 'required'),
            'card_name' => array('filter' => 'required'),
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
        // 支付降级
        if (\libs\utils\PaymentApi::isServiceDown()) {
            $this->setErr('ERR_MANUAL_REASON', \libs\utils\PaymentApi::maintainMessage());
            return false;
        }
        //判断身份实名认证服务是否可用，如果服务不可用则直接返回相应的说明信息
        if (intval(app_conf("ID5_VALID")) === 3) {
            $msg = app_conf("ID5_MAINTEN_MSG");
            $this->setErr('ERR_MANUAL_REASON', $msg);
            return false;
        }
        $paymentService = new PaymentService();
        $bankInfo = array();
        $bankInfo['bankName'] = $data['bank_id'];
        $bankInfo['cardNo'] = trim($data['idno']);
        $bankInfo['realName'] = trim($data['name']);
        $bankInfo['cardName'] = trim($data['card_name']);
        $bankInfo['bankCardNo'] = trim($data['bankcard']);
        $bankInfo['source'] = 2;
        try {
            $bankInfo = $paymentService->filterXss($bankInfo);
            $result = $paymentService->combineRegister($loginUser['id'], $bankInfo);
            if ($result['status'] == PaymentService::REGISTER_SUCCESS) {
                //生产用户访问日志
                UserAccessLogService::produceLog($loginUser['id'], UserAccessLogEnum::TYPE_BIND_BANK_CARD, '实名绑卡成功', $bankInfo, '', UserAccessLogService::getDevice($_SERVER['HTTP_OS']));
                $ret = array('success' => '00', 'msg' => '注册成功');
            } else {
                $ret = array('success' => '01', 'msg' => $result['msg']);
            }
        } catch (\Exception $e) {
            $ret = array('success' => '01', 'msg' => $e->getMessage());
        }
        $this->json_data = $ret;
        return true;
    }

}
