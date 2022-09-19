<?php
namespace NCFGroup\Protos\Stock;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 提交证券交易所信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author zhangzuoyang
 */
class RequestSubmitStockInfo extends AbstractRequestBase
{
    /**
     * 证券上海
     *
     * @var int
     * @optional
     */
    private $stockSH = 0;

    /**
     * 证券深圳
     *
     * @var int
     * @optional
     */
    private $stockSZ = 0;

    /**
     * 基金上海
     *
     * @var int
     * @optional
     */
    private $fundSH = 0;

    /**
     * 基金深圳
     *
     * @var int
     * @optional
     */
    private $fundSZ = 0;

    /**
     * 签名
     *
     * @var string
     * @optional
     */
    private $fundSignId = ' ';

    /**
     * 签名
     *
     * @var string
     * @optional
     */
    private $entrustSignId = '';

    /**
     * 用户ID
     *
     * @var string
     * @required
     */
    private $userId;

    /**
     * @return int
     */
    public function getStockSH()
    {
        return $this->stockSH;
    }

    /**
     * @param int $stockSH
     * @return RequestSubmitStockInfo
     */
    public function setStockSH($stockSH = 0)
    {
        $this->stockSH = $stockSH;

        return $this;
    }
    /**
     * @return int
     */
    public function getStockSZ()
    {
        return $this->stockSZ;
    }

    /**
     * @param int $stockSZ
     * @return RequestSubmitStockInfo
     */
    public function setStockSZ($stockSZ = 0)
    {
        $this->stockSZ = $stockSZ;

        return $this;
    }
    /**
     * @return int
     */
    public function getFundSH()
    {
        return $this->fundSH;
    }

    /**
     * @param int $fundSH
     * @return RequestSubmitStockInfo
     */
    public function setFundSH($fundSH = 0)
    {
        $this->fundSH = $fundSH;

        return $this;
    }
    /**
     * @return int
     */
    public function getFundSZ()
    {
        return $this->fundSZ;
    }

    /**
     * @param int $fundSZ
     * @return RequestSubmitStockInfo
     */
    public function setFundSZ($fundSZ = 0)
    {
        $this->fundSZ = $fundSZ;

        return $this;
    }
    /**
     * @return string
     */
    public function getFundSignId()
    {
        return $this->fundSignId;
    }

    /**
     * @param string $fundSignId
     * @return RequestSubmitStockInfo
     */
    public function setFundSignId($fundSignId = ' ')
    {
        $this->fundSignId = $fundSignId;

        return $this;
    }
    /**
     * @return string
     */
    public function getEntrustSignId()
    {
        return $this->entrustSignId;
    }

    /**
     * @param string $entrustSignId
     * @return RequestSubmitStockInfo
     */
    public function setEntrustSignId($entrustSignId = '')
    {
        $this->entrustSignId = $entrustSignId;

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
     * @return RequestSubmitStockInfo
     */
    public function setUserId($userId)
    {
        \Assert\Assertion::string($userId);

        $this->userId = $userId;

        return $this;
    }

}