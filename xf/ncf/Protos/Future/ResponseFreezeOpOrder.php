<?php
namespace NCFGroup\Protos\Future;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 冻结op订单返回
 *
 * 由代码生成器生成, 不可人为修改
 * @author jingxu
 */
class ResponseFreezeOpOrder extends ResponseBase
{
    /**
     * 冻结成功与否
     *
     * @var bool
     * @required
     */
    private $successFul;

    /**
     * @return bool
     */
    public function getSuccessFul()
    {
        return $this->successFul;
    }

    /**
     * @param bool $successFul
     * @return ResponseFreezeOpOrder
     */
    public function setSuccessFul($successFul)
    {
        \Assert\Assertion::boolean($successFul);

        $this->successFul = $successFul;

        return $this;
    }

}