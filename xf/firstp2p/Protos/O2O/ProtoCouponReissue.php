<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * o2o:券码补发Proto
 *
 * 由代码生成器生成, 不可人为修改
 * @author yutao
 */
class ProtoCouponReissue extends ProtoBufferBase
{
    /**
     * 主键ID
     *
     * @var int
     * @required
     */
    private $id;

    /**
     * 用户ID
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * 用户名称
     *
     * @var string
     * @optional
     */
    private $userName = '';

    /**
     * 券组ID
     *
     * @var int
     * @required
     */
    private $couponGroupId;

    /**
     * 券码
     *
     * @var string
     * @required
     */
    private $coupon;

    /**
     * 创建时间
     *
     * @var int
     * @optional
     */
    private $createTime = '';

    /**
     * 后台管理员
     *
     * @var string
     * @optional
     */
    private $adminName = '';

    /**
     * 备注
     *
     * @var string
     * @optional
     */
    private $remark = '';

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return ProtoCouponReissue
     */
    public function setId($id)
    {
        \Assert\Assertion::integer($id);

        $this->id = $id;

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
     * @return ProtoCouponReissue
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
    public function getUserName()
    {
        return $this->userName;
    }

    /**
     * @param string $userName
     * @return ProtoCouponReissue
     */
    public function setUserName($userName = '')
    {
        $this->userName = $userName;

        return $this;
    }
    /**
     * @return int
     */
    public function getCouponGroupId()
    {
        return $this->couponGroupId;
    }

    /**
     * @param int $couponGroupId
     * @return ProtoCouponReissue
     */
    public function setCouponGroupId($couponGroupId)
    {
        \Assert\Assertion::integer($couponGroupId);

        $this->couponGroupId = $couponGroupId;

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
     * @return ProtoCouponReissue
     */
    public function setCoupon($coupon)
    {
        \Assert\Assertion::string($coupon);

        $this->coupon = $coupon;

        return $this;
    }
    /**
     * @return int
     */
    public function getCreateTime()
    {
        return $this->createTime;
    }

    /**
     * @param int $createTime
     * @return ProtoCouponReissue
     */
    public function setCreateTime($createTime = '')
    {
        $this->createTime = $createTime;

        return $this;
    }
    /**
     * @return string
     */
    public function getAdminName()
    {
        return $this->adminName;
    }

    /**
     * @param string $adminName
     * @return ProtoCouponReissue
     */
    public function setAdminName($adminName = '')
    {
        $this->adminName = $adminName;

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
     * @return ProtoCouponReissue
     */
    public function setRemark($remark = '')
    {
        $this->remark = $remark;

        return $this;
    }

}