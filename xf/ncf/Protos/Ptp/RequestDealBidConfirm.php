<?php
namespace NCFGroup\Protos\Ptp;;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 投资确认
 *
 * 由代码生成器生成, 不可人为修改
 * @author xiaoan
 */
class RequestDealBidConfirm extends AbstractRequestBase
{
    /**
     * 标id
     *
     * @var int
     * @optional
     */
    private $id = 0;

    /**
     * 投资金额
     *
     * @var string
     * @required
     */
    private $money;

    /**
     * 用户id
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * 用户余额
     *
     * @var string
     * @required
     */
    private $userMoney;

    /**
     * 优惠码
     *
     * @var string
     * @required
     */
    private $code;

    /**
     * 分站ID
     *
     * @var string
     * @optional
     */
    private $siteId = '1';

    /**
     * 加密标id
     *
     * @var string
     * @optional
     */
    private $ecid = '';

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return RequestDealBidConfirm
     */
    public function setId($id = 0)
    {
        $this->id = $id;

        return $this;
    }
    /**
     * @return string
     */
    public function getMoney()
    {
        return $this->money;
    }

    /**
     * @param string $money
     * @return RequestDealBidConfirm
     */
    public function setMoney($money)
    {
        \Assert\Assertion::string($money);

        $this->money = $money;

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
     * @return RequestDealBidConfirm
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
    public function getUserMoney()
    {
        return $this->userMoney;
    }

    /**
     * @param string $userMoney
     * @return RequestDealBidConfirm
     */
    public function setUserMoney($userMoney)
    {
        \Assert\Assertion::string($userMoney);

        $this->userMoney = $userMoney;

        return $this;
    }
    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $code
     * @return RequestDealBidConfirm
     */
    public function setCode($code)
    {
        \Assert\Assertion::string($code);

        $this->code = $code;

        return $this;
    }
    /**
     * @return string
     */
    public function getSiteId()
    {
        return $this->siteId;
    }

    /**
     * @param string $siteId
     * @return RequestDealBidConfirm
     */
    public function setSiteId($siteId = '1')
    {
        $this->siteId = $siteId;

        return $this;
    }
    /**
     * @return string
     */
    public function getEcid()
    {
        return $this->ecid;
    }

    /**
     * @param string $ecid
     * @return RequestDealBidConfirm
     */
    public function setEcid($ecid = '')
    {
        $this->ecid = $ecid;

        return $this;
    }

}