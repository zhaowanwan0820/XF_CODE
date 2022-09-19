<?php
/**
 * 普惠实名认证提交
 *
 */
namespace web\controllers\account;

use web\controllers\BaseAction;
use core\enum\PaymentEnum;
use core\service\user\UserService;
use core\service\payment\PaymentService;
use core\service\risk\RiskServiceFactory;
use libs\utils\Risk;

class RegisterWithBank extends BaseAction {

    public function init() {
        return $this->check_login();
    }

    public function invoke() {
        if (!check_token()) {
            return $this->show_error($GLOBALS['lang']['TOKEN_ERR'], '', 0, 0, '/');
        }
        $user_info = $GLOBALS['user_info'];
        $data = $_POST;
        RiskServiceFactory::instance(Risk::BC_REAL_NAME_AUTH)->check($user_info, Risk::ASYNC, $data);
        $paymentService = new PaymentService();
        try {
            $data = $paymentService->filterXss($data);
        } catch (\Exception $e) {
            if (isset($data['isAjax']) && $data['isAjax'] == 1) {
                echo json_encode(array(
                    'errorCode' => -1,
                    'errorMsg' => $e->getMessage(),
                ));
                exit;
            } else {
                return $this->show_error($e->getMessage());
            }
        }

        $result = array();
        $result['status'] = PaymentEnum::REGISTER_SUCCESS;
        $result['msg'] = '注册成功';

        // 实名认证+超级账户开户
        UserService::doIdValidateRegister($user_info['id'], $data, true);
        if (UserService::hasError()) {
            $result['status'] = PaymentEnum::REGISTER_FAILURE;
            $result['msg'] = UserService::getErrorMsg();

            if (isset($data['isAjax']) && $data['isAjax'] == 1) {
                echo json_encode(array(
                    'errorCode' => -1,
                    'errorMsg' => $result['msg'],
                ));exit;
            } else {
                return $this->show_error($result['msg']);
            }
        }

        // 实名认证成功，跳转到账户首页
        RiskServiceFactory::instance(Risk::BC_REAL_NAME_AUTH)->notify();
        if (isset($data['isAjax']) && $data['isAjax'] == 1) {
            $formData = [
                'errorCode' => 0,
                'errorMsg' => '',
                'redirect' => '/account',
            ];
            echo json_encode($formData);
        } else {
            app_redirect('/account');
            return ;
        }
    }
}