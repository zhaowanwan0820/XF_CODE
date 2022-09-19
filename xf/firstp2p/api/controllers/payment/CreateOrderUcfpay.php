<?php

/**
 * 新协议支付逻辑-接口-APP
 *
 * @author 郭峰<guofeng3@ucfgroup.com>
 */

namespace api\controllers\payment;

use libs\web\Form;
use api\conf\ConstDefine;
use api\controllers\AppBaseAction;
use core\service\ChargeService;
use core\service\UserBankcardService;
use core\dao\PaymentNoticeModel;
use libs\utils\PaymentApi;

class CreateOrderUcfpay extends AppBaseAction {

    public function init() {
        parent::init();
        if (app_conf('MAINTENANCE_APP_PAYMENT_OFF_SWITCH') === '1') {
            $this->setErr(ERR_SYSTEM, app_conf('MAINTENANCE_APP_PAYMENT_OFF'));
            return false;
        }
        $this->form = new Form('post');
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'money' => array('filter' => 'int'),
            'bankCardId' => array('filter' => 'string', 'option' => array('optional' => true)),
            'os' => array('filter' => 'string', 'option' => array('optional' => true)),
            'ver' => array('filter' => 'string', 'option' => array('optional' => true)),
            'return_url' => array('filter' => 'string', 'option' => array('optional' => true)),
            'mobileType' => array('filter' => 'string', 'option' => array('optional' => true)),
            'show_nav' => array('filter' => 'string', 'option' => array('optional' => true)),
            'platform' => array('filter' => 'int', 'option' => array('optional' => true)),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR');
            return false;
        }
    }

    public function invoke() {
        $version = $this->getAppVersion();
        if ($version >= ConstDefine::VERSION_MULTI_CARD) {
            return $this->invokeNew();
        }
        return $this->invokeOld();
    }

    public function invokeNew()
    {
        try {
            $userInfo = $this->getUserByToken();
            if (empty($userInfo))
            {
                throw new \Exception('ERR_GET_USER_FAIL');
            }
            // 检查用户是否已在先锋支付开户
            if ($userInfo['payment_user_id'] <= 0)
            {
                throw new \Exception('ERR_MANUAL_REASON|您尚未开户无法进行充值，请稍后再试');
            }

            $data = $this->form->data;
            if (!isset($data['money']) || empty($data['money']))
            {
                throw new \Exception('ERR_MANUAL_REASON|请填写充值金额');
            }

            // 新版app，如果没传或传默认值则表示先锋支付接口挂了
            if (empty($data['bankCardId']) || $data['bankCardId'] == UserBankcardService::BANK_CARDID_DEFAULT) {
                throw new \Exception('ERR_SYSTEM|' . app_conf('MAINTENANCE_APP_PAYMENT_OFF'));
            }

            // 充值金额，单位分
            $amountCent = intval($data['money']);
            // 充值金额，单位元
            $amountYuan = bcdiv($amountCent, 100, 2);
            // 限制最少金额为1元
            if(bccomp($amountCent, 100, 2) < 0)
            {
                throw new \Exception('ERR_PARAMS_VERIFY_FAIL|充值金额最低1元');
            }
            // 限制最大金额为99999999元
            if(bccomp($amountCent, 9999999900, 2) > 0)
            {
                throw new \Exception('ERR_PARAMS_VERIFY_FAIL|单笔订单金额不能超过99999999元');
            }

            // 快捷限额判断
            $chargeService = new ChargeService();
            $limitRule = $chargeService->getLimitRuleByBankCardId($userInfo['id'], $data['bankCardId'], ChargeService::LIMIT_TYPE_NEWH5);
            do {
                // 没有限制记录当作不限额
                if (empty($limitRule)) {
                    break;
                }
                // 只有限额记录存在并且金额不为0的时候, 判断单笔是否超限额
                if (isset($limitRule['singlelimit']) && $limitRule['singlelimit'] >= 0 && $amountCent > $limitRule['singlelimit']) {
                    throw new \Exception('ERR_MANUAL_REASON|充值金额超过单笔限额,请重新输入充值金额');
                }
                // 当最小限额存在并且不为空时, 判断充值金额是否小于单笔最小限额
                if (isset($limitRule['lowlimit']) && $limitRule['lowlimit'] >= 0 && $amountCent < $limitRule['lowlimit']) {
                    throw new \Exception('ERR_MANUAL_REASON|充值金额低于单笔最小金额,请重新输入充值金额');
                }
            } while (false);

            $userType = '00';
            // 获取用户的身份证类型信息
            $idInfo = $this->rpc->local('UserService\getIdnoAndType', array($userInfo['id']));
            if(is_array($idInfo))
            {
                if ($idInfo['id_type'] == 1 && strlen($idInfo['idno']) == 18)
                {
                    $userType = '01';
                } elseif ($idInfo['id_type'] == 2)
                {
                    $userType = '04';
                } elseif ($idInfo['id_type'] == 3)
                {
                    $userType = '03';
                } elseif ($idInfo['id_type'] >= 4 && $idInfo['id_type'] <= 6)
                {
                    $userType = '02';
                } elseif ($idInfo['id_type'] == 99 )
                {
                    $userType = '99';
                }
            }
            if ($userType != '01')
            {
                throw new \Exception('ERR_MANUAL_REASON|手机充值只支持使用二代身份证验证的用户，如有疑问请致电95782');
            }

            // 获取当前手机操作系统
            $os = isset($_SERVER['HTTP_OS']) ? $_SERVER['HTTP_OS'] : "iOS";
            $osId = $os === 'Android' ? PaymentNoticeModel::PLATFORM_ANDROID : PaymentNoticeModel::PLATFORM_IOS;
            // 创建订单
            $chargeService = new ChargeService();
            $orderSn = $chargeService->createOrder($userInfo['id'], $amountYuan, PaymentNoticeModel::PLATFORM_H5_NEW_CHARGE);
            if ($orderSn <= 0)
            {
                throw new \Exception('ERR_SYSTEM|创建订单失败！');
            }
            $noticeSn = '';
            do {
                // 根据订单自增ID，获取该订单的数据
                $paymentNoticeModel = new PaymentNoticeModel();
                $paymentNotice = $paymentNoticeModel->find($orderSn);
                // 订单编号
                $noticeSn = $paymentNotice['notice_sn'];
                if (!empty($noticeSn))
                {
                    break;
                }
                usleep(200);
            } while (empty($noticeSn));
            if (empty($noticeSn))
            {
                throw new \Exception('ERR_SYSTEM|创建订单失败！');
            }

            // 生成[先锋支付]的form表单
            $params = [];
            $params['userId'] = $userInfo['id'];
            $params['outOrderId'] = $noticeSn;
            $params['amount'] = $amountCent;
            $params['hasTitle'] = 'Y';
            $params['mobileType'] = isset($data['mobileType']) ? $data['mobileType'] : ($osId == PaymentNoticeModel::PLATFORM_ANDROID ? '12' : '11');
            $params['returnUrl'] = isset($data['return_url']) ? trim($data['return_url']) : 'storemanager://api?type=recharge';
            // 银行卡唯一标识
            $params['bankCardId'] = addslashes($data['bankCardId']);

            $result = array();
            if (!empty($data['mobileType'])) {
                $formString = PaymentApi::instance()->getGateway()->getForm('newH5Charge', $params, 'h5chargeForm', false);
                $result['formId'] = 'h5chargeForm';
                $result['form'] = $formString;
            } else {
                $requestUrl = PaymentApi::instance()->getGateway()->getRequestUrl('newH5Charge', $params, 'h5chargeForm', false);
                $result['url'] = $requestUrl;
            }
            $this->json_data = $result;
            return true;
        } catch (\Exception $e) {
            $exceptionInfo = explode('|', $e->getMessage());
            $this->setErr((isset($exceptionInfo[0]) ? $exceptionInfo[0] :'ERR_SYSTEM'), (isset($exceptionInfo[1]) ? $exceptionInfo[1] :''));
            return false;
        }
    }

    public function invokeOld()
    {
        try {
            $userInfo = $this->getUserByToken();
            if (empty($userInfo))
            {
                throw new \Exception('ERR_GET_USER_FAIL');
            }
            // 检查用户是否已在先锋支付开户
            if ($userInfo['payment_user_id'] <= 0)
            {
                throw new \Exception('ERR_MANUAL_REASON|您尚未开户无法进行充值，请稍后再试');
            }

            $data = $this->form->data;
            if (!isset($data['money']) || empty($data['money']))
            {
                throw new \Exception('ERR_MANUAL_REASON|请填写充值金额');
            }

            // 充值金额，单位分
            $amountCent = intval($data['money']);
            // 充值金额，单位元
            $amountYuan = bcdiv($amountCent, 100, 2);
            // 限制最少金额为1元
            if(bccomp($amountCent, 100, 2) < 0)
            {
                throw new \Exception('ERR_PARAMS_VERIFY_FAIL|充值金额最低1元');
            }
            // 限制最大金额为99999999元
            if(bccomp($amountCent, 9999999900, 2) > 0)
            {
                throw new \Exception('ERR_PARAMS_VERIFY_FAIL|单笔订单金额不能超过99999999元');
            }

            $userType = '00';
            // 获取用户的身份证类型信息
            $idInfo = $this->rpc->local('UserService\getIdnoAndType', array($userInfo['id']));
            if(is_array($idInfo))
            {
                if ($idInfo['id_type'] == 1 && strlen($idInfo['idno']) == 18)
                {
                    $userType = '01';
                } elseif ($idInfo['id_type'] == 2)
                {
                    $userType = '04';
                } elseif ($idInfo['id_type'] == 3)
                {
                    $userType = '03';
                } elseif ($idInfo['id_type'] >= 4 && $idInfo['id_type'] <= 6)
                {
                    $userType = '02';
                } elseif ($idInfo['id_type'] == 99 )
                {
                    $userType = '99';
                }
            }
            if ($userType != '01')
            {
                throw new \Exception('ERR_MANUAL_REASON|手机充值只支持使用二代身份证验证的用户，如有疑问请致电95782');
            }

            // 获取当前手机操作系统
            $os = isset($_SERVER['HTTP_OS']) ? $_SERVER['HTTP_OS'] : "iOS";
            $osId = $os === 'Android' ? PaymentNoticeModel::PLATFORM_ANDROID : PaymentNoticeModel::PLATFORM_IOS;
            // 创建订单
            $chargeService = new ChargeService();
            $orderSn = $chargeService->createOrder($userInfo['id'], $amountYuan, PaymentNoticeModel::PLATFORM_H5_NEW_CHARGE);
            if ($orderSn <= 0)
            {
                throw new \Exception('ERR_SYSTEM|创建订单失败！');
            }
            $noticeSn = '';
            do {
                // 根据订单自增ID，获取该订单的数据
                $paymentNoticeModel = new PaymentNoticeModel();
                $paymentNotice = $paymentNoticeModel->find($orderSn);
                // 订单编号
                $noticeSn = $paymentNotice['notice_sn'];
                if (!empty($noticeSn))
                {
                    break;
                }
                usleep(200);
            } while (empty($noticeSn));
            if (empty($noticeSn))
            {
                throw new \Exception('ERR_SYSTEM|创建订单失败！');
            }

            // 生成[先锋支付]的form表单
            $params = [];
            $params['userId'] = $userInfo['id'];
            $params['outOrderId'] = $noticeSn;
            $params['amount'] = $amountCent;
            //$params['hasTitle'] = !empty($data['show_nav']) ? 'Y' : 'N';
            $params['hasTitle'] = 'Y';
            $params['mobileType'] = isset($data['mobileType']) ? $data['mobileType'] : ($osId == PaymentNoticeModel::PLATFORM_ANDROID ? '12' : '11');
            $params['returnUrl'] = isset($data['return_url']) ? trim($data['return_url']) : 'storemanager://api?type=recharge';

            $result = array();
            if (!empty($data['mobileType'])) {
                $formString = PaymentApi::instance()->getGateway()->getForm('newH5Charge', $params, 'h5chargeForm', false);
                $result['formId'] = 'h5chargeForm';
                $result['form'] = $formString;
            } else {
                $requestUrl = PaymentApi::instance()->getGateway()->getRequestUrl('newH5Charge', $params, 'h5chargeForm', false);
                $result['url'] = $requestUrl;
            }
            $this->json_data = $result;
            return true;
        } catch (\Exception $e) {
            $exceptionInfo = explode('|', $e->getMessage());
            $this->setErr((isset($exceptionInfo[0]) ? $exceptionInfo[0] :'ERR_SYSTEM'), (isset($exceptionInfo[1]) ? $exceptionInfo[1] :''));
            return false;
        }
    }
}