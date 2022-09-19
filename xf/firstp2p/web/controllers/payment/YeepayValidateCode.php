<?php
/**
 * 易宝个人中心充值操作- 在易宝设置充值卡-发送四要素验证
 * @author wangqunqiang<wangqunqiang@ucfgroup.com>
 */

namespace web\controllers\payment;
use libs\web\Form;
use web\controllers\BaseAction;
use core\dao\PaymentModel;
use core\dao\PaymentNoticeModel;
use core\dao\DealOrderModel;
use libs\web\Url;
use libs\utils\PaymentApi;
use libs\utils\Block;
use core\service\PaymentService;
class YeepayValidateCode extends BaseAction {

    public function init() {
        if(!$this->check_login()) return false;
        $this->form = new Form("post");
        $this->form->rules = array(
                'mobile' => array('filter' => 'string'),
                'pd_FrpId'=>array('filter'=>'string'),
        );
        $this->form->validate();
    }


    public function invoke() {
        $data     = $this->form->data;
        // 检查用户请求绑卡接口的频率，60s请求1次
        if (false === Block::check('YEEPAY_BIND_BANKCARD_SECOND', $data['mobile'], true))
        {
            return ajax_return(array('status' => -2, 'msg' => '请求太频繁，请稍后再试'));
        }

        $userId = $GLOBALS['user_info']['id'];
        // 收集用户相关数据，通过session读取
        $sessionData = \es_session::get('yeepay_order_'.$userId);
        if (empty($sessionData))
        {
            return ajax_return(array('status' => -1, 'msg' => '订单支付失败，请重新支付'));
        }
        // 保存手机号
        $sessionData['mobile'] = $data['mobile'];
        \es_session::set('yeepay_order_'.$userId, $sessionData);
        // 请求易宝绑卡验证码接口
        $yeepayPaymentService = new \core\service\YeepayPaymentService();
        $validateRequestParams = array(
            'uid' => $sessionData['userId'],
            'cardno' => $sessionData['cardNo'],
            'idcardno' => $sessionData['idno'],
            'username' => $sessionData['realName'],
            'phone' => $data['mobile'],
        );
        $response = $yeepayPaymentService->bindBankCard($validateRequestParams);

        // 如果返回该卡已绑定，则允许进入确认充值页面(TZ1001028:已绑卡成功)
        if(!isset($response['respCode']) || ($response['respCode'] !== '00' && $response['respCode'] !== 'TZ1001028'))
        {
            return ajax_return(array('status' =>-1, 'msg' => $response['respMsg']));
        }
        if ($response['respCode'] === 'TZ1001028')
        {
            // 已绑卡成功，无需短信验证,跳转
            return ajax_return(array('status' => 1));
        }
        Block::check('YEEPAY_BIND_BANKCARD_SECOND', $data['mobile']);
        return ajax_return(array('status' => 0));
    }
}