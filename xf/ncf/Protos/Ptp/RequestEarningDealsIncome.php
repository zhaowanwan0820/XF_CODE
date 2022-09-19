<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 获取首页收益数据
 *
 * 由代码生成器生成, 不可人为修改
 * @author zhangzhuyan
 */
class RequestEarningDealsIncome extends AbstractRequestBase
{
    /**
     * 是否显示全部数据
     *
     * @var bool
     * @required
     */
    private $isShowAll;

    /**
     * @return bool
     */
    public function getIsShowAll()
    {
        return $this->isShowAll;
    }

    /**
     * @param bool $isShowAll
     * @return RequestEarningDealsIncome
     */
    public function setIsShowAll($isShowAll)
    {
        \Assert\Assertion::boolean($isShowAll);

        $this->isShowAll = $isShowAll;

        return $this;
    }

}