<?php
namespace core\event;

use NCFGroup\Common\Extensions\Base\ResponseBase;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;
use NCFGroup\Task\Events\AsyncEvent;

use core\event\BaseEvent;
use libs\utils\Logger;
use libs\utils\Curl;

use core\service\UserService;

class OpenEvent extends BaseEvent
{
    const SEND_COUPON  = 1; //发送优惠券
    const ADUNION_DEAL = 2; //广告联盟埋点入库
    const USE_TICKET   = 3; //注册后将编码置为已经使用

    private $_action; //发生的事件

    private $_params; //事件的参数

    private $_rpcRes; //rpc的结果

    public function __construct($action, $params) {
        $this->_action = $action;
        $this->_params = $params;
    }

    public function execute() {
        if ($this->_action == self::USE_TICKET) {
            return $this->_useTicket();
        }

        if ($this->_action == self::SEND_COUPON) {
            return $this->_sendCoupon();
        }

        if ($this->_action == self::ADUNION_DEAL) {
            return $this->_recordAdunionDeal();
        }

        return false;
    }

    private function _useTicket() {
        $response = $this->_rpcOpen('OpenTicket', 'useTicket', $this->_params);
        return $this->_rpcRes && $response->result;
    }

    private function _sendCoupon() {
        $response = $this->_rpcOpen('OpenTicket', 'sendCouponNow', $this->_params);
        return $this->_rpcRes && !$response->data;
    }

    private function _dealAnul($data) {
        $anulRatio = in_array($data['loantype'], array(1, 2, 8)) ? 0.56 : 1;
        return floor($data['deal_load_money'] * $data['repay_time'] / 360 * $anulRatio * 100) / 100;
    }

    private function _recordAdunionDeal() {
        $userService = new UserService();
        $userInfo = $userService->getUserViaSlave($this->_params['uid']);
        if (empty($userInfo)) {
            Logger::warn("查询用户信息失败, 数据:" . json_encode($this->_params));
            return false;
        } else {
            $userInfo = $userInfo->getRow();
        }

        $proId    = app_conf('MINNIE_PRO_ID');
        $regTime  = date("Y-m-d H:i:s", $userInfo['create_time'] + 8 * 3600);
        $coupon   = empty($this->_params['cn']) ? $this->_params['goods_cn'] : $this->_params['cn'];

        $saveUrl  = app_conf('MINNIE_ADD_URL');
        $saveData = array(
             'pro_id'          => $proId,
             'action'          => 'REG',
             'open_id'         => numTo32($this->_params['uid']),
             'euid'            => $coupon . '_' . $this->_params['euid'],
             'data_unique_key' => $this->_params['uid'],
             'action_data'     => json_encode(array('coupon' => $coupon, 'regist_time' => $regTime)),
        );

        $result = json_decode(Curl::post($saveUrl, $saveData), true);
        if (empty($result) || Curl::$httpCode != 200 || $result['errno']) {
            Logger::error("保存数据失败, 数据:" . json_encode($saveData));
            return false;
        }

        if (!$this->_params['goods_price']) {
            return true;
        }

        $saveData = array(
            'pro_id'          => $proId,
            'action'          => 'DEAL',
            'open_id'         => numTo32($this->_params['uid']),
            'euid'            => $coupon . '_' . $this->_params['euid'],
            'data_unique_key' => $this->_params['order_sn'],
            'action_data'     => json_encode(array(
                  'uid'              => $this->_params['uid'],
                  'username'         => $userInfo['real_name'],
                  'order_sn'         => $this->_params['order_sn'],
                  'order_money'      => $this->_params['total_price'],
                  'ordertime'        => $this->_params['order_time'],
                  'dealid'           => $this->_params['mid'],
                  'dealname'         => $this->_params['goods_name'],
                  'deal_start_money' => $this->_params['deal_info']['min_loan_money'],
                  'deal_repay_time'  => $this->_params['deal_info']['repay_time'],
                  'deal_loantype'    => $this->_params['deal_info']['loantype'],
                  'deal_type'        => $this->_params['deal_info']['deal_type'],
                  'deal_anul'        => $this->_dealAnul(array(
                        'loantype'        => $this->_params['deal_info']['loantype'],
                        'repay_time'      => $this->_params['days'],
                        'deal_load_money' => $this->_params['total_price']
                  )),
             )),
        );

        $result = json_decode(Curl::post($saveUrl, $saveData), true);
        if (empty($result) || Curl::$httpCode != 200 || $result['errno']) {
            Logger::error("保存数据失败, 数据:" . json_encode($saveData));
            return false;
        }

        return true;
    }

    private function _rpcOpen($service, $method, $params) {
        $request = new SimpleRequestBase();
        $request->setParamArray($params);

        try {
            $this->_rpcRes = true;
            $openRpc = $this->_getOpenRpc();
            $openRpc->setTimeOut(31);
            return $openRpc->callByObject(array(
                'service' => 'NCFGroup\Open\Services\\' . $service,
                'method' => $method,
                'args' => $request,
            ));
        } catch (\Exception $e) {
            Logger::error(sprintf("RPC OPEN [%s - %s] FAIL, PARAMS:%s", $service, $method, json_encode($params, JSON_UNESCAPED_UNICODE)));
            $this->_rpcRes = false;
            return new ResponseBase();
        }
    }

    private function _getOpenRpc() {
        if(!isset($GLOBALS['openbackRpc'])) {
            \libs\utils\PhalconRPCInject::init();
        }

        return $GLOBALS['openbackRpc'];
    }

    public function alertMails() {
        return array('wangge@ucfgroup.com', 'daiyuxin@ucfgroup.com', 'wangzengli@ucfgroup.com', 'zhangyao1@ucfgroup.com');
    }

}
