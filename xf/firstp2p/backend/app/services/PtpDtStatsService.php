<?php
namespace NCFGroup\Ptp\services;

use NCFGroup\Common\Extensions\Base\ServiceBase;
use NCFGroup\Protos\Ptp\RequestDtP2pStats;
use core\service\DtStatsService;

/**
 * 多投统计服务
 * Class PtpDtStatsService
 * @package NCFGroup\Ptp\services
 */
class PtpDtStatsService extends ServiceBase {

    /**
     * 获取p2p统计信息
     * @param RequestDtP2pStats $request
     * @return boolean|Ambigous <\core\service\mixed, boolean>
     */
    public function getP2pStats(RequestDtP2pStats $request) {
        $ds = new DtStatsService();
        $p2pDealId = $request->getP2pDealId();
        return $ds->getP2pStats($p2pDealId);
    }
}
