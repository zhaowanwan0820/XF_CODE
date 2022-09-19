<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * OpenAppInfo
 *
 * 由代码生成器生成, 不可人为修改
 * @author liuzhenpeng
 */
class ProtoOpenIdInfo extends ProtoBufferBase
{
    /**
     * merchant_id
     *
     * @var int
     * @required
     */
    private $merchant_id;

    /**
     * @return int
     */
    public function getMerchant_id()
    {
        return $this->merchant_id;
    }

    /**
     * @param int $merchant_id
     * @return ProtoOpenIdInfo
     */
    public function setMerchant_id($merchant_id)
    {
        \Assert\Assertion::integer($merchant_id);

        $this->merchant_id = $merchant_id;

        return $this;
    }

}