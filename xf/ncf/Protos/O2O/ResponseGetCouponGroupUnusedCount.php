<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use NCFGroup\Common\Extensions\Base\Pageable;
use Assert\Assertion;

/**
 * 获取指定券组列表的剩余库存
 *
 * 由代码生成器生成, 不可人为修改
 * @author yanbingrong <yanbingrong@ucfgroup.com>
 */
class ResponseGetCouponGroupUnusedCount extends AbstractRequestBase
{
    /**
     * 券组列表
     *
     * @var array
     * @required
     */
    private $list;

    /**
     * @return array
     */
    public function getList()
    {
        return $this->list;
    }

    /**
     * @param array $list
     * @return ResponseGetCouponGroupUnusedCount
     */
    public function setList(array $list)
    {
        $this->list = $list;

        return $this;
    }

}