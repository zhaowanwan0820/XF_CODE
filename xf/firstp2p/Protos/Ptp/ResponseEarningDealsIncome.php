<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ResponseBase;
use Assert\Assertion;

/**
 * 首页收益数据
 *
 * 由代码生成器生成, 不可人为修改
 * @author zhangzhuyan
 */
class ResponseEarningDealsIncome extends ResponseBase
{
    /**
     * 收益详情
     *
     * @var array
     * @required
     */
    private $income;

    /**
     * @return array
     */
    public function getIncome()
    {
        return $this->income;
    }

    /**
     * @param array $income
     * @return ResponseEarningDealsIncome
     */
    public function setIncome(array $income)
    {
        $this->income = $income;

        return $this;
    }

}