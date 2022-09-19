<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ResponseBase;
use Assert\Assertion;

/**
 * 热标与待上线标列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiansong
 */
class ResponseCfpDeals extends ResponseBase
{
    /**
     * 标列表（带翻页）
     *
     * @var \NCFGroup\Common\Extensions\Base\Page<ProtoCfpDeal>
     * @required
     */
    private $dataPage;

    /**
     * @return \NCFGroup\Common\Extensions\Base\Page<ProtoCfpDeal>
     */
    public function getDataPage()
    {
        return $this->dataPage;
    }

    /**
     * @param \NCFGroup\Common\Extensions\Base\Page<ProtoCfpDeal> $dataPage
     * @return ResponseCfpDeals
     */
    public function setDataPage(\NCFGroup\Common\Extensions\Base\Page $dataPage)
    {
        $this->dataPage = $dataPage;

        return $this;
    }

}