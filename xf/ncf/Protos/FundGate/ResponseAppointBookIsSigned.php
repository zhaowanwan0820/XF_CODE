<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * sigh返回
 *
 * 由代码生成器生成, 不可人为修改
 * @author jingxu
 */
class ResponseAppointBookIsSigned extends ResponseBase
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
     * @return ResponseAppointBookIsSigned
     */
    public function setSuccessFul($successFul)
    {
        \Assert\Assertion::boolean($successFul);

        $this->successFul = $successFul;

        return $this;
    }

}