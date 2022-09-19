<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use NCFGroup\Common\Extensions\Base\Pageable;
use Assert\Assertion;

/**
 * 根据触发规则获取券组列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author Yu Tao <yutao@ucfgroup.com>
 */
class ResponseGetCouponGroupListByTrigger extends AbstractRequestBase
{
    /**
     * 券组入口过期时间
     *
     * @var int
     * @required
     */
    private $expireTime;

    /**
     * 券组列表
     *
     * @var array
     * @required
     */
    private $list;

    /**
     * @return int
     */
    public function getExpireTime()
    {
        return $this->expireTime;
    }

    /**
     * @param int $expireTime
     * @return ResponseGetCouponGroupListByTrigger
     */
    public function setExpireTime($expireTime)
    {
        \Assert\Assertion::integer($expireTime);

        $this->expireTime = $expireTime;

        return $this;
    }
    /**
     * @return array
     */
    public function getList()
    {
        return $this->list;
    }

    /**
     * @param array $list
     * @return ResponseGetCouponGroupListByTrigger
     */
    public function setList(array $list)
    {
        $this->list = $list;

        return $this;
    }

}