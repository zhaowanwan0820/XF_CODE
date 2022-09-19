<?php
use core\service\PaymentCheckService;

/**
 * 大额充值订单查询， 资金到账流水查询页面
 */
class TransferOrderQueryAction extends CommonAction
{
    public function index()
    {
        $checkService = new PaymentCheckService();
        $params['pageNo'] = '1';

        // 默认查询时间
        $startDate = !empty($_REQUEST['startDate']) ? $_REQUEST['startDate'] : date('Y-m-d 00:00:00', time());
        $endDate = !empty($_REQUEST['endDate']) ? $_REQUEST['endDate'] : date('Y-m-d 23:59:59', time());
        $_REQUEST['startDate'] = $startDate;
        $_REQUEST['endDate'] = $endDate;
        $params = array_merge($params, $_REQUEST);
        unset($params['a']);
        unset($params['m']);

        if (isset($params['orderStatus']) && $params['orderStatus'] == 'all')
        {
            $params['orderStatus'] = '';
        }
        $result = $checkService->queryOfflineOrders($params);
        $this->assign('list', $result);
        $this->assign('startDate', $startDate);
        $this->assign('endDate', $endDate);

        $merchantId = !empty($_REQUEST['merchantId']) ? $_REQUEST['merchantId'] : '';
        $this->assign('merchantId', $merchantId);
        $this->display();
    }

    public function accountRecords()
    {
        $checkService = new PaymentCheckService();
        $params['pageNo'] = '1';

        $transStartTime = !empty($_REQUEST['transStartTime']) ? $_REQUEST['transStartTime'] : '';
        $transEndTime = !empty($_REQUEST['transEndTime']) ? $_REQUEST['transEndTime'] : '';
        $_REQUEST['transStartTime'] = $transStartTime;
        $_REQUEST['transEndTime'] = $transEndTime;

        $accountStartDate = !empty($_REQUEST['accountStartDate']) ? $_REQUEST['accountStartDate'] : '';
        $accountEndDate = !empty($_REQUEST['accountEndDate']) ? $_REQUEST['accountEndDate'] : '';
        $_REQUEST['accountStartDate'] = $accountStartDate;
        $_REQUEST['accountEndDate'] = $accountEndDate;

        $params = array_merge($params, $_REQUEST);
        unset($params['a']);
        unset($params['m']);

        $this->assign('transStartTime', $transStartTime);
        $this->assign('transEndTime', $transEndTime);

        $this->assign('accountStartDate', $accountStartDate);
        $this->assign('accountEndDate', $accountEndDate);

        $merchantId = !empty($_REQUEST['merchantId']) ? $_REQUEST['merchantId'] : '';
        $this->assign('merchantId', $merchantId);
        $result = $checkService->queryAccountRecords($params);
        $this->assign('list', $result);
        $this->display();
    }
}
