<?php

/**
 * Index.php
 *
 * @date 2014年4月8日14:52:33
 */

namespace web\controllers\account;

use web\controllers\BaseAction;
use core\dao\BankModel;
use libs\utils\Finance;
use core\service\PaymentService;
use core\service\UserVerifyService;
use core\service\risk\RiskService;
use core\service\UserAccessLogService;
use NCFGroup\Protos\Ptp\Enum\UserAccessLogEnum;
use NCFGroup\Protos\Ptp\Enum\DeviceEnum;

class RegisterWithBank extends BaseAction {
    public function init() {
        return $this->check_login();
    }

    public function invoke() {
        if (!check_token()) {
            return $this->show_error($GLOBALS['lang']['TOKEN_ERR'], '', 0, 0, '/');
        }

        $user_info = $GLOBALS ['user_info'];
        $data = $_POST;
        $paymentService = new PaymentService();
        try {
            $data = $paymentService->filterXss($data);
            $riskRet = RiskService::check('REALNAME', array(
                'idno'=>$data['cardNo']
            ));

            if ($riskRet !== true) {
                throw new \Exception('为了您的账户安全，请前往网信APP进行实名认证');
            }

            // 用户实名认证方式选择
            UserVerifyService::PcH5RealNameAuth($user_info['id'], $user_info['user_type'], $user_info['user_purpose']);
        } catch (\Exception $e) {
            if (isset($data['isAjax']) && $data['isAjax'] == 1) {
                echo json_encode(array(
                    'errorCode' => -1,
                    'errorMsg' => $e->getMessage(),
                ));
                exit;
            } else {
                if ($e->getCode() == -2)
                {
                    return $this->show_error($e->getMessage(), "请下载网信APP");
                }
                return $this->show_error($e->getMessage());
            }

        }

        $result = array();
        $result['status'] = PaymentService::REGISTER_SUCCESS;
        $result['msg'] = '注册成功';
        try {
            $result['status'] = $paymentService->register($GLOBALS['user_info']['id'], $data);
        }
        catch (\Exception $e)
        {
            $result['status'] = PaymentService::REGISTER_FAILURE;
            $result['msg'] = $paymentService->getLastError();
        }
        if ($result['status'] == PaymentService::REGISTER_SUCCESS) {
            //生产用户访问日志
            UserAccessLogService::produceLog($GLOBALS['user_info']['id'], UserAccessLogEnum::TYPE_REAL_NAME_AUTH, '实名认证成功', $data, '', DeviceEnum::DEVICE_WEB);

            // 实名认证通过，更新用户网信授权协议为已授权
            $this->rpc->local('UserService\signWxFreepayment', array($GLOBALS['user_info']['id']));

            if (isset($data['isAjax']) && $data['isAjax'] == 1)
            {
                $formData = [
                    'errorCode' => 0,
                    'errorMsg' => '',
                    'redirect' => '/account/registerSuccess',
                ];
                // TODO SupervisionMock
                if ($this->is_firstp2p) {
                    $formData['redirect'] = '/account/registerStandard';
                }
                echo json_encode($formData);
            }
            else
            {
                // TODO SupervisionMock
                if ($this->is_firstp2p) {
                    app_redirect('/account/registerStandard');
                    return ;
                }

                app_redirect('/account/registerSuccess');
                return ;


            }
        } else {
            if (isset($data['isAjax']) && $data['isAjax'] == 1) {
                echo json_encode(array(
                    'errorCode' => -1,
                    'errorMsg' => $paymentService->getLastError(),
                ));
            } else {
                return $this->show_error($result['msg']);
            }
        }
    }

}
