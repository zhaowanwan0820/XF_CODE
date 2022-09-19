<?php
namespace NCFGroup\Protos\Stock;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 提交回访问卷信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author fupingzhou
 */
class RequestSubmitQuestionnaire extends AbstractRequestBase
{
    /**
     * 用户id
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * 回访问卷id
     *
     * @var int
     * @required
     */
    private $hfwjid;

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
     * @return RequestSubmitQuestionnaire
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
    public function getHfwjid()
    {
        return $this->hfwjid;
    }

    /**
     * @param int $hfwjid
     * @return RequestSubmitQuestionnaire
     */
    public function setHfwjid($hfwjid)
    {
        \Assert\Assertion::integer($hfwjid);

        $this->hfwjid = $hfwjid;

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
     * @return RequestSubmitQuestionnaire
     */
    public function setAnswer($answer)
    {
        \Assert\Assertion::string($answer);

        $this->answer = $answer;

        return $this;
    }

}