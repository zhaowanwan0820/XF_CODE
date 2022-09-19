<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * gearman jobserver 总信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author jingxu
 */
class ResponseJobServerInfo extends ResponseBase
{
    /**
     * 单个job server 信息
     *
     * @var array<ProtoJobServer>
     * @required
     */
    private $protoJobServerArr;

    /**
     * @return array<ProtoJobServer>
     */
    public function getProtoJobServerArr()
    {
        return $this->protoJobServerArr;
    }

    /**
     * @param array<ProtoJobServer> $protoJobServerArr
     * @return ResponseJobServerInfo
     */
    public function setProtoJobServerArr(array $protoJobServerArr)
    {
        $this->protoJobServerArr = $protoJobServerArr;

        return $this;
    }

}