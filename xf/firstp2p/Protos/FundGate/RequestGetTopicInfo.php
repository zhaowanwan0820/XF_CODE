<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 获取基金专题信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author fupingzhou
 */
class RequestGetTopicInfo extends AbstractRequestBase
{
    /**
     * 专题id
     *
     * @var int
     * @required
     */
    private $id;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return RequestGetTopicInfo
     */
    public function setId($id)
    {
        \Assert\Assertion::integer($id);

        $this->id = $id;

        return $this;
    }

}