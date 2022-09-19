<?php
/**
 * 电信补发流量和打款五元
 * @author 王群强<wangqunqiang@ucfgroup.com>
 */
ini_set('memory_limit', '512M');
set_time_limit(0);
require_once(dirname(__FILE__) . '/../app/init.php');
require_once dirname(__FILE__).'/../system/libs/msgcenter.php';

use libs\utils\Alarm;

use libs\utils\PaymentApi;
use libs\utils\PaymentCashApi;
use NCFGroup\Task\Services\TaskService AS GTaskService;
use core\event\CashpresentEvent;

class TelO2oWorker
{
    /**
     * 读取异常的用户数据，打款失败或者发流量失败的用户
     */
    public function run()
    {
        $rows = $this->_getData();
        $cntRows = count($rows);
        \libs\utils\PaymentApi::log('TELCOM RESCUE '.$cntRows.'RECORDS');
        foreach($rows as $item) {
            if (empty($item['paystatus'])) {
                // 打款
                try {
                    \libs\utils\PaymentApi::log('TELCOM RESCUE cashPresent '. $item['id']);
                    $cashPresent = new \core\service\CashpresentService();
                    // 没创建打款记录的补创建订单
                    if (empty($item['cashid'])) {
                        $cashPresent->pay($item['id'], $item['real_name'], $item['mobile'], '500', $item['bankcard'], $item['short_name']);
                    }
                    else{
                        if ($item['cashStatus'] == '2') {
                            throw new \Exception('电信活动打款失败');
                        }
                        //异步处理现金发放
                        $params = array(
                            'userId' => $item['id'],
                            'orderId' => $item['cashid'],
                            'amount' => '500',
                            'realName' => $item['real_name'],
                            'mobile' => $item['mobile'],
                            'bankcard' => $item['bankcard'],
                            'bankShortName' => $item['short_name'],
                        );
                        $event = new CashpresentEvent($params);
                        $obj = new GTaskService();
                        $ret = $obj->doBackground($event, 60);
                        PaymentApi::log('CashpresentEventPush. params:'.json_encode($params));
                    }
                }
                catch (\Exception $e) {
                    PaymentApi::log('CashpresentEventFailed!');
                }
            }
            if (empty($item['packagestatus'])) {
                if ($item['invite_code'] != 'F055D9') {
                    return true;
                }
                // 打流量
                \libs\utils\PaymentApi::log('TELCOM RESCUE packageTransfer '. $item['id']);
                $BdActivityService = new \core\service\BdActivityService();
                $res = $BdActivityService->pushYiShangOrder(0,$item['id'],$item['invite_code']);
                if ($res) {
                    $tagService = new \core\service\UserTagService();
                    $tagService->addUserTagsByConstName($userId, array('O2O_TELTRANS'));
                }
            }
        }
    }

    private function _getData()
    {
        $ret = $GLOBALS['db']->get_slave()->getAll("
        SELECT u.id,p.id AS cashid,p.status AS cashStatus,u.mobile,u.real_name,u.invite_code,b.bankcard,a.`status` as packagestatus,p.`status` as paystatus,ba.short_name
        FROM firstp2p_user u
        LEFT JOIN firstp2p_cash_present p ON p.user_id = u.id
        LEFT JOIN firstp2p_user_bankcard b ON u.id = b.user_id
        LEFT JOIN firstp2p_bank ba ON b.bank_id = ba.id
        LEFT JOIN firstp2p_bd_activity_order a ON a.user_id = u.id
        WHERE invite_code ='F055D9' AND b.bankcard != '' AND (p.status = '' OR a.status = '')");
        return $ret;
    }
}

$telO2oWorker = new TelO2oWorker();
$telO2oWorker->run();
