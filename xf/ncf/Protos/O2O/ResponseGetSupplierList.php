<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\ResponseBase;
use Assert\Assertion;
use NCFGroup\Protos\O2O\ProtoSupplier;

/**
 * 供应商列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author Vincent <daiyuxin@ucfgroup.com>
 */
class ResponseGetSupplierList extends ResponseBase
{
    /**
     * 供应商列表数据（带翻页）
     *
     * @var \NCFGroup\Common\Extensions\Base\Page<ProtoProduct>
     * @required
     */
    private $dataPage;

    /**
     * @return \NCFGroup\Common\Extensions\Base\Page<ProtoSupplier>
     */
    public function getDataPage()
    {
        return $this->dataPage;
    }

    /**
     * @param \NCFGroup\Common\Extensions\Base\Page<ProtoSupplier> $dataPage
     * @return ResponseGetSupplierList
     */
    public function setDataPage($dataPage)
    {
        $this->dataPage = $dataPage;

        return $this;
    }

}
