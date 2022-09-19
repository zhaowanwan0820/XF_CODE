<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * o2o:向合作方推送券信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author liguizhi
 */
class RequestPushCoupon extends ProtoBufferBase
{
    /**
     * 优惠券兑换订单
     *
     * @var string
     * @required
     */
    private $orderId;

    /**
     * 网信给合作方分配的唯一key
     *
     * @var string
     * @required
     */
    private $clientId;

    /**
     * 优惠券开始时间
     *
     * @var int
     * @required
     */
    private $beginTime;

    /**
     * 优惠券结束时间
     *
     * @var int
     * @required
     */
    private $endTime;

    /**
     * 优惠券金额
     *
     * @var string
     * @optional
     */
    private $price = '';

    /**
     * 合作方商品编号
     *
     * @var string
     * @optional
     */
    private $productId = '';

    /**
     * 网信userId
     *
     * @var string
     * @optional
     */
    private $userId = '';

    /**
     * 网信用户绑定的手机号
     *
     * @var string
     * @optional
     */
    private $phone = '';

    /**
     * 合作方用户名
     *
     * @var string
     * @optional
     */
    private $userName = '';

    /**
     * 合作方用户邮箱
     *
     * @var string
     * @optional
     */
    private $email = '';

    /**
     * 合作方用户身份证号码
     *
     * @var string
     * @optional
     */
    private $idno = '';

    /**
     * 合作方其他信息
     *
     * @var string
     * @optional
     */
    private $extra = '';

    /**
     * @return string
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * @param string $orderId
     * @return RequestPushCoupon
     */
    public function setOrderId($orderId)
    {
        \Assert\Assertion::string($orderId);

        $this->orderId = $orderId;

        return $this;
    }
    /**
     * @return string
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * @param string $clientId
     * @return RequestPushCoupon
     */
    public function setClientId($clientId)
    {
        \Assert\Assertion::string($clientId);

        $this->clientId = $clientId;

        return $this;
    }
    /**
     * @return int
     */
    public function getBeginTime()
    {
        return $this->beginTime;
    }

    /**
     * @param int $beginTime
     * @return RequestPushCoupon
     */
    public function setBeginTime($beginTime)
    {
        \Assert\Assertion::integer($beginTime);

        $this->beginTime = $beginTime;

        return $this;
    }
    /**
     * @return int
     */
    public function getEndTime()
    {
        return $this->endTime;
    }

    /**
     * @param int $endTime
     * @return RequestPushCoupon
     */
    public function setEndTime($endTime)
    {
        \Assert\Assertion::integer($endTime);

        $this->endTime = $endTime;

        return $this;
    }
    /**
     * @return string
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param string $price
     * @return RequestPushCoupon
     */
    public function setPrice($price = '')
    {
        $this->price = $price;

        return $this;
    }
    /**
     * @return string
     */
    public function getProductId()
    {
        return $this->productId;
    }

    /**
     * @param string $productId
     * @return RequestPushCoupon
     */
    public function setProductId($productId = '')
    {
        $this->productId = $productId;

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
     * @return RequestPushCoupon
     */
    public function setUserId($userId = '')
    {
        $this->userId = $userId;

        return $this;
    }
    /**
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @param string $phone
     * @return RequestPushCoupon
     */
    public function setPhone($phone = '')
    {
        $this->phone = $phone;

        return $this;
    }
    /**
     * @return string
     */
    public function getUserName()
    {
        return $this->userName;
    }

    /**
     * @param string $userName
     * @return RequestPushCoupon
     */
    public function setUserName($userName = '')
    {
        $this->userName = $userName;

        return $this;
    }
    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     * @return RequestPushCoupon
     */
    public function setEmail($email = '')
    {
        $this->email = $email;

        return $this;
    }
    /**
     * @return string
     */
    public function getIdno()
    {
        return $this->idno;
    }

    /**
     * @param string $idno
     * @return RequestPushCoupon
     */
    public function setIdno($idno = '')
    {
        $this->idno = $idno;

        return $this;
    }
    /**
     * @return string
     */
    public function getExtra()
    {
        return $this->extra;
    }

    /**
     * @param string $extra
     * @return RequestPushCoupon
     */
    public function setExtra($extra = '')
    {
        $this->extra = $extra;

        return $this;
    }

}