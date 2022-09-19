<?php
/**
 * 快捷充值的限额详情页-页面-APP
 *
 */
namespace api\controllers\payment;

use libs\web\Form;
use libs\utils\PaymentApi;
use api\controllers\AppBaseAction;
use core\service\PaymentUserAccountService;
use api\conf\ConstDefine;
use core\service\PaymentService;
use core\service\ChargeService;

class LimitDetail extends AppBaseAction {
    // 是否h5页面
    const IS_H5 = true;
    // 是否使用session
    protected $useSession = true;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'bankCardId' => array('filter' => 'string', 'option' => array('optional' => true)),
            'bankCode' => array('filter' => 'string', 'option' => array('optional' => true)),
        );

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
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

    public function invokeNew() {
        try {
            $data = $this->form->data;
            // APP登录页
            $returnLoginUrl = $this->getAppScheme('native', array('name'=>'login'));
            // 根据token获取用户信息
            $userInfo = $this->getUserByToken(false);
            if (empty($userInfo)) {
                throw new \Exception('ERR_GET_USER_FAIL');
            }
            $bankCardId = !empty($data['bankCardId']) ? addslashes($data['bankCardId']) : '';
            $bankCode = !empty($data['bankCode']) ? addslashes($data['bankCode']) : '';

            // 获取该用户可用的充值渠道
            $chargeSrv = new ChargeService();
            $channelList = $chargeSrv->queryChannelList($userInfo['id'], $bankCardId, $bankCode);
            $list = !empty($channelList['list']) ? $channelList['list'] : [];
            // 限额拉黑 大额转账和pc扫码
            $blacklist = [ChargeService::PAYMENT_METHOD_BIGPAY, ChargeService::PAYMENT_METHOD_PCPAY];
            $ps = new PaymentService();
            foreach ($list as $key => $channelItem) {
                if ($channelItem['is_valid'] == ChargeService::CHANNEL_NOT_AVALIABLE || in_array($channelItem['payment_method'], $blacklist)) {
                    unset($list[$key]);
                    continue;
                }

                $list[$key]['singleLimitDesc'] = $ps->formatMoneyNew(bcdiv($channelItem['singlelimit'],100, 2)).'元';
                if ($channelItem['singlelimit'] == ChargeService::LIMIT_NONE_LIMIT) {
                    $list[$key]['singleLimitDesc'] = '无限额';
                }
                $list[$key]['dayLimitDesc'] = $ps->formatMoneyNew(bcdiv($channelItem['daylimit'], 100, 2)).'元';
                if ($channelItem['singlelimit'] == ChargeService::LIMIT_NONE_LIMIT) {
                    $list[$key]['dayLimitDesc'] = '无限额';
                }
                $list[$key]['singleMinLimitDesc'] = $ps->formatMoneyNew(bcdiv($channelItem['lowlimit'], 100,2)).'元';
                $list[$key]['limitDesc'] = $channelItem['limit_desc'];
                $list[$key]['limitIntro'] = '';
            }
            $this->tpl->assign('limitInfo', $list);

            $this->template = $this->getTemplate('limit_detail');
            return true;
        } catch (\Exception $e) {
            $exceptionInfo = explode('|', $e->getMessage());
            $this->setErr((isset($exceptionInfo[0]) ? $exceptionInfo[0] : 'ERR_SYSTEM'), (isset($exceptionInfo[1]) ? $exceptionInfo[1] :''));
            // 登录token失效，跳到App登录页
            if ($this->errno == '40002') {
                header('Location:' . $returnLoginUrl);
            }
            return false;
        }
    }

    public function invokeOld() {
        try {
            $data = $this->form->data;
            // APP登录页
            $returnLoginUrl = $this->getAppScheme('native', array('name'=>'login'));
            // 根据token获取用户信息
            $userInfo = $this->getUserByToken(false);
            if (empty($userInfo)) {
                throw new \Exception('ERR_GET_USER_FAIL');
            }

            // 获取该用户可用的充值渠道
            $availableChannel = PaymentUserAccountService::getAvailableChargeChannel($userInfo['id']);
            if (empty($availableChannel) || $availableChannel['ret'] === false) {
                $emptyLimitInfo = [['paymentMethod'=>PaymentApi::PAYMENT_SERVICE_UCFPAY, 'payment_method'=>ChargeService::PAYMENT_METHOD_UCFPAY], ['paymentMethod'=>PaymentApi::PAYMENT_SERVICE_YEEPAY, 'payment_method'=>ChargeService::PAYMENT_METHOD_YEEPAY]];
                $this->tpl->assign('limitInfo', $emptyLimitInfo);
            } else {
                $chargeChannelList = [];
                $paymentAccountobj = new PaymentUserAccountService();
                foreach ($availableChannel['list'] as $key => $channelItem) {
                    $chargeChannelList[$key]['paymentMethod'] = $channelItem['paymentMethod'];
                    if ($channelItem['paymentMethod'] == PaymentApi::PAYMENT_SERVICE_UCFPAY) {
                        $chargeChannelList[$key]['payment_method'] = ChargeService::PAYMENT_METHOD_UCFPAY;
                    }
                    if ($channelItem['paymentMethod'] == PaymentApi::PAYMENT_SERVICE_YEEPAY) {
                        $chargeChannelList[$key]['payment_method'] = ChargeService::PAYMENT_METHOD_YEEPAY;
                    }
                    $chargeChannelList[$key]['paymentName'] = $channelItem['paymentName'];
                    // 获取指定渠道的本地充值限额
                    $limitInfo = $paymentAccountobj->getNewChargeLimit($userInfo['id'], $channelItem['chargeChannel']);
                    $chargeChannelList[$key]['limitDesc'] = $limitInfo['limitDesc'];
                    $chargeChannelList[$key]['singleLimitDesc'] = $limitInfo['singleLimitDesc'];
                    $chargeChannelList[$key]['dayLimitDesc'] = $limitInfo['dayLimitDesc'];
                    $chargeChannelList[$key]['limitIntro'] = $limitInfo['limitIntro'];
                }
                $this->tpl->assign('limitInfo', $chargeChannelList);
            }

            $this->template = $this->getTemplate('limit_detail');
            return true;
        } catch (\Exception $e) {
            $exceptionInfo = explode('|', $e->getMessage());
            $this->setErr((isset($exceptionInfo[0]) ? $exceptionInfo[0] : 'ERR_SYSTEM'), (isset($exceptionInfo[1]) ? $exceptionInfo[1] :''));
            // 登录token失效，跳到App登录页
            if ($this->errno == '40002') {
                header('Location:' . $returnLoginUrl);
            }
            return false;
        }
    }

}
