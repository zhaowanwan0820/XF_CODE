<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 用户持仓基金详情
 *
 * 由代码生成器生成, 不可人为修改
 * @author fupingzhou
 */
class ResponseGetPurchaseOrderDetail extends ResponseBase
{
    /**
     * 持仓信息
     *
     * @var array
     * @required
     */
    private $positionInfo;

    /**
     * @return array
     */
    public function getPositionInfo()
    {
        return $this->positionInfo;
    }

    /**
     * @param array $positionInfo
     * @return ResponseGetPurchaseOrderDetail
     */
    public function setPositionInfo(array $positionInfo)
    {
        $this->positionInfo = $positionInfo;

        return $this;
    }

}