<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;

/**
 * job server function
 *
 * 由代码生成器生成, 不可人为修改
 * @author jingxu
 */
class ProtoJobServerFun extends ProtoBufferBase
{
    /**
     * 方法名
     *
     * @var string
     * @required
     */
    private $functionName;

    /**
     * 等待执行的job数目
     *
     * @var int
     * @required
     */
    private $waitingJobCnt;

    /**
     * 正在执行的job数目
     *
     * @var int
     * @required
     */
    private $runingJobCnt;

    /**
     * worker进程数目
     *
     * @var int
     * @required
     */
    private $workerCnt;

    /**
     * @return string
     */
    public function getFunctionName()
    {
        return $this->functionName;
    }

    /**
     * @param string $functionName
     * @return ProtoJobServerFun
     */
    public function setFunctionName($functionName)
    {
        \Assert\Assertion::string($functionName);

        $this->functionName = $functionName;

        return $this;
    }
    /**
     * @return int
     */
    public function getWaitingJobCnt()
    {
        return $this->waitingJobCnt;
    }

    /**
     * @param int $waitingJobCnt
     * @return ProtoJobServerFun
     */
    public function setWaitingJobCnt($waitingJobCnt)
    {
        \Assert\Assertion::integer($waitingJobCnt);

        $this->waitingJobCnt = $waitingJobCnt;

        return $this;
    }
    /**
     * @return int
     */
    public function getRuningJobCnt()
    {
        return $this->runingJobCnt;
    }

    /**
     * @param int $runingJobCnt
     * @return ProtoJobServerFun
     */
    public function setRuningJobCnt($runingJobCnt)
    {
        \Assert\Assertion::integer($runingJobCnt);

        $this->runingJobCnt = $runingJobCnt;

        return $this;
    }
    /**
     * @return int
     */
    public function getWorkerCnt()
    {
        return $this->workerCnt;
    }

    /**
     * @param int $workerCnt
     * @return ProtoJobServerFun
     */
    public function setWorkerCnt($workerCnt)
    {
        \Assert\Assertion::integer($workerCnt);

        $this->workerCnt = $workerCnt;

        return $this;
    }

}