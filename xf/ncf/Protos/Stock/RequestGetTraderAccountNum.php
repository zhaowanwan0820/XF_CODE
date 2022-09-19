<?php
namespace NCFGroup\Protos\Stock;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 获取券商开户各个阶段数量统计
 *
 * 由代码生成器生成, 不可人为修改
 * @author libing
 */
class RequestGetTraderAccountNum extends AbstractRequestBase
{
    /**
     * 券商来源
     *
     * @var string
     * @required
     */
    private $source;

    /**
     * 券商名称缩写
     *
     * @var string
     * @required
     */
    private $briefName;

    /**
     * 创建时间起始
     *
     * @var string
     * @required
     */
    private $startCtime;

    /**
     * 创建时间截止
     *
     * @var string
     * @required
     */
    private $endCtime;

    /**
     * 修改时间起始
     *
     * @var string
     * @required
     */
    private $startMtime;

    /**
     * 修改时间截止
     *
     * @var string
     * @required
     */
    private $endMtime;

    /**
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param string $source
     * @return RequestGetTraderAccountNum
     */
    public function setSource($source)
    {
        \Assert\Assertion::string($source);

        $this->source = $source;

        return $this;
    }
    /**
     * @return string
     */
    public function getBriefName()
    {
        return $this->briefName;
    }

    /**
     * @param string $briefName
     * @return RequestGetTraderAccountNum
     */
    public function setBriefName($briefName)
    {
        \Assert\Assertion::string($briefName);

        $this->briefName = $briefName;

        return $this;
    }
    /**
     * @return string
     */
    public function getStartCtime()
    {
        return $this->startCtime;
    }

    /**
     * @param string $startCtime
     * @return RequestGetTraderAccountNum
     */
    public function setStartCtime($startCtime)
    {
        \Assert\Assertion::string($startCtime);

        $this->startCtime = $startCtime;

        return $this;
    }
    /**
     * @return string
     */
    public function getEndCtime()
    {
        return $this->endCtime;
    }

    /**
     * @param string $endCtime
     * @return RequestGetTraderAccountNum
     */
    public function setEndCtime($endCtime)
    {
        \Assert\Assertion::string($endCtime);

        $this->endCtime = $endCtime;

        return $this;
    }
    /**
     * @return string
     */
    public function getStartMtime()
    {
        return $this->startMtime;
    }

    /**
     * @param string $startMtime
     * @return RequestGetTraderAccountNum
     */
    public function setStartMtime($startMtime)
    {
        \Assert\Assertion::string($startMtime);

        $this->startMtime = $startMtime;

        return $this;
    }
    /**
     * @return string
     */
    public function getEndMtime()
    {
        return $this->endMtime;
    }

    /**
     * @param string $endMtime
     * @return RequestGetTraderAccountNum
     */
    public function setEndMtime($endMtime)
    {
        \Assert\Assertion::string($endMtime);

        $this->endMtime = $endMtime;

        return $this;
    }

}