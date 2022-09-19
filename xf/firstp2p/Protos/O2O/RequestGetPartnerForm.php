<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * o2o:获取合作方表单配置
 *
 * 由代码生成器生成, 不可人为修改
 * @author liguizhi
 */
class RequestGetPartnerForm extends ProtoBufferBase
{
    /**
     * 门店ID
     *
     * @var string
     * @required
     */
    private $storeId;

    /**
     * @return string
     */
    public function getStoreId()
    {
        return $this->storeId;
    }

    /**
     * @param string $storeId
     * @return RequestGetPartnerForm
     */
    public function setStoreId($storeId)
    {
        \Assert\Assertion::string($storeId);

        $this->storeId = $storeId;

        return $this;
    }

}