<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 传输FundTopic相关数据
 *
 * 由代码生成器生成, 不可人为修改
 * @author libing
 */
class RequestFundTopic extends AbstractRequestBase
{
    /**
     * FundTopic自增主键
     *
     * @var string
     * @required
     */
    private $id;

    /**
     * 专题名称
     *
     * @var string
     * @required
     */
    private $topicName;

    /**
     * 专题banner
     *
     * @var string
     * @required
     */
    private $topicBanner;

    /**
     * 专题简介
     *
     * @var string
     * @required
     */
    private $introduction;

    /**
     * 是否上线
     *
     * @var string
     * @required
     */
    private $status;

    /**
     * 专题类型
     *
     * @var string
     * @required
     */
    private $topicType;

    /**
     * 排序
     *
     * @var string
     * @required
     */
    private $seqNo;

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     * @return RequestFundTopic
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
    public function getTopicName()
    {
        return $this->topicName;
    }

    /**
     * @param string $topicName
     * @return RequestFundTopic
     */
    public function setTopicName($topicName)
    {
        \Assert\Assertion::string($topicName);

        $this->topicName = $topicName;

        return $this;
    }
    /**
     * @return string
     */
    public function getTopicBanner()
    {
        return $this->topicBanner;
    }

    /**
     * @param string $topicBanner
     * @return RequestFundTopic
     */
    public function setTopicBanner($topicBanner)
    {
        \Assert\Assertion::string($topicBanner);

        $this->topicBanner = $topicBanner;

        return $this;
    }
    /**
     * @return string
     */
    public function getIntroduction()
    {
        return $this->introduction;
    }

    /**
     * @param string $introduction
     * @return RequestFundTopic
     */
    public function setIntroduction($introduction)
    {
        \Assert\Assertion::string($introduction);

        $this->introduction = $introduction;

        return $this;
    }
    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     * @return RequestFundTopic
     */
    public function setStatus($status)
    {
        \Assert\Assertion::string($status);

        $this->status = $status;

        return $this;
    }
    /**
     * @return string
     */
    public function getTopicType()
    {
        return $this->topicType;
    }

    /**
     * @param string $topicType
     * @return RequestFundTopic
     */
    public function setTopicType($topicType)
    {
        \Assert\Assertion::string($topicType);

        $this->topicType = $topicType;

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
     * @return RequestFundTopic
     */
    public function setSeqNo($seqNo)
    {
        \Assert\Assertion::string($seqNo);

        $this->seqNo = $seqNo;

        return $this;
    }

}