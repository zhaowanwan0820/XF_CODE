<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 多投向p2p投资service
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangchuanlu
 */
class RequestDtBid extends AbstractRequestBase
{
    /**
     * token
     *
     * @var string
     * @required
     */
    private $token;

    /**
     * 投资的p2pDealId
     *
     * @var int
     * @required
     */
    private $p2pDealId;

    /**
     * 用户ID
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * 赎回用户ID
     *
     * @var int
     * @optional
     */
    private $redeemUserId = 0;

    /**
     * 转账金额
     *
     * @var float
     * @required
     */
    private $money;

    /**
     * 透传参数
     *
     * @var array
     * @optional
     */
    private $transParams = NULL;

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param string $token
     * @return RequestDtBid
     */
    public function setToken($token)
    {
        \Assert\Assertion::string($token);

        $this->token = $token;

        return $this;
    }
    /**
     * @return int
     */
    public function getP2pDealId()
    {
        return $this->p2pDealId;
    }

    /**
     * @param int $p2pDealId
     * @return RequestDtBid
     */
    public function setP2pDealId($p2pDealId)
    {
        \Assert\Assertion::integer($p2pDealId);

        $this->p2pDealId = $p2pDealId;

        return $this;
    }
    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return RequestDtBid
     */
    public function setUserId($userId)
    {
        \Assert\Assertion::integer($userId);

        $this->userId = $userId;

        return $this;
    }
    /**
     * @return int
     */
    public function getRedeemUserId()
    {
        return $this->redeemUserId;
    }

    /**
     * @param int $redeemUserId
     * @return RequestDtBid
     */
    public function setRedeemUserId($redeemUserId = 0)
    {
        $this->redeemUserId = $redeemUserId;

        return $this;
    }
    /**
     * @return float
     */
    public function getMoney()
    {
        return $this->money;
    }

    /**
     * @param float $money
     * @return RequestDtBid
     */
    public function setMoney($money)
    {
        \Assert\Assertion::float($money);

        $this->money = $money;

        return $this;
    }
    /**
     * @return array
     */
    public function getTransParams()
    {
        return $this->transParams;
    }

    /**
     * @param array $transParams
     * @return RequestDtBid
     */
    public function setTransParams(array $transParams = NULL)
    {
        $this->transParams = $transParams;

        return $this;
    }

}