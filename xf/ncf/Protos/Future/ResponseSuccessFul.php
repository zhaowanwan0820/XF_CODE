<?php
namespace NCFGroup\Protos\Future;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 返回成功与否
 *
 * 由代码生成器生成, 不可人为修改
 * @author jingxu
 */
class ResponseSuccessFul extends AbstractRequestBase
{
    /**
     * 成功与否
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
     * @return ResponseSuccessFul
     */
    public function setSuccessFul($successFul)
    {
        \Assert\Assertion::boolean($successFul);

        $this->successFul = $successFul;

        return $this;
    }

}