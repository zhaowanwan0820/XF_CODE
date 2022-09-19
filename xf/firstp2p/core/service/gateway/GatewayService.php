<?php
namespace core\service\gateway;

use libs\utils\PaymentApi;
use libs\common\ErrCode;
use libs\db\Db;
use core\service\ChargeService;
use core\dao\PaymentNoticeModel;
use core\dao\UserModel;

/**
 * P2P存管服务类
 *
 */
class GatewayService {
    public $services = array(
        'finance.allocateTransfer' => array('self', 'allocateTransfer'),
        'trade.batchOrderSearch' => array('\core\service\gateway\BatchOrderSearchService', 'execute'),
        'member.assetsAmount' => array('self', 'assetsAmount'),
    );


    public function execute($params) {
        $service = $this->getService($params);
        $result = call_user_func_array($service, [$params]);
        return $result;
    }

    public function getService($requestData) {
        // 检查请求时间
        $requestTime = !empty($requestData['requestTime']) ? strtotime($requestData['requestTime']) : 0;
        if (time() - $requestTime > 10) {
            $this->exception('ERR_REQUEST_TIMEOUT');
        }
        // 服务获取
        if (empty($requestData['service']) || !isset($this->services[trim($requestData['service'])])) {
            $this->exception('ERR_SERVICE');
        }
        return $this->services[trim($requestData['service'])];
    }

    public function exception($key) {
        throw new \Exception(ErrCode::getMsg($key), ErrCode::getCode($key));
    }

    /**
     * 划拨接口
     */
    public function allocateTransfer() {
        $params = func_get_args();
        $params = $params[0];
        $userId = isset($params['userId']) ? intval($params['userId']) : $this->exception('ERR_PARAM');
        $orderId = isset($params['orderId']) ? trim($params['orderId']) : $this->exception('ERR_PARAM');
        $amount = isset($params['amount']) ? intval($params['amount']) : $this->exception('ERR_PARAM');
        $bizDesc = isset($params['bizDesc']) ? trim($params['bizDesc']) : $this->exception('ERR_PARAM');
        $memo = isset($params['memo']) ? trim($params['memo']) : '';

        // 并发控制
        $uniqOutOrderId = 'ALLOCATE_TRANSFER_'.$orderId;
        $redis = \SiteApp::init()->dataCache;
        $redisState = $redis->setNx($uniqOutOrderId, 1, 5);
        $state = is_object($redisState) ? $redisState->getPayload(): 'FAIL';
        if ($state !== 'OK') {
            $this->exception('ERR_REQUEST_FREQUENCY_TOO_FAST');
        }
        $db = Db::getInstance('firstp2p', 'master');
        try {
            $db->startTrans();
            // 充值金额，转换成元
            $money = bcdiv($amount, 100, 2);
            // 改业务单状态
            $chargeService = new ChargeService();
            $noticeId = $chargeService->createOrder($userId, $money, PaymentNoticeModel::PLATFORM_SUPERVISION, $orderId);
            $i = 0;
            while (empty($paymentNotice) && $i ++ < 3) {
                $paymentNotice = $db->getRow("SELECT * FROM firstp2p_payment_notice WHERE id ='{$noticeId}'");
                usleep(500);
            }
            $result = $chargeService->paidSuccess($paymentNotice);
            if ($result !== true) {
                $this->exception('ERR_CHARGE_FAILED');
            }
            $db->commit();
            return true;
        } catch (\Exception $e) {
            $db->rollback();

            PaymentApi::log('处理用户交易失败,'.$e->getMessage().'('.$e->getCode().')');
            throw new \Exception('处理用户交易失败', $e->getCode());
        }
    }

    /**
     * 查询用户在投资产总额接口
     */
    public function assetsAmount() {
        $paramsArgs = func_get_args();
        $params = isset($paramsArgs[0]) ? $paramsArgs[0] : [];
        $userId = isset($params['userId']) ? intval($params['userId']) : $this->exception('ERR_PARAM');

        // 检查用户是否存在
        $userInfo = UserModel::instance()->find($userId, 'user_name', true);
        if (empty($userInfo)) {
            $this->exception('ERR_USER_NOEXIST');
        }

        $sql = sprintf('SELECT SUM(`money`) AS `sum` FROM firstp2p_deal_loan_repay WHERE `status` = 0 AND `type` = 1 AND loan_user_id=%d', $userId);
        $amount = Db::getInstance('firstp2p', 'slave')->getOne($sql);
        return array('uid'=>$userId, 'amount'=>(is_null($amount) ? 0 : $amount));
    }

}