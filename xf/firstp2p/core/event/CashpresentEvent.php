<?php

namespace core\event;

use core\event\BaseEvent;
use NCFGroup\Task\Events\AsyncEvent;
use core\dao\MsgBoxModel;
use core\service\MessageService;
use libs\utils\PaymentApi;
use libs\utils\PaymentCashApi;
use core\service\CashpresentService;

/**
 * 小额代发异步处理逻辑
 * @uses AsyncEvent
 * @package default
 */
class CashpresentEvent extends BaseEvent
{

    private $_params = array();

    public function __construct($params)
    {
        $this->_params = $params;
    }

    public function execute()
    {
        //发送请求
        $params = array(
            'merchantNo' => $this->_params['orderId'],
            'source' => '1',
            'amount' => $this->_params['amount'],
            'transCur' => '156',
            'userType' => '1',
            'accountNo' => $this->_params['bankcard'],
            'accountName' => $this->_params['realName'],
            'mobileNo' => $this->_params['mobile'],
            'bankNo' => $this->_params['bankShortName'],
            'memo' => $this->_params['userId'],
        );

        try {
            $ret = PaymentCashApi::instance()->request('withdraw', $params);

            //处理返回结果
            $cashpresentService = new CashpresentService();
            $cashpresentService->processApiResult($ret['merchantNo'], $ret['status'], $this->_params['userId']);
        } catch (\Exception $e) {
            PaymentApi::log('CashpresentEventFailed. message:'.$e->getMessage());
        }

        return true;
    }

    public function alertMails()
    {
        return array('quanhengzhuang@ucfgroup.com', 'wangqunqiang@ucfgroup.com');
    }

}
