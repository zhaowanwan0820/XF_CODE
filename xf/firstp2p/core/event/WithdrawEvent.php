<?php
/**
 *-------------------------------------------------------
 * 提现异步请求支付
 *-------------------------------------------------------
 * 2015-07-22 18:23:55
 *-------------------------------------------------------
 */

namespace core\event;

use NCFGroup\Task\Events\AsyncEvent;
use core\service\UserCarryService;
use libs\utils\PaymentApi;
use core\event\BaseEvent;
use libs\utils\Alarm;
use libs\utils\ABControl;

/**
 * WithdrawEvent
 * 提现异步请求支付
 *
 * @uses AsyncEvent
 * @package default
 */
class WithdrawEvent extends BaseEvent
{
    public $withdrawId;

    public function __construct($withdrawId) {
        $this->withdrawId = $withdrawId;
    }

    /**
     * 请求支付接口
     */
    public function execute() {
        $userCarryService = new UserCarryService();
        $withdraw = \libs\db\Db::getInstance('firstp2p', 'slave')->getRow("SELECT * FROM firstp2p_user_carry WHERE id = '{$this->withdrawId}'");
        // 判断告警黑名单
        $slientWithdrawAlarm = ABControl::getInstance()->hit('slientWithdrawAlarmUserIds', ['id' => $withdraw['user_id']]);
        // 支付可用资金校验
        $params = array('source' => 1, 'userId' => $withdraw['user_id']);
        $a_UserBalance = PaymentApi::instance()->request('searchuserbalance', $params);
        if (empty($a_UserBalance))
        {
            $message = "{$withdraw['user_id']} 用户提现记录{$this->withdrawId} 查询支付可用余额失败\n";
            PaymentApi::log($message);
            return true;
        }
        // 余额不足检查，检查余额不足是否因为提现支付已经受理导致
        if(bccomp($a_UserBalance['availableBalance']['amount'], $withdraw['money'], 2) < 0) {
            // 查询支付订单状态
            $response = PaymentApi::instance()->request('searchonetrade', array('businessType' => '14', 'outOrderId' => $withdraw['id']));
            // 支付提现订单不存在的发告警邮件
            if (isset($response['status']) && $response['status'] == '30004')
            {
                $message = "{$withdraw['user_id']} 用户提现记录{$withdraw['id']}支付平台该用户余额不足, 支付可用资金:{$a_UserBalance['availableBalance']['amount']},提现金额:{$withdraw['money']}<br/>\n";
                PaymentApi::log($message);
                // 如果不需要静音告警
                if (!$slientWithdrawAlarm) {
                    Alarm::push('withdraw', '提现发起异常', $message);
                }
                return true;
            }
            else if (isset($response['orderStatus']) && $response['orderStatus'] == '02')
            {
                $updateStatus = array();
                $updateStatus['withdraw_status'] = 3;
                $GLOBALS['db']->autoExecute('firstp2p_user_carry', $updateStatus, 'UPDATE', " id = '{$withdraw['id']}' AND withdraw_status = 0 ");
            }
            // 其他状态等回调
            // else {}
        }
        try {
            $userCarryService->doPass(null, $this->withdrawId);
            return true;
        }
        catch(\Exception $e) {
            // 重试三次失败之后
            // 检查余额不足是否因为提现支付已经受理导致
            PaymentApi::log('WithdrawEvent failed, withdrawId:'.$this->withdrawId);
            return true;
        }

    }

    public function alertMails() {
        return array('wangqunqiang@ucfgroup.com');
    }
}
