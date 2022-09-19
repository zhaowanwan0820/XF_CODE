<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use NCFGroup\Common\Extensions\Base\Pageable;
use Assert\Assertion;

/**
 * 获取商铺兑换记录列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author quanhengzhuang
 */
class RequestGetComfirmedCouponList extends AbstractRequestBase
{
    /**
     * 分页类
     *
     * @var \NCFGroup\Common\Extensions\Base\Pageable
     * @required
     */
    private $pageable;

    /**
     * 商店用户id
     *
     * @var int
     * @required
     */
    private $storeId;

    /**
     * @return \NCFGroup\Common\Extensions\Base\Pageable
     */
    public function getPageable()
    {
        return $this->pageable;
    }

    /**
     * @param \NCFGroup\Common\Extensions\Base\Pageable $pageable
     * @return RequestGetComfirmedCouponList
     */
    public function setPageable(\NCFGroup\Common\Extensions\Base\Pageable $pageable)
    {
        $this->pageable = $pageable;

        return $this;
    }
    /**
     * @return int
     */
    public function getStoreId()
    {
        return $this->storeId;
    }

    /**
     * @param int $storeId
     * @return RequestGetComfirmedCouponList
     */
    public function setStoreId($storeId)
    {
        \Assert\Assertion::integer($storeId);

        $this->storeId = $storeId;

        return $this;
    }

}