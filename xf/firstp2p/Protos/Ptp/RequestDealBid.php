<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 投资接口
 *
 * 由代码生成器生成, 不可人为修改
 * @author xiaoan
 */
class RequestDealBid extends AbstractRequestBase
{
    /**
     * 标id
     *
     * @var int
     * @optional
     */
    private $id = 0;

    /**
     * 投标金额
     *
     * @var string
     * @required
     */
    private $money;

    /**
     * 优惠码
     *
     * @var string
     * @optional
     */
    private $coupon = '';

    /**
     * 投资来源
     *
     * @var int
     * @optional
     */
    private $source_type = 0;

    /**
     * 站点id
     *
     * @var int
     * @optional
     */
    private $site_id = 1;

    /**
     * 用户id
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * 第三方订单id(JFB)
     *
     * @var string
     * @optional
     */
    private $orderId = '';

    /**
     * 投资劵ID
     *
     * @var int
     * @optional
     */
    private $discountId = '';

    /**
     * 投资劵组ID
     *
     * @var int
     * @optional
     */
    private $discountGroupId = '';

    /**
     * 投资劵签名字符串
     *
     * @var string
     * @optional
     */
    private $discountSign = '';

    /**
     * 投资劵价格
     *
     * @var string
     * @optional
     */
    private $discountGoodprice = '';

    /**
     * 加密标id
     *
     * @var string
     * @optional
     */
    private $ecid = '';

    /**
     * 投资劵类型
     *
     * @var int
     * @optional
     */
    private $discountType = '1';

    /**
     * 行为跟踪ID
     *
     * @var int
     * @optional
     */
    private $trackId = '0';

    /**
     * 渠道信息
     *
     * @var string
     * @optional
     */
    private $euid = '';

    /**
     * 用户信息
     *
     * @var array
     * @optional
     */
    private $userInfo = NULL;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return RequestDealBid
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
     * @return RequestDealBid
     */
    public function setMoney($money)
    {
        \Assert\Assertion::string($money);

        $this->money = $money;

        return $this;
    }
    /**
     * @return string
     */
    public function getCoupon()
    {
        return $this->coupon;
    }

    /**
     * @param string $coupon
     * @return RequestDealBid
     */
    public function setCoupon($coupon = '')
    {
        $this->coupon = $coupon;

        return $this;
    }
    /**
     * @return int
     */
    public function getSource_type()
    {
        return $this->source_type;
    }

    /**
     * @param int $source_type
     * @return RequestDealBid
     */
    public function setSource_type($source_type = 0)
    {
        $this->source_type = $source_type;

        return $this;
    }
    /**
     * @return int
     */
    public function getSite_id()
    {
        return $this->site_id;
    }

    /**
     * @param int $site_id
     * @return RequestDealBid
     */
    public function setSite_id($site_id = 1)
    {
        $this->site_id = $site_id;

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
     * @return RequestDealBid
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
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * @param string $orderId
     * @return RequestDealBid
     */
    public function setOrderId($orderId = '')
    {
        $this->orderId = $orderId;

        return $this;
    }
    /**
     * @return int
     */
    public function getDiscountId()
    {
        return $this->discountId;
    }

    /**
     * @param int $discountId
     * @return RequestDealBid
     */
    public function setDiscountId($discountId = '')
    {
        $this->discountId = $discountId;

        return $this;
    }
    /**
     * @return int
     */
    public function getDiscountGroupId()
    {
        return $this->discountGroupId;
    }

    /**
     * @param int $discountGroupId
     * @return RequestDealBid
     */
    public function setDiscountGroupId($discountGroupId = '')
    {
        $this->discountGroupId = $discountGroupId;

        return $this;
    }
    /**
     * @return string
     */
    public function getDiscountSign()
    {
        return $this->discountSign;
    }

    /**
     * @param string $discountSign
     * @return RequestDealBid
     */
    public function setDiscountSign($discountSign = '')
    {
        $this->discountSign = $discountSign;

        return $this;
    }
    /**
     * @return string
     */
    public function getDiscountGoodprice()
    {
        return $this->discountGoodprice;
    }

    /**
     * @param string $discountGoodprice
     * @return RequestDealBid
     */
    public function setDiscountGoodprice($discountGoodprice = '')
    {
        $this->discountGoodprice = $discountGoodprice;

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
     * @return RequestDealBid
     */
    public function setEcid($ecid = '')
    {
        $this->ecid = $ecid;

        return $this;
    }
    /**
     * @return int
     */
    public function getDiscountType()
    {
        return $this->discountType;
    }

    /**
     * @param int $discountType
     * @return RequestDealBid
     */
    public function setDiscountType($discountType = '1')
    {
        $this->discountType = $discountType;

        return $this;
    }
    /**
     * @return int
     */
    public function getTrackId()
    {
        return $this->trackId;
    }

    /**
     * @param int $trackId
     * @return RequestDealBid
     */
    public function setTrackId($trackId = '0')
    {
        $this->trackId = $trackId;

        return $this;
    }
    /**
     * @return string
     */
    public function getEuid()
    {
        return $this->euid;
    }

    /**
     * @param string $euid
     * @return RequestDealBid
     */
    public function setEuid($euid = '')
    {
        $this->euid = $euid;

        return $this;
    }
    /**
     * @return array
     */
    public function getUserInfo()
    {
        return $this->userInfo;
    }

    /**
     * @param array $userInfo
     * @return RequestDealBid
     */
    public function setUserInfo(array $userInfo = NULL)
    {
        $this->userInfo = $userInfo;

        return $this;
    }

}