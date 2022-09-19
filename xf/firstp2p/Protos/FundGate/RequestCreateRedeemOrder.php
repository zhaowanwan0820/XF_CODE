<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 创建基金赎回订单(只在本地生效)
 *
 * 由代码生成器生成, 不可人为修改
 * @author chengQ<qicheng@ucfgroup.com>
 */
class RequestCreateRedeemOrder extends AbstractRequestBase
{
    /**
     * 用户ID
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * 基金编码
     *
     * @var string
     * @required
     */
    private $fundCode;

    /**
     * 赎回份额
     *
     * @var float
     * @required
     */
    private $share;

    /**
     * 分站Id(默认为主站，值为1)
     *
     * @var int
     * @optional
     */
    private $siteId = 1;

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return RequestCreateRedeemOrder
     */
    public function setUserId($userId)
    {
        \Assert\Assertion::integer($userId);

        $this->userId = $userId;

        return $this;
    }
    /**
     * @return string
     */
    public function getFundCode()
    {
        return $this->fundCode;
    }

    /**
     * @param string $fundCode
     * @return RequestCreateRedeemOrder
     */
    public function setFundCode($fundCode)
    {
        \Assert\Assertion::string($fundCode);

        $this->fundCode = $fundCode;

        return $this;
    }
    /**
     * @return float
     */
    public function getShare()
    {
        return $this->share;
    }

    /**
     * @param float $share
     * @return RequestCreateRedeemOrder
     */
    public function setShare($share)
    {
        \Assert\Assertion::float($share);

        $this->share = $share;

        return $this;
    }
    /**
     * @return int
     */
    public function getSiteId()
    {
        return $this->siteId;
    }

    /**
     * @param int $siteId
     * @return RequestCreateRedeemOrder
     */
    public function setSiteId($siteId = 1)
    {
        $this->siteId = $siteId;

        return $this;
    }

}