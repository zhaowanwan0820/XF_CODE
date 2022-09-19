<?php
namespace NCFGroup\Protos\Stock;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 修改用户信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author zhangzuoyang
 */
class RequestUpdateUserInfo extends AbstractRequestBase
{
    /**
     * 用户ID
     *
     * @var string
     * @required
     */
    private $userId;

    /**
     * 身份证地址
     *
     * @var string
     * @required
     */
    private $idCardAddress;

    /**
     * 身份证有效期
     *
     * @var string
     * @required
     */
    private $idCardExpirationDate;

    /**
     * 用户住址
     *
     * @var string
     * @required
     */
    private $address;

    /**
     * 学历ID
     *
     * @var string
     * @required
     */
    private $degreeId;

    /**
     * 职位ID
     *
     * @var string
     * @required
     */
    private $professionId;

    /**
     * @return string
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param string $userId
     * @return RequestUpdateUserInfo
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
    public function getIdCardAddress()
    {
        return $this->idCardAddress;
    }

    /**
     * @param string $idCardAddress
     * @return RequestUpdateUserInfo
     */
    public function setIdCardAddress($idCardAddress)
    {
        \Assert\Assertion::string($idCardAddress);

        $this->idCardAddress = $idCardAddress;

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
     * @return RequestUpdateUserInfo
     */
    public function setIdCardExpirationDate($idCardExpirationDate)
    {
        \Assert\Assertion::string($idCardExpirationDate);

        $this->idCardExpirationDate = $idCardExpirationDate;

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
     * @return RequestUpdateUserInfo
     */
    public function setAddress($address)
    {
        \Assert\Assertion::string($address);

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
     * @return RequestUpdateUserInfo
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
     * @return RequestUpdateUserInfo
     */
    public function setProfessionId($professionId)
    {
        \Assert\Assertion::string($professionId);

        $this->professionId = $professionId;

        return $this;
    }

}