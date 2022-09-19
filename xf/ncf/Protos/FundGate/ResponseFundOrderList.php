<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;

/**
 * 基金购买列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author Gu Weigang <guweigang@ucfgroup.com>
 */
class ResponseFundOrderList extends ProtoBufferBase
{
    /**
     * 已购列表
     *
     * @var array<ProtoFundOrder>
     * @required
     */
    private $data;

    /**
     * @return array<ProtoFundOrder>
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param array<ProtoFundOrder> $data
     * @return ResponseFundOrderList
     */
    public function setData(array $data)
    {
        $this->data = $data;

        return $this;
    }

}