<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;

/**
 * 单个job server 信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author jingxu
 */
class ProtoJobServer extends ProtoBufferBase
{
    /**
     * 此jobserver ip
     *
     * @var string
     * @required
     */
    private $ip;

    /**
     * 是否活着
     *
     * @var bool
     * @required
     */
    private $alive;

    /**
     * job server中所注册的function
     *
     * @var array<ProtoJobServerFun>
     * @required
     */
    private $protoJobServerFunArr;

    /**
     * job server 中的 worker 信息
     *
     * @var array
     * @required
     */
    private $workeInfoArr;

    /**
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @param string $ip
     * @return ProtoJobServer
     */
    public function setIp($ip)
    {
        \Assert\Assertion::string($ip);

        $this->ip = $ip;

        return $this;
    }
    /**
     * @return bool
     */
    public function getAlive()
    {
        return $this->alive;
    }

    /**
     * @param bool $alive
     * @return ProtoJobServer
     */
    public function setAlive($alive)
    {
        \Assert\Assertion::boolean($alive);

        $this->alive = $alive;

        return $this;
    }
    /**
     * @return array<ProtoJobServerFun>
     */
    public function getProtoJobServerFunArr()
    {
        return $this->protoJobServerFunArr;
    }

    /**
     * @param array<ProtoJobServerFun> $protoJobServerFunArr
     * @return ProtoJobServer
     */
    public function setProtoJobServerFunArr(array $protoJobServerFunArr)
    {
        $this->protoJobServerFunArr = $protoJobServerFunArr;

        return $this;
    }
    /**
     * @return array
     */
    public function getWorkeInfoArr()
    {
        return $this->workeInfoArr;
    }

    /**
     * @param array $workeInfoArr
     * @return ProtoJobServer
     */
    public function setWorkeInfoArr(array $workeInfoArr)
    {
        $this->workeInfoArr = $workeInfoArr;

        return $this;
    }

}