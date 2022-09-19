<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 基金相关协议
 *
 * 由代码生成器生成, 不可人为修改
 * @author jingxu
 */
class ResponseGetContracts extends ResponseBase
{
    /**
     * 协议
     *
     * @var Array<ProtoContract>
     * @required
     */
    private $protoContractList;

    /**
     * @return Array<ProtoContract>
     */
    public function getProtoContractList()
    {
        return $this->protoContractList;
    }

    /**
     * @param Array<ProtoContract> $protoContractList
     * @return ResponseGetContracts
     */
    public function setProtoContractList(array $protoContractList)
    {
        $this->protoContractList = $protoContractList;

        return $this;
    }

}