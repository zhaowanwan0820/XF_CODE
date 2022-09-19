<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 用户Reqeust
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiansong
 */
class RequestUser extends AbstractRequestBase
{
    /**
     * 理财师ID
     *
     * @var int
     * @required
     */
    private $cfpId;

    /**
     * 用户ID
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * 用户名
     *
     * @var string
     * @optional
     */
    private $userName = '';

    /**
     * 手机号码
     *
     * @var string
     * @optional
     */
    private $mobile = '';

    /**
     * 是否脱敏
     *
     * @var int
     * @optional
     */
    private $isDesensitize = 1;

    /**
     * site ID
     *
     * @var int
     * @optional
     */
    private $siteId = NULL;

    /**
     * 是否是可编辑的信息
     *
     * @var int
     * @optional
     */
    private $isEditableInfo = 0;

    /**
     * @return int
     */
    public function getCfpId()
    {
        return $this->cfpId;
    }

    /**
     * @param int $cfpId
     * @return RequestUser
     */
    public function setCfpId($cfpId)
    {
        \Assert\Assertion::integer($cfpId);

        $this->cfpId = $cfpId;

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
     * @return RequestUser
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
     * @return RequestUser
     */
    public function setUserName($userName = '')
    {
        $this->userName = $userName;

        return $this;
    }
    /**
     * @return string
     */
    public function getMobile()
    {
        return $this->mobile;
    }

    /**
     * @param string $mobile
     * @return RequestUser
     */
    public function setMobile($mobile = '')
    {
        $this->mobile = $mobile;

        return $this;
    }
    /**
     * @return int
     */
    public function getIsDesensitize()
    {
        return $this->isDesensitize;
    }

    /**
     * @param int $isDesensitize
     * @return RequestUser
     */
    public function setIsDesensitize($isDesensitize = 1)
    {
        $this->isDesensitize = $isDesensitize;

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
     * @return RequestUser
     */
    public function setSiteId($siteId = NULL)
    {
        $this->siteId = $siteId;

        return $this;
    }
    /**
     * @return int
     */
    public function getIsEditableInfo()
    {
        return $this->isEditableInfo;
    }

    /**
     * @param int $isEditableInfo
     * @return RequestUser
     */
    public function setIsEditableInfo($isEditableInfo = 0)
    {
        $this->isEditableInfo = $isEditableInfo;

        return $this;
    }

}