<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ResponseBase;
use Assert\Assertion;

/**
 * 获取用户信息接口
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiansong
 */
class ResponseGetLoginUser extends ResponseBase
{
    /**
     * 用户信息
     *
     * @var array
     * @required
     */
    private $user;

    /**
     * 基金户总资金
     *
     * @var string
     * @required
     */
    private $totalFundAmount;

    /**
     * 契约基金户总资金
     *
     * @var string
     * @required
     */
    private $totalSpecialFundAmount;

    /**
     * @return array
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param array $user
     * @return ResponseGetLoginUser
     */
    public function setUser(array $user)
    {
        $this->user = $user;

        return $this;
    }
    /**
     * @return string
     */
    public function getTotalFundAmount()
    {
        return $this->totalFundAmount;
    }

    /**
     * @param string $totalFundAmount
     * @return ResponseGetLoginUser
     */
    public function setTotalFundAmount($totalFundAmount)
    {
        \Assert\Assertion::string($totalFundAmount);

        $this->totalFundAmount = $totalFundAmount;

        return $this;
    }
    /**
     * @return string
     */
    public function getTotalSpecialFundAmount()
    {
        return $this->totalSpecialFundAmount;
    }

    /**
     * @param string $totalSpecialFundAmount
     * @return ResponseGetLoginUser
     */
    public function setTotalSpecialFundAmount($totalSpecialFundAmount)
    {
        \Assert\Assertion::string($totalSpecialFundAmount);

        $this->totalSpecialFundAmount = $totalSpecialFundAmount;

        return $this;
    }

}