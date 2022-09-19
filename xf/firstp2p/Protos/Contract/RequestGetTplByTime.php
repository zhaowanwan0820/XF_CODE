<?php
namespace NCFGroup\Protos\Contract;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 按照智多鑫前缀获取最新的合同模板
 *
 * 由代码生成器生成, 不可人为修改
 * @author duxuefeng
 */
class RequestGetTplByTime extends ProtoBufferBase
{
    /**
     * 标的ID
     *
     * @var int
     * @required
     */
    private $dealId;

    /**
     * 模板标记前缀
     *
     * @var string
     * @required
     */
    private $tplPrefix;

    /**
     * 类型(0:无;1:多投宝)
     *
     * @var int
     * @optional
     */
    private $type = 1;

    /**
     * 用户投资时间
     *
     * @var int
     * @required
     */
    private $time;

    /**
     * @return int
     */
    public function getDealId()
    {
        return $this->dealId;
    }

    /**
     * @param int $dealId
     * @return RequestGetTplByTime
     */
    public function setDealId($dealId)
    {
        \Assert\Assertion::integer($dealId);

        $this->dealId = $dealId;

        return $this;
    }
    /**
     * @return string
     */
    public function getTplPrefix()
    {
        return $this->tplPrefix;
    }

    /**
     * @param string $tplPrefix
     * @return RequestGetTplByTime
     */
    public function setTplPrefix($tplPrefix)
    {
        \Assert\Assertion::string($tplPrefix);

        $this->tplPrefix = $tplPrefix;

        return $this;
    }
    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     * @return RequestGetTplByTime
     */
    public function setType($type = 1)
    {
        $this->type = $type;

        return $this;
    }
    /**
     * @return int
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * @param int $time
     * @return RequestGetTplByTime
     */
    public function setTime($time)
    {
        \Assert\Assertion::integer($time);

        $this->time = $time;

        return $this;
    }

}