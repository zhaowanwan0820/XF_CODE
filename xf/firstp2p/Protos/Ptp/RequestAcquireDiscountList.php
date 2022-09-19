<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 同步或异步领取多张投资券
 *
 * 由代码生成器生成, 不可人为修改
 * @author yanbingrong<yanbingrong@ucfgroup.com>
 */
class RequestAcquireDiscountList extends AbstractRequestBase
{
    /**
     * 用户ID
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * 券组ID，多个用逗号分隔
     *
     * @var string
     * @required
     */
    private $discountGroupIds;

    /**
     * 券码唯一token
     *
     * @var string
     * @required
     */
    private $token;

    /**
     * 领取备注
     *
     * @var string
     * @optional
     */
    private $remark = '';

    /**
     * 交易id
     *
     * @var int
     * @optional
     */
    private $dealLoadId = 0;

    /**
     * 是否同步请求，默认异步请求
     *
     * @var int
     * @optional
     */
    private $isSync = 0;

    /**
     * 返利金额，覆盖投资券的金额配置
     *
     * @var float
     * @optional
     */
    private $rebateAmount = 0;

    /**
     * 返利期限，覆盖投资券的期限配置
     *
     * @var int
     * @optional
     */
    private $rebateLimit = 0;

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return RequestAcquireDiscountList
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
    public function getDiscountGroupIds()
    {
        return $this->discountGroupIds;
    }

    /**
     * @param string $discountGroupIds
     * @return RequestAcquireDiscountList
     */
    public function setDiscountGroupIds($discountGroupIds)
    {
        \Assert\Assertion::string($discountGroupIds);

        $this->discountGroupIds = $discountGroupIds;

        return $this;
    }
    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param string $token
     * @return RequestAcquireDiscountList
     */
    public function setToken($token)
    {
        \Assert\Assertion::string($token);

        $this->token = $token;

        return $this;
    }
    /**
     * @return string
     */
    public function getRemark()
    {
        return $this->remark;
    }

    /**
     * @param string $remark
     * @return RequestAcquireDiscountList
     */
    public function setRemark($remark = '')
    {
        $this->remark = $remark;

        return $this;
    }
    /**
     * @return int
     */
    public function getDealLoadId()
    {
        return $this->dealLoadId;
    }

    /**
     * @param int $dealLoadId
     * @return RequestAcquireDiscountList
     */
    public function setDealLoadId($dealLoadId = 0)
    {
        $this->dealLoadId = $dealLoadId;

        return $this;
    }
    /**
     * @return int
     */
    public function getIsSync()
    {
        return $this->isSync;
    }

    /**
     * @param int $isSync
     * @return RequestAcquireDiscountList
     */
    public function setIsSync($isSync = 0)
    {
        $this->isSync = $isSync;

        return $this;
    }
    /**
     * @return float
     */
    public function getRebateAmount()
    {
        return $this->rebateAmount;
    }

    /**
     * @param float $rebateAmount
     * @return RequestAcquireDiscountList
     */
    public function setRebateAmount($rebateAmount = 0)
    {
        $this->rebateAmount = $rebateAmount;

        return $this;
    }
    /**
     * @return int
     */
    public function getRebateLimit()
    {
        return $this->rebateLimit;
    }

    /**
     * @param int $rebateLimit
     * @return RequestAcquireDiscountList
     */
    public function setRebateLimit($rebateLimit = 0)
    {
        $this->rebateLimit = $rebateLimit;

        return $this;
    }

}