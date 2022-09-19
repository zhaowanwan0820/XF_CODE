<?php
namespace api\controllers\account;

use api\conf\Error;
use api\controllers\FundBaseAction;
use libs\utils\PaymentApi;
use libs\web\Form;
use core\dao\FundMoneyLogModel;
use libs\utils\Logger;

/**
 * QueryFundMoneyLog
 * 获取余额处理记录
 *
 * @uses BaseAction
 * @package
 * @version $id$
 * @author Pine wangjiansong@ucfgroup.com
 */
class QueryFundMoneyLog extends FundBaseAction
{
    public function init() {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array(
            'signature' => array('filter'=>"required", 'message'=> '签名不能为空！'),
            'out_order_id' => array('filter' => 'required'),
            'event' => array('filter' => 'required'),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;

        $eventArray = array(FundMoneyLogModel::EVENT_LOCK, FundMoneyLogModel::EVENT_UNLOCK, FundMoneyLogModel::EVENT_REFUND);
        if (!in_array($data['event'], $eventArray)) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', '参数校验失败');
            return false;
        }
        $log = $this->rpc->local('FundMoneyLogService\getLogByConditions', array($data['out_order_id'], array('event' => $data['event'])));
        if (!$log) {
            $this->flag = FundBaseAction::FLAG_NO_REQUEST;
        }

        if ($log['status'] != FundMoneyLogModel::STATUS_SUCCESS) {
            $this->flag = FundBaseAction::FLAG_DEAL_FAILED;
        }

        if ($log['status'] == FundMoneyLogModel::STATUS_SUCCESS) {
            $ret = array( 'money' => bcmul($log['money'], 100, 0),
                          'out_order_id' => $log['out_order_id'],
                          'user_id' => $log['user_id'],
                          'fund_name' => $log['fund_name']
                      );

            if ($data['event'] == FundMoneyLogModel::EVENT_UNLOCK) {
                $ret['deal_success'] = $log['event_info'];
            }

            if ($data['event'] == FundMoneyLogModel::EVENT_REFUND) {
                $ret['refund_type'] = ($log['event_info']-2) > 0 ? $log['event_info'] - 2 : $log['event_info'];
            }
        }

        // 记录日志
        $apiLog = $data;
        $apiLog['time'] = date('Y-m-d H:i:s');
        $apiLog['ip'] = get_real_ip();
        PaymentApi::log("API_QUERY_MONEY_LOG:".json_encode($apiLog), Logger::INFO);

        $this->json_data = $ret;
        return true;
    }
}
