<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 网盟标的相关
 *
 * 由代码生成器生成, 不可人为修改
 * @author liuzhenpeng
 */
class ProtoWebUnionDeal extends AbstractRequestBase
{
    /**
     * 用户手机号码
     *
     * @var string
     * @required
     */
    private $mobile;

    /**
     * 标的状态
     *
     * @var int
     * @optional
     */
    private $dealStatus = 0;

    /**
     * 标的id
     *
     * @var int
     * @optional
     */
    private $dealId = 0;

    /**
     * 投资id
     *
     * @var int
     * @optional
     */
    private $loadId = 0;

    /**
     * 标的id字符串
     *
     * @var string
     * @optional
     */
    private $dealIds = '';

    /**
     * @return string
     */
    public function getMobile()
    {
        return $this->mobile;
    }

    /**
     * @param string $mobile
     * @return ProtoWebUnionDeal
     */
    public function setMobile($mobile)
    {
        \Assert\Assertion::string($mobile);

        $this->mobile = $mobile;

        return $this;
    }
    /**
     * @return int
     */
    public function getDealStatus()
    {
        return $this->dealStatus;
    }

    /**
     * @param int $dealStatus
     * @return ProtoWebUnionDeal
     */
    public function setDealStatus($dealStatus = 0)
    {
        $this->dealStatus = $dealStatus;

        return $this;
    }
    /**
     * @return int
     */
    public function getDealId()
    {
        return $this->dealId;
    }

    /**
     * @param int $dealId
     * @return ProtoWebUnionDeal
     */
    public function setDealId($dealId = 0)
    {
        $this->dealId = $dealId;

        return $this;
    }
    /**
     * @return int
     */
    public function getLoadId()
    {
        return $this->loadId;
    }

    /**
     * @param int $loadId
     * @return ProtoWebUnionDeal
     */
    public function setLoadId($loadId = 0)
    {
        $this->loadId = $loadId;

        return $this;
    }
    /**
     * @return string
     */
    public function getDealIds()
    {
        return $this->dealIds;
    }

    /**
     * @param string $dealIds
     * @return ProtoWebUnionDeal
     */
    public function setDealIds($dealIds = '')
    {
        $this->dealIds = $dealIds;

        return $this;
    }

}