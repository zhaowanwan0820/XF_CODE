<?php
namespace NCFGroup\Protos\Stock;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 获取回访问卷信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author fupingzhou
 */
class ResponseGetQuestionnaire extends ResponseBase
{
    /**
     * 回访问卷id
     *
     * @var string
     * @required
     */
    private $hfwjid;

    /**
     * 问题列表
     *
     * @var array
     * @required
     */
    private $questionList;

    /**
     * @return string
     */
    public function getHfwjid()
    {
        return $this->hfwjid;
    }

    /**
     * @param string $hfwjid
     * @return ResponseGetQuestionnaire
     */
    public function setHfwjid($hfwjid)
    {
        \Assert\Assertion::string($hfwjid);

        $this->hfwjid = $hfwjid;

        return $this;
    }
    /**
     * @return array
     */
    public function getQuestionList()
    {
        return $this->questionList;
    }

    /**
     * @param array $questionList
     * @return ResponseGetQuestionnaire
     */
    public function setQuestionList(array $questionList)
    {
        $this->questionList = $questionList;

        return $this;
    }

}