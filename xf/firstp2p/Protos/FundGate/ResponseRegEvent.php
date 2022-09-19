<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 注册事件返回值
 *
 * 由代码生成器生成, 不可人为修改
 * @author jingxu
 */
class ResponseRegEvent extends ResponseBase
{
    /**
     * 存到数据库中的消息id
     *
     * @var int
     * @required
     */
    private $mqItemId;

    /**
     * @return int
     */
    public function getMqItemId()
    {
        return $this->mqItemId;
    }

    /**
     * @param int $mqItemId
     * @return ResponseRegEvent
     */
    public function setMqItemId($mqItemId)
    {
        \Assert\Assertion::integer($mqItemId);

        $this->mqItemId = $mqItemId;

        return $this;
    }

}