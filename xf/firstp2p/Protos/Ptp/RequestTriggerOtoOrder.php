<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 触发o2o礼券
 *
 * 由代码生成器生成, 不可人为修改
 * @author liguizhi
 */
class RequestTriggerOtoOrder extends AbstractRequestBase
{
    /**
     * 用户ID
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * 触发行为:首投,复投...
     *
     * @var int
     * @required
     */
    private $action;

    /**
     * 交易ID
     *
     * @var int
     * @required
     */
    private $dealLoadId;

    /**
     * 站点ID
     *
     * @var int
     * @optional
     */
    private $siteId = '1';

    /**
     * 交易金额
     *
     * @var string
     * @optional
     */
    private $money = '0';

    /**
     * 年化交易额
     *
     * @var string
     * @optional
     */
    private $annualizedAmount = '0';

    /**
     * 业务类型:1-p2p交易,2-智多鑫交易,3-智多鑫订单,4-优长金,5-黄金订单,6-优金宝,7-随心约,8-充值,9-速贷
     *
     * @var int
     * @optional
     */
    private $consumeType = '1';

    /**
     * 触发业务:1-p2p业务,2-智多鑫业务,3-黄金业务,4-随心约业务,5-速贷业务
     *
     * @var int
     * @optional
     */
    private $triggerType = '1';

    /**
     * 附加信息
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
     * @return RequestTriggerOtoOrder
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
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param int $action
     * @return RequestTriggerOtoOrder
     */
    public function setAction($action)
    {
        \Assert\Assertion::integer($action);

        $this->action = $action;

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
     * @return RequestTriggerOtoOrder
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
    public function getSiteId()
    {
        return $this->siteId;
    }

    /**
     * @param int $siteId
     * @return RequestTriggerOtoOrder
     */
    public function setSiteId($siteId = '1')
    {
        $this->siteId = $siteId;

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
     * @return RequestTriggerOtoOrder
     */
    public function setMoney($money = '0')
    {
        $this->money = $money;

        return $this;
    }
    /**
     * @return string
     */
    public function getAnnualizedAmount()
    {
        return $this->annualizedAmount;
    }

    /**
     * @param string $annualizedAmount
     * @return RequestTriggerOtoOrder
     */
    public function setAnnualizedAmount($annualizedAmount = '0')
    {
        $this->annualizedAmount = $annualizedAmount;

        return $this;
    }
    /**
     * @return int
     */
    public function getConsumeType()
    {
        return $this->consumeType;
    }

    /**
     * @param int $consumeType
     * @return RequestTriggerOtoOrder
     */
    public function setConsumeType($consumeType = '1')
    {
        $this->consumeType = $consumeType;

        return $this;
    }
    /**
     * @return int
     */
    public function getTriggerType()
    {
        return $this->triggerType;
    }

    /**
     * @param int $triggerType
     * @return RequestTriggerOtoOrder
     */
    public function setTriggerType($triggerType = '1')
    {
        $this->triggerType = $triggerType;

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
     * @return RequestTriggerOtoOrder
     */
    public function setExtra(array $extra = NULL)
    {
        $this->extra = $extra;

        return $this;
    }

}