<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 协议
 *
 * 由代码生成器生成, 不可人为修改
 * @author jingxu
 */
class ResponseGetContract extends ResponseBase
{
    /**
     * 协议
     *
     * @var ProtoContract
     * @required
     */
    private $protoContract;

    /**
     * @return ProtoContract
     */
    public function getProtoContract()
    {
        return $this->protoContract;
    }

    /**
     * @param ProtoContract $protoContract
     * @return ResponseGetContract
     */
    public function setProtoContract(ProtoContract $protoContract)
    {
        $this->protoContract = $protoContract;

        return $this;
    }

}