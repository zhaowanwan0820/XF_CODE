<?php
/**
 * 充值检查
 * 点击"已完成支付"跳转的页面
 */
namespace web\controllers\payment;
use libs\web\Form;
use web\controllers\BaseAction;
use core\dao\PaymentNoticeModel;
use core\dao\SupervisionChargeModel;
use libs\web\Url;
use libs\utils\PaymentApi;
use core\service\PaymentService;
use core\service\ChargeService;

class PayCheck extends BaseAction
{
    private static $typeMap = [
        0 => 'checkPaymentNotice',
        1 => 'checkSupervitionCharge',
    ];

    public function init()
    {
        if(!$this->check_login()) return false;
        $this->form = new Form('get');
        $this->form->rules = array(
            'id' => array('filter' => 'int'),
            'type' => array('filter' => 'int'),
            'check'=>array('filter'=>'int'),
            'autoCheck'=>array('filter'=>'int'),
        );
        $this->form->validate();
    }

    public function invoke()
    {
        $id = isset($this->form->data['id']) ? intval($this->form->data['id']) : 0;
        if (empty($id)) {
            return $this->show_error($GLOBALS['lang']['NOTICE_SN_NOT_EXIST']);
        }

        //订单类型 0充值单，1存管订单号
        $type = isset($this->form->data['type']) ? intval($this->form->data['type']) : 0;
        if (!isset(self::$typeMap[$type])) {
            return $this->show_error('非法操作');
        }
        call_user_func_array(['self', self::$typeMap[$type]], [$id]);
    }

    /**
     * 检查充值单
     */
    public function checkPaymentNotice($id) {

        //查询P2P侧订单状态
        $payment_notice = PaymentNoticeModel::instance()->findByViaSlave("id='{$id}' AND user_id='{$GLOBALS['user_info']['id']}'");
        if (empty($payment_notice)) {
            return $this->show_error($GLOBALS['lang']['NOTICE_SN_NOT_EXIST']);
        }

        //已经充值成功
        if ($payment_notice['is_paid'] == ChargeService::STATUS_SUCCESS) {
            $refreshTime = empty($this->form->data['autoCheck']) ? 3 : 15;
            return $this->show_success('恭喜，支付成功！', '', 0, 0, '/account', array(), $refreshTime);
        }

        //已经充值失败
        if ($payment_notice['is_paid'] == ChargeService::STATUS_FAILED) {
            return $this->show_error('充值失败，请重新充值', '', 0, 0, '/account/charge');
        }

        return $this->show_tips('订单正在处理，可能有5-30分钟延迟，请耐心等待。', '订单处理中');
    }

    /**
     * 检查存管充值单
     */
    public function checkSupervitionCharge($id) {

        //查询P2P侧订单状态
        $supervisionCharge = SupervisionChargeModel::instance()->getChargeRecordByOutId($id);
        if (empty($supervisionCharge)) {
            return $this->show_error($GLOBALS['lang']['NOTICE_SN_NOT_EXIST']);
        }

        //从农担分站跳转到普惠充值成功后，返回到农担个人中心页
        $jump = '/account';
        $fromSite = \es_session::get('from_site');
        $fromSiteId = !empty($fromSite['id']) ? $fromSite['id'] : null;
        $fromSiteHost = !empty($fromSite['host']) ? $fromSite['host'] : null;
        if ( !empty($fromSiteId) && !empty($fromSiteHost) && is_nongdan_site($fromSiteId) && $this->is_firstp2p) {
            $jump = get_http() . $fromSiteHost . $jump;
        }

        //已经充值成功
        if ($supervisionCharge['pay_status'] == SupervisionChargeModel::PAY_STATUS_SUCCESS) {
            $refreshTime = empty($this->form->data['autoCheck']) ? 3 : 15;
            return $this->show_success('恭喜，支付成功！', '', 0, 0, $jump, array(), $refreshTime);
        }

        //已经充值失败
        if ($supervisionCharge['pay_status'] == SupervisionChargeModel::PAY_STATUS_FAILURE) {
            return $this->show_error('充值失败，请重新充值', '', 0, 0, '/account/charge');
        }

        return $this->show_tips('订单正在处理，可能有5-30分钟延迟，请耐心等待。', '订单处理中', 0, 0, $jump);
    }

}
