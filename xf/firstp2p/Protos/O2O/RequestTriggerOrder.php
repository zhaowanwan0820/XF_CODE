<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use NCFGroup\Common\Extensions\Base\Pageable;
use Assert\Assertion;

/**
 * 生成触发订单
 *
 * 由代码生成器生成, 不可人为修改
 * @author yanbingrong <yanbingrong@ucfgroup.com>
 */
class RequestTriggerOrder extends AbstractRequestBase
{
    /**
     * 用户id
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * 触发
     *
     * @var int
     * @required
     */
    private $triggerMode;

    /**
     * 交易类型,1为p2p,2为智多鑫,4为黄金,7为随心约
     *
     * @var int
     * @required
     */
    private $dealType;

    /**
     * 交易id
     *
     * @var int
     * @required
     */
    private $dealLoadId;

    /**
     * 过期时间
     *
     * @var int
     * @required
     */
    private $expireTime;

    /**
     * 唯一码
     *
     * @var string
     * @required
     */
    private $token;

    /**
     * 额外信息
     *
     * @var array
     * @optional
     */
    private $extra = NULL;

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return RequestTriggerOrder
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
    public function getTriggerMode()
    {
        return $this->triggerMode;
    }

    /**
     * @param int $triggerMode
     * @return RequestTriggerOrder
     */
    public function setTriggerMode($triggerMode)
    {
        \Assert\Assertion::integer($triggerMode);

        $this->triggerMode = $triggerMode;

        return $this;
    }
    /**
     * @return int
     */
    public function getDealType()
    {
        return $this->dealType;
    }

    /**
     * @param int $dealType
     * @return RequestTriggerOrder
     */
    public function setDealType($dealType)
    {
        \Assert\Assertion::integer($dealType);

        $this->dealType = $dealType;

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
     * @return RequestTriggerOrder
     */
    public function setDealLoadId($dealLoadId)
    {
        \Assert\Assertion::integer($dealLoadId);

        $this->dealLoadId = $dealLoadId;

        return $this;
    }
    /**
     * @return int
     */
    public function getExpireTime()
    {
        return $this->expireTime;
    }

    /**
     * @param int $expireTime
     * @return RequestTriggerOrder
     */
    public function setExpireTime($expireTime)
    {
        \Assert\Assertion::integer($expireTime);

        $this->expireTime = $expireTime;

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
     * @return RequestTriggerOrder
     */
    public function setToken($token)
    {
        \Assert\Assertion::string($token);

        $this->token = $token;

        return $this;
    }
    /**
     * @return array
     */
    public function getExtra()
    {
        return $this->extra;
    }

    /**
     * @param array $extra
     * @return RequestTriggerOrder
     */
    public function setExtra(array $extra = NULL)
    {
        $this->extra = $extra;

        return $this;
    }

}