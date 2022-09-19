<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\ResponseBase;
use Assert\Assertion;
use NCFGroup\Protos\O2O\ProtoDelivery;

/**
 * 待发货列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author Vincent <daiyuxin@ucfgroup.com>
 */
class ResponseGetDeliveryList extends ResponseBase
{
    /**
     * 待发货列表（带翻页）
     *
     * @var \NCFGroup\Common\Extensions\Base\Page<ProtoDelivery>
     * @required
     */
    private $dataPage;

    /**
     * @return \NCFGroup\Common\Extensions\Base\Page<ProtoDelivery>
     */
    public function getDataPage()
    {
        return $this->dataPage;
    }

    /**
     * @param \NCFGroup\Common\Extensions\Base\Page<ProtoDelivery> $dataPage
     * @return ResponseGetDeliveryList
     */
    public function setDataPage(\NCFGroup\Common\Extensions\Base\Page $dataPage)
    {
        $this->dataPage = $dataPage;

        return $this;
    }

}