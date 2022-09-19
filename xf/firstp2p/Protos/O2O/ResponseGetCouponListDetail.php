<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\ResponseBase;
use Assert\Assertion;

/**
 * 券码明细列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author yangqing
 */
class ResponseGetCouponListDetail extends ResponseBase
{
    /**
     * 券码明细列表（带翻页）
     *
     * @var \NCFGroup\Common\Extensions\Base\Page<ProtoCfpCommission>
     * @required
     */
    private $dataPage;

    /**
     * @return \NCFGroup\Common\Extensions\Base\Page<ProtoCfpCommission>
     */
    public function getDataPage()
    {
        return $this->dataPage;
    }

    /**
     * @param \NCFGroup\Common\Extensions\Base\Page<ProtoCfpCommission> $dataPage
     * @return ResponseGetCouponListDetail
     */
    public function setDataPage(\NCFGroup\Common\Extensions\Base\Page $dataPage)
    {
        $this->dataPage = $dataPage;

        return $this;
    }

}