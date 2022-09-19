<?php
namespace NCFGroup\Ptp\services;

use NCFGroup\Common\Extensions\Base\ServiceBase;
use NCFGroup\Protos\Ptp\RequestDtBid;
use core\service\DealService;
use core\service\DealLoadService;
use core\service\DtTransferService;
use core\service\IdempotentService;
use core\service\DtDepositoryService;
use core\service\UserLoanRepayStatisticsService;
use core\dao\UserLoanRepayStatisticsModel;
use core\dao\IdempotentModel;
use core\dao\JobsModel;
use core\dao\DealLoadModel;
use NCFGroup\Protos\Duotou\Enum\CommonEnum;
use core\dao\UserModel;
use libs\utils\Logger;
use core\dao\FinanceQueueModel;

/**
 * 多投投资P2p服务类
 * Class PtpDtTransferService
 * @package NCFGroup\Ptp\services
 */
class PtpDtBidService extends ServiceBase {

    /**
     * 投资P2p标的
     * @param RequestDtBid $request
     * @return bool
     *
     */
    public function bidP2pDeal(RequestDtBid $request) {
        $money = $request->getMoney();
        $orderId = $request->getToken();
        $p2pDealId = $request->getP2pDealId();
        $userId = $request->getUserId();
        $transParams = $request->getTransParams();
        $service = new DtDepositoryService();
        return $service->sendDtBidRequest($orderId,$userId,$p2pDealId,$money,$transParams);
    }
}
