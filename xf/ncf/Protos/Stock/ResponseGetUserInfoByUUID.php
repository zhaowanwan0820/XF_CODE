<?php
namespace NCFGroup\Protos\Stock;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 用户信息接口
 *
 * 由代码生成器生成, 不可人为修改
 * @author zhangzuoyang
 */
class ResponseGetUserInfoByUUID extends ResponseBase
{
    /**
     * 证件姓名
     *
     * @var string
     * @optional
     */
    private $customerName = '';

    /**
     * 身份证号
     *
     * @var string
     * @optional
     */
    private $idCardNumber = '';

    /**
     * 手机号
     *
     * @var string
     * @optional
     */
    private $mobile = '';

    /**
     * 身份证有效期
     *
     * @var string
     * @optional
     */
    private $idCardExpirationDate = '';

    /**
     * 身份证地址
     *
     * @var string
     * @optional
     */
    private $idCardAddress = '';

    /**
     * 住址
     *
     * @var string
     * @optional
     */
    private $address = '';

    /**
     * 学位
     *
     * @var string
     * @required
     */
    private $degreeId;

    /**
     * 职业
     *
     * @var string
     * @required
     */
    private $professionId;

    /**
     * 职业列表
     *
     * @var array
     * @optional
     */
    private $professions = NULL;

    /**
     * 学位列表
     *
     * @var array
     * @optional
     */
    private $degrees = NULL;

    /**
     * 性别 1: 男；2：女
     *
     * @var string
     * @optional
     */
    private $gender = '1';

    /**
     * 身份证正面照片
     *
     * @var string
     * @optional
     */
    private $frontUrl = '';

    /**
     * 身份证反面照片
     *
     * @var string
     * @optional
     */
    private $backUrl = '';

    /**
     * 用户ID
     *
     * @var string
     * @required
     */
    private $userId;

    /**
     * 生日
     *
     * @var string
     * @required
     */
    private $birthday;

    /**
     * 用户照片
     *
     * @var string
     * @optional
     */
    private $selfUrl = '\'\'';

    /**
     * @return string
     */
    public function getCustomerName()
    {
        return $this->customerName;
    }

    /**
     * @param string $customerName
     * @return ResponseGetUserInfoByUUID
     */
    public function setCustomerName($customerName = '')
    {
        $this->customerName = $customerName;

        return $this;
    }
    /**
     * @return string
     */
    public function getIdCardNumber()
    {
        return $this->idCardNumber;
    }

    /**
     * @param string $idCardNumber
     * @return ResponseGetUserInfoByUUID
     */
    public function setIdCardNumber($idCardNumber = '')
    {
        $this->idCardNumber = $idCardNumber;

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
     * @return ResponseGetUserInfoByUUID
     */
    public function setMobile($mobile = '')
    {
        $this->mobile = $mobile;

        return $this;
    }
    /**
     * @return string
     */
    public function getIdCardExpirationDate()
    {
        return $this->idCardExpirationDate;
    }

    /**
     * @param string $idCardExpirationDate
     * @return ResponseGetUserInfoByUUID
     */
    public function setIdCardExpirationDate($idCardExpirationDate = '')
    {
        $this->idCardExpirationDate = $idCardExpirationDate;

        return $this;
    }
    /**
     * @return string
     */
    public function getIdCardAddress()
    {
        return $this->idCardAddress;
    }

    /**
     * @param string $idCardAddress
     * @return ResponseGetUserInfoByUUID
     */
    public function setIdCardAddress($idCardAddress = '')
    {
        $this->idCardAddress = $idCardAddress;

        return $this;
    }
    /**
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param string $address
     * @return ResponseGetUserInfoByUUID
     */
    public function setAddress($address = '')
    {
        $this->address = $address;

        return $this;
    }
    /**
     * @return string
     */
    public function getDegreeId()
    {
        return $this->degreeId;
    }

    /**
     * @param string $degreeId
     * @return ResponseGetUserInfoByUUID
     */
    public function setDegreeId($degreeId)
    {
        \Assert\Assertion::string($degreeId);

        $this->degreeId = $degreeId;

        return $this;
    }
    /**
     * @return string
     */
    public function getProfessionId()
    {
        return $this->professionId;
    }

    /**
     * @param string $professionId
     * @return ResponseGetUserInfoByUUID
     */
    public function setProfessionId($professionId)
    {
        \Assert\Assertion::string($professionId);

        $this->professionId = $professionId;

        return $this;
    }
    /**
     * @return array
     */
    public function getProfessions()
    {
        return $this->professions;
    }

    /**
     * @param array $professions
     * @return ResponseGetUserInfoByUUID
     */
    public function setProfessions(array $professions = NULL)
    {
        $this->professions = $professions;

        return $this;
    }
    /**
     * @return array
     */
    public function getDegrees()
    {
        return $this->degrees;
    }

    /**
     * @param array $degrees
     * @return ResponseGetUserInfoByUUID
     */
    public function setDegrees(array $degrees = NULL)
    {
        $this->degrees = $degrees;

        return $this;
    }
    /**
     * @return string
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * @param string $gender
     * @return ResponseGetUserInfoByUUID
     */
    public function setGender($gender = '1')
    {
        $this->gender = $gender;

        return $this;
    }
    /**
     * @return string
     */
    public function getFrontUrl()
    {
        return $this->frontUrl;
    }

    /**
     * @param string $frontUrl
     * @return ResponseGetUserInfoByUUID
     */
    public function setFrontUrl($frontUrl = '')
    {
        $this->frontUrl = $frontUrl;

        return $this;
    }
    /**
     * @return string
     */
    public function getBackUrl()
    {
        return $this->backUrl;
    }

    /**
     * @param string $backUrl
     * @return ResponseGetUserInfoByUUID
     */
    public function setBackUrl($backUrl = '')
    {
        $this->backUrl = $backUrl;

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
     * @return ResponseGetUserInfoByUUID
     */
    public function setUserId($userId)
    {
        \Assert\Assertion::string($userId);

        $this->userId = $userId;

        return $this;
    }
    /**
     * @return string
     */
    public function getBirthday()
    {
        return $this->birthday;
    }

    /**
     * @param string $birthday
     * @return ResponseGetUserInfoByUUID
     */
    public function setBirthday($birthday)
    {
        \Assert\Assertion::string($birthday);

        $this->birthday = $birthday;

        return $this;
    }
    /**
     * @return string
     */
    public function getSelfUrl()
    {
        return $this->selfUrl;
    }

    /**
     * @param string $selfUrl
     * @return ResponseGetUserInfoByUUID
     */
    public function setSelfUrl($selfUrl = '\'\'')
    {
        $this->selfUrl = $selfUrl;

        return $this;
    }

}