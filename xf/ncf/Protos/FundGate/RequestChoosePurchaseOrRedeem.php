<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 选择申购或赎回
 *
 * 由代码生成器生成, 不可人为修改
 * @author shiyan
 */
class RequestChoosePurchaseOrRedeem extends AbstractRequestBase
{
    /**
     * 用户Id
     *
     * @var string
     * @required
     */
    private $userId;

    /**
     * 分页
     *
     * @var \NCFGroup\Common\Extensions\Base\Pageable
     * @required
     */
    private $pageable;

    /**
     * 是否过滤（0表示不过滤，1表示过滤）
     *
     * @var bool
     * @required
     */
    private $isFilter;

    /**
     * @return string
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param string $userId
     * @return RequestChoosePurchaseOrRedeem
     */
    public function setUserId($userId)
    {
        \Assert\Assertion::string($userId);

        $this->userId = $userId;

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
     * @return RequestChoosePurchaseOrRedeem
     */
    public function setPageable(\NCFGroup\Common\Extensions\Base\Pageable $pageable)
    {
        $this->pageable = $pageable;

        return $this;
    }
    /**
     * @return bool
     */
    public function getIsFilter()
    {
        return $this->isFilter;
    }

    /**
     * @param bool $isFilter
     * @return RequestChoosePurchaseOrRedeem
     */
    public function setIsFilter($isFilter)
    {
        \Assert\Assertion::boolean($isFilter);

        $this->isFilter = $isFilter;

        return $this;
    }

}