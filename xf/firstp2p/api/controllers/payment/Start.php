<?php

/**
 * 易宝-充值首页-页面-APP
 *
 * @author 郭峰<guofeng3@ucfgroup.com>
 */

namespace api\controllers\payment;

use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\PaymentApi;
use core\service\YeepayPaymentService;
use core\service\UserBankcardService;
use core\dao\PaymentNoticeModel;
use core\service\ChargeService;
use api\conf\ConstDefine;
use core\service\PaymentUserAccountService;

class Start extends AppBaseAction {

    const IS_H5 = true;

    protected $useSession = true;
    public function init() {

        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'money' => array('filter' => 'float', 'option' => array('optional' => true)),
            'bankCardId' => array('filter' => 'string', 'option' => array('optional' => true)),
        );

        if (!$this->form->validate())
        {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        if ($this->getAppVersion() >= ConstDefine::VERSION_MULTI_CARD) {
            return $this->invokeNew();
        }
        return $this->invokeOld();
    }

    public function invokeOld() {
        try {
            // APP登录页
            $returnLoginUrl = $this->getAppScheme('native', array('name'=>'login'));
            // 根据token获取用户信息
            $userInfo = $this->getUserByToken();
            if (empty($userInfo))
            {
                throw new \Exception('ERR_GET_USER_FAIL');
            }
            $data = $this->form->data;
            // 获取当前可用的支付方式
            $paymentChannelList = PaymentApi::getPaymentChannel();
            // 没有可用的支付方式
            if (empty($paymentChannelList))
            {
                throw new \Exception('ERR_MANUAL_REASON|暂无可用的支付渠道');
            }

            // 用户输入的金额，单位元
            $money = '';
            if (bccomp($data['money'], '0.00', 2) > 0) {
                $money = $data['money'];
            }

            // 用户余额
            $userInfo['remain'] = format_price($userInfo['money'], false);
            // 把用户信息、订单信息，存到redis哨兵，有效期60分钟
            $redis = YeepayPaymentService::getRedisSentinels();
            $userClientKey = md5(sprintf('%s', $data['token']));
            $cacheKey = sprintf(YeepayPaymentService::CACHEKEY_YEEPAY_PAYMENT_API, $userClientKey);
            $cacheData = array(
                'userId' => $userInfo['id'],
                'token' => $data['token'],
            );
            $redis->hMset($cacheKey, $cacheData);
            $redis->expire($cacheKey, 3600);

            // 用户在app原生页面输入的金额，单位元
            $this->tpl->assign('money', $money);
            // 临时Token
            $this->tpl->assign('asgn', $this->setAsgnToken());
            // 载入充值首页
            $this->tpl->assign('userInfo', $userInfo);
            $this->tpl->assign('paymentChannelList', $paymentChannelList);
            $this->tpl->assign('userClientKey', $userClientKey);
            $this->tpl->assign('returnLoginUrl', $returnLoginUrl);
            // 获取易宝渠道的本地充值限额
            $paymentAccountobj = new PaymentUserAccountService();
            $limitInfo = $paymentAccountobj->getNewChargeLimit($userInfo['id'], PaymentNoticeModel::CHARGE_YEEPAY_CHANNEL);
            $this->tpl->assign('limitInfo', $limitInfo);

            $this->template = $this->getTemplate('yeepay_index_h5');
            return true;
        } catch (\Exception $e) {
            $exceptionInfo = explode('|', $e->getMessage());
            $this->setErr((isset($exceptionInfo[0]) ? $exceptionInfo[0] : 'ERR_SYSTEM'), (isset($exceptionInfo[1]) ? $exceptionInfo[1] :''));
            // 登录token失效，跳到App登录页
            if ($this->errno == '40002')
            {
                header('Location:' . $returnLoginUrl);
            }
            return false;
        }
    }

    public function invokeNew() {
        try {
            // APP登录页
            $returnLoginUrl = $this->getAppScheme('native', array('name'=>'login'));
            // 根据token获取用户信息
            $userInfo = $this->getUserByToken();
            if (empty($userInfo))
            {
                throw new \Exception('ERR_GET_USER_FAIL');
            }

            // 获取当前可用的支付方式
            $paymentChannelList = PaymentApi::getPaymentChannel();
            // 没有可用的支付方式
            if (empty($paymentChannelList))
            {
                throw new \Exception('ERR_MANUAL_REASON|暂无可用的支付渠道');
            }
            $data = $this->form->data;
            $data['appVersion'] = $this->getAppVersion();

            // 用户输入的金额，单位元
            $money = '';
            if (bccomp($data['money'], '0.00', 2) > 0) {
                $money = $data['money'];
            }

            // 取用户支付卡列表中的指定bankcardid的卡数据
            $bankcardServ = new UserBankcardService();
            $bankCardInfo = $bankcardServ->queryBankCardsList($userInfo['id'], false, $data['bankCardId']);
            if (empty($bankCardInfo['list'])) {
                $this->setErr('ERR_MANUAL_REASON', '用户指定的银行卡信息无效,请返回充值界面重新选择银行卡');
                return false;
            }
            $bankCardInfo = $bankCardInfo['list'];

            // 用户余额
            $userInfo['remain'] = format_price($userInfo['money'], false);
            // 把用户信息、订单信息，存到redis哨兵，有效期60分钟
            $redis = YeepayPaymentService::getRedisSentinels();
            $userClientKey = md5(sprintf('%s', $data['token']));
            $cacheKey = sprintf(YeepayPaymentService::CACHEKEY_YEEPAY_PAYMENT_API, $userClientKey);
            $cacheData = array(
                'userId' => $userInfo['id'],
                'token' => $data['token'],
                'bankCardId' => $data['bankCardId'],
            );
            $redis->hMset($cacheKey, $cacheData);
            $redis->expire($cacheKey, 3600);

            // 用户在app原生页面输入的金额，单位元
            $this->tpl->assign('money', $money);
            // 临时Token
            $this->tpl->assign('asgn', $this->setAsgnToken());
            // 载入充值首页
            $this->tpl->assign('userInfo', $userInfo);
            $this->tpl->assign('paymentChannelList', $paymentChannelList);
            $this->tpl->assign('userClientKey', $userClientKey);
            $this->tpl->assign('bankCardId', $data['bankCardId']);
            $this->tpl->assign('returnLoginUrl', $returnLoginUrl);
            $this->tpl->assign('appVersion', $data['appVersion']);
            // 获取易宝渠道的本地充值限额
            $chargeServ = new ChargeService();
            $limitInfo = $chargeServ->getYeepayLimitInfo($userInfo['id'], $bankCardInfo['bankCode']);
            $this->tpl->assign('limitInfo', $limitInfo);

            $this->template = $this->getTemplate('yeepay_index_h5');
            return true;
        } catch (\Exception $e) {
            $exceptionInfo = explode('|', $e->getMessage());
            $this->setErr((isset($exceptionInfo[0]) ? $exceptionInfo[0] : 'ERR_SYSTEM'), (isset($exceptionInfo[1]) ? $exceptionInfo[1] :''));
            // 登录token失效，跳到App登录页
            if ($this->errno == '40002')
            {
                header('Location:' . $returnLoginUrl);
            }
            return false;
        }
    }

    /**
     * 设置临时Token
     * @return string
     */
    private function setAsgnToken($sessionId = 'openapi_cr_asgn')
    {
        $asgn = md5(uniqid());
        \es_session::set($sessionId, $asgn);
        return $asgn;
    }

    public function _after_invoke()
    {
        if (self::IS_H5 && $this->errno == 0)
        {
            $this->setAutoViewDir();
            $this->tpl->display($this->template);
        } else {
            $arr_result = array();
            if ($this->errno == 0) {
                $arr_result['errno'] = 0;
                $arr_result['error'] = '';
                $arr_result['data'] = $this->json_data;
            } else {
                $arr_result['errno'] = $this->errno;
                $arr_result['error'] = $this->error;
                $arr_result['data'] = '';
            }
            if (isset($_REQUEST['debug']) && $_REQUEST['debug'] == 1) {
                var_export($arr_result);
            } else {
                header('Content-type: application/json;charset=UTF-8');
                echo json_encode($arr_result, JSON_UNESCAPED_UNICODE);
            }
        }
    }
}
