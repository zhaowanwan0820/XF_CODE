<?php

/**
 * 易宝-充值下单逻辑-接口-APP
 *
 * @author 郭峰<guofeng3@ucfgroup.com>
 */

namespace api\controllers\payment;

use libs\web\Form;
use api\controllers\YeepayBaseAction;
use core\service\ChargeService;
use core\service\YeepayPaymentService;
use core\dao\PaymentNoticeModel;
use libs\utils\PaymentApi;
use api\conf\ConstDefine;
use core\service\PaymentService;
use core\service\UserBankcardService;

class CreateOrder extends YeepayBaseAction {

    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'money' => array('filter' => 'float'),
            'asgn' => array('filter' => 'required', 'message' => 'asgn is required'),
            'bankCardId' => array('filter' => 'string', 'option' => array('optional' => true)),
            // appVersion
            'appVersion' => array('filter' => 'int', 'option' => array('optional' => true)),
        );

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate())
        {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
            return false;
        }
        $asgn = $this->getAsgnToken();
        if ($asgn !== $this->form->data['asgn'])
        {
            $this->setErr('ERR_PARAMS_ERROR', '页面已失效，请刷新后重试');
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        if (!empty($data['appVersion']) && $data['appVersion'] >= ConstDefine::VERSION_MULTI_CARD) {
            return $this->invokeNew($data);
        }
        return $this->invokeOld($data);
    }

    public function invokeOld($data) {
       try {
            $userInfo = $this->getUserBaseInfo();
            if (empty($userInfo))
            {
                throw new \Exception('ERR_GET_USER_FAIL');
            }
            // 检查用户是否已在先锋支付开户
            if ($userInfo['payment_user_id'] <= 0)
            {
                throw new \Exception('ERR_MANUAL_REASON|您尚未开户无法进行充值，请稍后再试');
            }

            // 获取当前可用的支付方式
            $paymentChannelList = PaymentApi::getPaymentChannel();
            // 没有可用的支付方式
            if (empty($paymentChannelList))
            {
                throw new \Exception('ERR_MANUAL_REASON|暂无可用的支付渠道');
            }

            $data = $this->form->data;
            if (!isset($data['money']) || empty($data['money']))
            {
                throw new \Exception('ERR_MANUAL_REASON|请填写充值金额');
            }

            // 充值金额，单位元
            $amountYuan = floatval($data['money']);
            // 充值金额，单位分
            $amountCent = bcmul($amountYuan, 100, 2);
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
                throw new \Exception('ERR_MANUAL_REASON|手机充值只支持使用二代身份证验证的用户，如有疑问请致电400-890-9888');
            }

            // 获取当前手机操作系统
            $os = isset($_SERVER['HTTP_OS']) ? $_SERVER['HTTP_OS'] : "iOS";
            $osId = $os === 'Android' ? PaymentNoticeModel::PLATFORM_ANDROID : PaymentNoticeModel::PLATFORM_IOS;
            // 获取支付方式ID
            $paymentId = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_YEEPAY)->getGateway()->getConfig('common', 'PAYMENT_ID');
            // 创建订单
            $chargeService = new ChargeService();
            $orderSn = $chargeService->createOrder($userInfo['id'], $amountYuan, $osId, '', $paymentId);
            if ($orderSn <= 0)
            {
                throw new \Exception('ERR_SYSTEM|创建订单失败！');
            }
            // 根据订单自增ID，获取该订单的数据
            $paymentNoticeModel = new PaymentNoticeModel();
            $paymentNotice = $paymentNoticeModel->find($orderSn);
            // 订单编号
            $noticeSn = $paymentNotice['notice_sn'];
            if (empty($noticeSn))
            {
                throw new \Exception('ERR_SYSTEM|创建订单失败！');
            }

            // 获取redis中的信息
            $userOrderInfo = $this->getUserRedisOrderInfo();
            $redis = YeepayPaymentService::getRedisSentinels();
            // 把用户信息、订单信息，存到redis哨兵，有效期60分钟
            $userClientKey = md5(sprintf('%s|%s', $userOrderInfo['token'], $noticeSn));
            $cacheKey = sprintf(YeepayPaymentService::CACHEKEY_YEEPAY_PAYMENT_API, $userClientKey);
            $cacheData = array(
                'userId' => $userInfo['id'],
                'orderId' => $noticeSn,
                'amountFen' => $amountCent,
                'returnUrl' => $this->getAppScheme('native', array('name'=>'mine')),
                'returnLoginUrl' => $this->getAppScheme('native', array('name'=>'login')),
                'returnSuccessUrl' => $this->getAppScheme('closeall'),
                'token' => $userOrderInfo['token'],
            );
            $redis->hMset($cacheKey, $cacheData);
            $redis->expire($cacheKey, 3600);

            // 生成[易宝支付]的form表单
            $form = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_YEEPAY)->getGateway()->getForm('payBindRequest', array('userClientKey'=>$userClientKey), 'h5chargeForm', false);

            $result = array();
            $result['formid'] = 'h5chargeForm';
            $result['form'] = $form;
            $this->json_data = $result;
            return true;
        } catch (\Exception $e) {
            $exceptionInfo = explode('|', $e->getMessage());
            $this->setErr((isset($exceptionInfo[0]) ? $exceptionInfo[0] :'ERR_SYSTEM'), (isset($exceptionInfo[1]) ? $exceptionInfo[1] :''));
            return false;
        }
    }


    public function invokeNew($data) {
        try {
            $userInfo = $this->getUserBaseInfo();
            if (empty($userInfo))
            {
                throw new \Exception('ERR_GET_USER_FAIL');
            }
            // 检查用户是否已在先锋支付开户
            if ($userInfo['payment_user_id'] <= 0)
            {
                throw new \Exception('ERR_MANUAL_REASON|您尚未开户无法进行充值，请稍后再试');
            }

            // 获取当前可用的支付方式
            $paymentChannelList = PaymentApi::getPaymentChannel();
            // 没有可用的支付方式
            if (empty($paymentChannelList))
            {
                throw new \Exception('ERR_MANUAL_REASON|暂无可用的支付渠道');
            }

            if (!isset($data['money']) || empty($data['money']))
            {
                throw new \Exception('ERR_MANUAL_REASON|请填写充值金额');
            }

            // 充值金额，单位元
            $amountYuan = floatval($data['money']);
            // 充值金额，单位分
            $amountCent = bcmul($amountYuan, 100, 2);

            // 易宝充值单笔限额服务端判断
            $chargeServ = new ChargeService();
            $ps = new PaymentService();
            $userBankCardService = new UserBankcardService();
            $cardInfo = $userBankCardService->queryBankCardsList($userInfo['id'], false, $data['bankCardId']);

            $limitInfo = [];
            if (!empty($cardInfo['list'])) {
                $limitInfo = $chargeServ->getYeepayLimitInfo($userInfo['id'], $cardInfo['list']['bankCode']);
            }

            // 限额判断 如果不存在 则认为无限额
            do {
                if (empty($limitInfo)) {
                    break;
                }
                // 限制单笔最少金额为1元
                if(bccomp($limitInfo['lowlimit'], 0) >=0 && bccomp($amountCent, $limitInfo['lowlimit']) < 0)
                {
                    throw new \Exception('ERR_PARAMS_VERIFY_FAIL|充值金额最低'.$ps->formatMoney(bcdiv($limitInfo['lowlimit'], 100, 2)).'元');
                }
                // 限制单笔最大金额
                if(bccomp($limitInfo['singlelimit'], 0) >= 0 && bccomp($amountCent, $limitInfo['singlelimit']) > 0)
                {
                    throw new \Exception('ERR_PARAMS_VERIFY_FAIL|单笔订单金额不能超过'.$ps->formatMoney(bcdiv($limitInfo['singlelimit'], 100, 2)).'元');
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
            // 获取支付方式ID
            $paymentId = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_YEEPAY)->getGateway()->getConfig('common', 'PAYMENT_ID');
            // 创建订单
            $chargeService = new ChargeService();
            $orderSn = $chargeService->createOrder($userInfo['id'], $amountYuan, $osId, '', $paymentId);
            if ($orderSn <= 0)
            {
                throw new \Exception('ERR_SYSTEM|创建订单失败！');
            }
            // 根据订单自增ID，获取该订单的数据
            $paymentNoticeModel = new PaymentNoticeModel();
            $paymentNotice = $paymentNoticeModel->find($orderSn);
            // 订单编号
            $noticeSn = $paymentNotice['notice_sn'];
            if (empty($noticeSn))
            {
                throw new \Exception('ERR_SYSTEM|创建订单失败！');
            }

            // 获取redis中的信息
            $userOrderInfo = $this->getUserRedisOrderInfo();
            $redis = YeepayPaymentService::getRedisSentinels();
            // 把用户信息、订单信息，存到redis哨兵，有效期60分钟
            $userClientKey = md5(sprintf('%s|%s', $userOrderInfo['token'], $noticeSn));
            $cacheKey = sprintf(YeepayPaymentService::CACHEKEY_YEEPAY_PAYMENT_API, $userClientKey);
            $cacheData = array(
                'userId' => $userInfo['id'],
                'orderId' => $noticeSn,
                'amountFen' => $amountCent,
                'returnUrl' => $this->getAppScheme('native', array('name'=>'mine')),
                'returnLoginUrl' => $this->getAppScheme('native', array('name'=>'login')),
                'returnSuccessUrl' => $this->getAppScheme('closeall'),
                'token' => $userOrderInfo['token'],
            );
            $redis->hMset($cacheKey, $cacheData);
            $redis->expire($cacheKey, 3600);

            // 生成[易宝支付]的form表单
            $form = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_YEEPAY)->getGateway()->getForm('payBindRequest', array('userClientKey'=>$userClientKey, 'bankCardId' => $data['bankCardId'], 'money' => $data['money'], 'appVersion' => $data['appVersion']), 'h5chargeForm', false);

            $result = array();
            $result['formid'] = 'h5chargeForm';
            $result['form'] = $form;
            $this->json_data = $result;
            return true;
        } catch (\Exception $e) {
            $exceptionInfo = explode('|', $e->getMessage());
            $this->setErr((isset($exceptionInfo[0]) ? $exceptionInfo[0] :'ERR_SYSTEM'), (isset($exceptionInfo[1]) ? $exceptionInfo[1] :''));
            return false;
        }
    }
}
