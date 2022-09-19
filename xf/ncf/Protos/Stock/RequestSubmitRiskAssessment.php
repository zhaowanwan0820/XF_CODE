<?php
namespace NCFGroup\Protos\Stock;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 提交风险评测信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author fupingzhou
 */
class RequestSubmitRiskAssessment extends AbstractRequestBase
{
    /**
     * 用户id
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * 问卷id
     *
     * @var int
     * @required
     */
    private $wjid;

    /**
     * 答案
     *
     * @var string
     * @required
     */
    private $answer;

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return RequestSubmitRiskAssessment
     */
    public function setUserId($userId)
    {
        \Assert\Assertion::integer($userId);

        $this->userId = $userId;

        return $this;
    }
    /**
     * @return int
     */
    public function getWjid()
    {
        return $this->wjid;
    }

    /**
     * @param int $wjid
     * @return RequestSubmitRiskAssessment
     */
    public function setWjid($wjid)
    {
        \Assert\Assertion::integer($wjid);

        $this->wjid = $wjid;

        return $this;
    }
    /**
     * @return string
     */
    public function getAnswer()
    {
        return $this->answer;
    }

    /**
     * @param string $answer
     * @return RequestSubmitRiskAssessment
     */
    public function setAnswer($answer)
    {
        \Assert\Assertion::string($answer);

        $this->answer = $answer;

        return $this;
    }

}