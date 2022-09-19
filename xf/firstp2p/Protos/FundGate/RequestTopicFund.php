<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 传输topicFund相关数据
 *
 * 由代码生成器生成, 不可人为修改
 * @author libing
 */
class RequestTopicFund extends AbstractRequestBase
{
    /**
     * topicFund默认自增id
     *
     * @var string
     * @required
     */
    private $id;

    /**
     * 基金代码
     *
     * @var string
     * @required
     */
    private $fundCode;

    /**
     * 专题id
     *
     * @var string
     * @required
     */
    private $topicId;

    /**
     * 收益周期
     *
     * @var string
     * @required
     */
    private $cycle;

    /**
     * 卖点
     *
     * @var string
     * @required
     */
    private $usp;

    /**
     * 排序
     *
     * @var string
     * @required
     */
    private $seqNo;

    /**
     * 是否上线
     *
     * @var string
     * @required
     */
    private $isDelete;

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     * @return RequestTopicFund
     */
    public function setId($id)
    {
        \Assert\Assertion::string($id);

        $this->id = $id;

        return $this;
    }
    /**
     * @return string
     */
    public function getFundCode()
    {
        return $this->fundCode;
    }

    /**
     * @param string $fundCode
     * @return RequestTopicFund
     */
    public function setFundCode($fundCode)
    {
        \Assert\Assertion::string($fundCode);

        $this->fundCode = $fundCode;

        return $this;
    }
    /**
     * @return string
     */
    public function getTopicId()
    {
        return $this->topicId;
    }

    /**
     * @param string $topicId
     * @return RequestTopicFund
     */
    public function setTopicId($topicId)
    {
        \Assert\Assertion::string($topicId);

        $this->topicId = $topicId;

        return $this;
    }
    /**
     * @return string
     */
    public function getCycle()
    {
        return $this->cycle;
    }

    /**
     * @param string $cycle
     * @return RequestTopicFund
     */
    public function setCycle($cycle)
    {
        \Assert\Assertion::string($cycle);

        $this->cycle = $cycle;

        return $this;
    }
    /**
     * @return string
     */
    public function getUsp()
    {
        return $this->usp;
    }

    /**
     * @param string $usp
     * @return RequestTopicFund
     */
    public function setUsp($usp)
    {
        \Assert\Assertion::string($usp);

        $this->usp = $usp;

        return $this;
    }
    /**
     * @return string
     */
    public function getSeqNo()
    {
        return $this->seqNo;
    }

    /**
     * @param string $seqNo
     * @return RequestTopicFund
     */
    public function setSeqNo($seqNo)
    {
        \Assert\Assertion::string($seqNo);

        $this->seqNo = $seqNo;

        return $this;
    }
    /**
     * @return string
     */
    public function getIsDelete()
    {
        return $this->isDelete;
    }

    /**
     * @param string $isDelete
     * @return RequestTopicFund
     */
    public function setIsDelete($isDelete)
    {
        \Assert\Assertion::string($isDelete);

        $this->isDelete = $isDelete;

        return $this;
    }

}