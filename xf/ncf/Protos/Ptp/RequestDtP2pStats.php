<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 多投查询p2p统计信息service
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangchuanlu
 */
class RequestDtP2pStats extends AbstractRequestBase
{
    /**
     * p2p标Id
     *
     * @var int
     * @optional
     */
    private $p2pDealId = 0;

    /**
     * @return int
     */
    public function getP2pDealId()
    {
        return $this->p2pDealId;
    }

    /**
     * @param int $p2pDealId
     * @return RequestDtP2pStats
     */
    public function setP2pDealId($p2pDealId = 0)
    {
        $this->p2pDealId = $p2pDealId;

        return $this;
    }

}