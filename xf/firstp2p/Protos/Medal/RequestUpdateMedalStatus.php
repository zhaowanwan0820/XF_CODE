<?php
namespace NCFGroup\Protos\Medal;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 更新勋章有效状态
 *
 * 由代码生成器生成, 不可人为修改
 * @author luzhengshuai
 */
class RequestUpdateMedalStatus extends ProtoBufferBase
{
    /**
     * 勋章ID
     *
     * @var integer
     * @required
     */
    private $medalId;

    /**
     * 是否有效
     *
     * @var integer
     * @required
     */
    private $isEffective;

    /**
     * @return integer
     */
    public function getMedalId()
    {
        return $this->medalId;
    }

    /**
     * @param integer $medalId
     * @return RequestUpdateMedalStatus
     */
    public function setMedalId($medalId)
    {
        \Assert\Assertion::integer($medalId);

        $this->medalId = $medalId;

        return $this;
    }
    /**
     * @return integer
     */
    public function getIsEffective()
    {
        return $this->isEffective;
    }

    /**
     * @param integer $isEffective
     * @return RequestUpdateMedalStatus
     */
    public function setIsEffective($isEffective)
    {
        \Assert\Assertion::integer($isEffective);

        $this->isEffective = $isEffective;

        return $this;
    }

}