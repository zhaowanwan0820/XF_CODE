<?php
namespace NCFGroup\Protos\Future;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 获取追加保证金信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author zhangzuoyang
 */
class RequestAddBandInfo extends AbstractRequestBase
{
    /**
     * 合同ID
     *
     * @var string
     * @required
     */
    private $orderNo;

    /**
     * 用户ID
     *
     * @var string
     * @required
     */
    private $userId;

    /**
     * @return string
     */
    public function getOrderNo()
    {
        return $this->orderNo;
    }

    /**
     * @param string $orderNo
     * @return RequestAddBandInfo
     */
    public function setOrderNo($orderNo)
    {
        \Assert\Assertion::string($orderNo);

        $this->orderNo = $orderNo;

        return $this;
    }
    /**
     * @return string
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param string $userId
     * @return RequestAddBandInfo
     */
    public function setUserId($userId)
    {
        \Assert\Assertion::string($userId);

        $this->userId = $userId;

        return $this;
    }

}