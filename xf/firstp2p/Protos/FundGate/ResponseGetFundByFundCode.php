<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 获取fund信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author qicheng
 */
class ResponseGetFundByFundCode extends ResponseBase
{
    /**
     * 基金信息
     *
     * @var array
     * @required
     */
    private $fund;

    /**
     * 是否跳转到结束页面
     *
     * @var bool
     * @optional
     */
    private $shouldToOverAction = false;

    /**
     * @return array
     */
    public function getFund()
    {
        return $this->fund;
    }

    /**
     * @param array $fund
     * @return ResponseGetFundByFundCode
     */
    public function setFund(array $fund)
    {
        $this->fund = $fund;

        return $this;
    }
    /**
     * @return bool
     */
    public function getShouldToOverAction()
    {
        return $this->shouldToOverAction;
    }

    /**
     * @param bool $shouldToOverAction
     * @return ResponseGetFundByFundCode
     */
    public function setShouldToOverAction($shouldToOverAction = false)
    {
        $this->shouldToOverAction = $shouldToOverAction;

        return $this;
    }

}