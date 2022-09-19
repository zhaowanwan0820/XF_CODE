<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\ResponseBase;
use Assert\Assertion;
use NCFGroup\Protos\O2O\ProtoCouponReissue;

/**
 * 券码补发列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author Yu TAO <yutao@ucfgroup.com>
 */
class ResponseGetCouponReissueList extends ResponseBase
{
    /**
     * 券组列表数据（带翻页）
     *
     * @var \NCFGroup\Common\Extensions\Base\Page<ProtoCouponReissue>
     * @required
     */
    private $dataPage;

    /**
     * @return \NCFGroup\Common\Extensions\Base\Page<ProtoCouponReissue>
     */
    public function getDataPage()
    {
        return $this->dataPage;
    }

    /**
     * @param \NCFGroup\Common\Extensions\Base\Page<ProtoCouponReissue> $dataPage
     * @return ResponseGetCouponReissueList
     */
    public function setDataPage(\NCFGroup\Common\Extensions\Base\Page $dataPage)
    {
        $this->dataPage = $dataPage;

        return $this;
    }

}