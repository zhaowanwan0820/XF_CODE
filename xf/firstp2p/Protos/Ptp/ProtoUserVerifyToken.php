<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 验证token
 *
 * 由代码生成器生成, 不可人为修改
 * @author yutao
 */
class ProtoUserVerifyToken extends ProtoBufferBase
{
    /**
     * 是否有效
     *
     * @var bool
     * @required
     */
    private $is_effect;

    /**
     * @return bool
     */
    public function getIs_effect()
    {
        return $this->is_effect;
    }

    /**
     * @param bool $is_effect
     * @return ProtoUserVerifyToken
     */
    public function setIs_effect($is_effect)
    {
        \Assert\Assertion::boolean($is_effect);

        $this->is_effect = $is_effect;

        return $this;
    }

}