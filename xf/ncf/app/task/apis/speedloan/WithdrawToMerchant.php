<?php
/**
 * 提现到网信速贷电子账户
 */
namespace task\apis\speedloan;

use NCFGroup\Common\Library\Idworker;
use NCFGroup\Common\Library\StandardApi;

use core\service\supervision\SupervisionFinanceService;
use core\service\supervision\SupervisionOrderService;
use task\lib\ApiAction;

class WithdrawToMerchant extends ApiAction
{
    public function invoke()
    {
        $data = $this->getParam();
        $response['msg'] = '';
        $response['status'] = 0;

        try {
            $userId = isset($data['userId']) ? intval($data['userId']) : '';
            if (empty($userId)) {
                throw new \Exception('用户ID错误');
            }
            $amount= isset($data['amount']) ? intval($data['amount']) : '';
            if (empty($amount)) {
                throw new \Exception('交易金额错误');
            }
            $orderId = isset($data['orderId']) ? intval($data['orderId']) : '';
            if (empty($orderId)) {
                throw new \Exception('提现单号错误');
            }

            // 提现基础业务数据
            $orderInfo = [
                'userId' => $userId,
                'orderId' => $orderId,
            ];

            // 存管行接口参数
            $orderInfo += [
                'bankCardName' => app_conf('SPEED_LOAN_MERCHANT_NAME'),
                'bankCardNo' => app_conf('SPEED_LOAN_MERCHANT_ACCOUNT'),
                'bidId' => intval($data['bidId']),
                'totalAmount' => intval($data['totalAmount']),
                'repayAmount' => intval($data['repayAmount']),
                'cardFlag' => '1',
            ];
            $financeService = new SupervisionFinanceService();
            // 收取服务费
            if (isset($data['serviceFee']) && !empty($data['serviceFee'])) {
                $orderInfo['chargeAmount'] = intval($data['serviceFee']);
                $orderInfo['chargeOrderList'] = json_encode([[
                    'amount' => $orderInfo['chargeAmount'],
                    'receiveUserId' => app_conf('SPEED_LOAN_SERVICE_FEE_UID'),
                    'subOrderId' => Idworker::instance()->getId(),
                ]]);
            }

            //异步添加存管订单
            $supervisionOrderService = new SupervisionOrderService();
            $supervisionOrderService->asyncAddOrder(SupervisionOrderService::SERVICE_CREDIT_LOAN_WITHDRAW, $orderInfo);

            $supervisionApi = StandardApi::instance(StandardApi::SUPERVISION_GATEWAY);
            $orderInfo['noticeUrl'] = app_conf('NOTIFY_DOMAIN').'/creditloan/withdrawNotify';
            // 请求存管接口
            $result= $supervisionApi->request('creditLoanWithdraw', $orderInfo);
            if (!isset($result['respCode']) || $result['respCode'] != '00') {
                throw new \Exception('提现失败');
            }
            $response = ['status' => 0, 'msg' => '成功'];

        } catch (\Exception $e) {
            $response = ['status' => -1, 'msg' => $e->getMessage()];
        }
        $this->json_data = $response;
    }
}
