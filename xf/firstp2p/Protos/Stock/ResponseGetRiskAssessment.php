<?php
namespace NCFGroup\Protos\Stock;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 获取风险评测信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author fupingzhou
 */
class ResponseGetRiskAssessment extends ResponseBase
{
    /**
     * 问卷id
     *
     * @var string
     * @required
     */
    private $wjid;

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
    public function getWjid()
    {
        return $this->wjid;
    }

    /**
     * @param string $wjid
     * @return ResponseGetRiskAssessment
     */
    public function setWjid($wjid)
    {
        \Assert\Assertion::string($wjid);

        $this->wjid = $wjid;

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
     * @return ResponseGetRiskAssessment
     */
    public function setQuestionList(array $questionList)
    {
        $this->questionList = $questionList;

        return $this;
    }

}