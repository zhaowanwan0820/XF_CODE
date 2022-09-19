<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\ResponseBase;
use Assert\Assertion;
use NCFGroup\Protos\O2O\ProtoUserCoupon;

/**
 * 券码列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiansong
 */
class ResponseGetUserCouponList extends ResponseBase
{
    /**
     * 券码列表（带翻页）
     *
     * @var \NCFGroup\Common\Extensions\Base\Page<ProtoUserCoupon>
     * @required
     */
    private $dataPage;

    /**
     * @return \NCFGroup\Common\Extensions\Base\Page<ProtoUserCoupon>
     */
    public function getDataPage()
    {
        return $this->dataPage;
    }

    /**
     * @param \NCFGroup\Common\Extensions\Base\Page<ProtoUserCoupon> $dataPage
     * @return ResponseGetUserCouponList
     */
    public function setDataPage(\NCFGroup\Common\Extensions\Base\Page $dataPage)
    {
        $this->dataPage = $dataPage;

        return $this;
    }

}