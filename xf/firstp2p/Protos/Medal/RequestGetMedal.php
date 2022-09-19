<?php
namespace NCFGroup\Protos\Medal;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 勋章信息获取接口
 *
 * 由代码生成器生成, 不可人为修改
 * @author luzhengshuai
 */
class RequestGetMedal extends ProtoBufferBase
{
    /**
     * medal Id
     *
     * @var integer
     * @required
     */
    private $medalId;

    /**
     * @return integer
     */
    public function getMedalId()
    {
        return $this->medalId;
    }

    /**
     * @param integer $medalId
     * @return RequestGetMedal
     */
    public function setMedalId($medalId)
    {
        \Assert\Assertion::integer($medalId);

        $this->medalId = $medalId;

        return $this;
    }

}