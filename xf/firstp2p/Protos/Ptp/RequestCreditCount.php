<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 中国信贷注册统计
 *
 * 由代码生成器生成, 不可人为修改
 * @author yangqing
 */
class RequestCreditCount extends ProtoBufferBase
{
    /**
     * 统计起始时间
     *
     * @var int
     * @required
     */
    private $time;

    /**
     * @return int
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * @param int $time
     * @return RequestCreditCount
     */
    public function setTime($time)
    {
        \Assert\Assertion::integer($time);

        $this->time = $time;

        return $this;
    }

}