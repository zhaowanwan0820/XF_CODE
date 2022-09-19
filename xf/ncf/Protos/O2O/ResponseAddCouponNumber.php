<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 导入券码返回
 *
 * 由代码生成器生成, 不可人为修改
 * @author yanbingrong <yanbingrong@ucfgroup.com>
 */
class ResponseAddCouponNumber extends ResponseBase
{
    /**
     * 成功添加的个数
     *
     * @var int
     * @required
     */
    private $count;

    /**
     * @return int
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * @param int $count
     * @return ResponseAddCouponNumber
     */
    public function setCount($count)
    {
        \Assert\Assertion::integer($count);

        $this->count = $count;

        return $this;
    }

}