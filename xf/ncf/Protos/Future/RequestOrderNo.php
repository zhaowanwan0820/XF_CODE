<?php
namespace NCFGroup\Protos\Future;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 合约单号
 *
 * 由代码生成器生成, 不可人为修改
 * @author jingxu
 */
class RequestOrderNo extends AbstractRequestBase
{
    /**
     * 合约单号
     *
     * @var string
     * @required
     */
    private $orderNo;

    /**
     * 分页
     *
     * @var \NCFGroup\Common\Extensions\Base\Pageable
     * @optional
     */
    private $pageable = NULL;

    /**
     * @return string
     */
    public function getOrderNo()
    {
        return $this->orderNo;
    }

    /**
     * @param string $orderNo
     * @return RequestOrderNo
     */
    public function setOrderNo($orderNo)
    {
        \Assert\Assertion::string($orderNo);

        $this->orderNo = $orderNo;

        return $this;
    }
    /**
     * @return \NCFGroup\Common\Extensions\Base\Pageable
     */
    public function getPageable()
    {
        return $this->pageable;
    }

    /**
     * @param \NCFGroup\Common\Extensions\Base\Pageable $pageable
     * @return RequestOrderNo
     */
    public function setPageable(\NCFGroup\Common\Extensions\Base\Pageable $pageable = NULL)
    {
        $this->pageable = $pageable;

        return $this;
    }

}