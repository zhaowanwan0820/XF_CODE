<?php
/**
 * 重新提现
 */
namespace task\apis\withdraw;

use task\lib\ApiAction;
use core\service\supervision\SupervisionWithdrawService;

class Redo extends ApiAction
{
    public function invoke()
    {
        $param  = $this->getParam();
        $response['msg'] = '';
        $response['status'] = 0;
        $outOrderId = !empty($param['outOrderId']) ? trim($param['outOrderId']) : '';
        try {
            if (empty($outOrderId)) {
                throw new \Exception('订单号不存在');
            }
            $sws = new SupervisionWithdrawService();
            if (!$sws->redoWithdraw($outOrderId, $param['userId']))
            {
                throw new \Exception('发起重新提现失败');
            }
        } catch (\Exception $e) {
            $response['msg'] = $e->getMessage();
            $response['status'] = -1;
        }
        $this->json_data = $response;
    }
}
