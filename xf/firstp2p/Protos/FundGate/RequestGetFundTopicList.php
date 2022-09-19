<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use NCFGroup\Common\Extensions\Base\Pageable;

/**
 * 获取基金专题列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author sunqing
 */
class RequestGetFundTopicList extends AbstractRequestBase
{
    /**
     * 分页
     *
     * @var \NCFGroup\Common\Extensions\Base\Pageable
     * @required
     */
    private $pageable;

    /**
     * 专题名称
     *
     * @var string
     * @optional
     */
    private $topicName = '‘’';

    /**
     * 专题是否上线
     *
     * @var int
     * @optional
     */
    private $status = -1;

    /**
     * @return \NCFGroup\Common\Extensions\Base\Pageable
     */
    public function getPageable()
    {
        return $this->pageable;
    }

    /**
     * @param \NCFGroup\Common\Extensions\Base\Pageable $pageable
     * @return RequestGetFundTopicList
     */
    public function setPageable(\NCFGroup\Common\Extensions\Base\Pageable $pageable)
    {
        $this->pageable = $pageable;

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
     * @return RequestGetFundTopicList
     */
    public function setTopicName($topicName = '‘’')
    {
        $this->topicName = $topicName;

        return $this;
    }
    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param int $status
     * @return RequestGetFundTopicList
     */
    public function setStatus($status = -1)
    {
        $this->status = $status;

        return $this;
    }

}