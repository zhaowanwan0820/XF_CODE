<?php
namespace NCFGroup\Protos\Stock;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 更改用户资料
 *
 * 由代码生成器生成, 不可人为修改
 * @author zhangzuoyang
 */
class RequestSubmitProfile extends AbstractRequestBase
{
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
     * 职业编号
     *
     * @var int
     * @required
     */
    private $professionId;

    /**
     * 学历编号
     *
     * @var int
     * @required
     */
    private $degreeId;

    /**
     * 住址
     *
     * @var string
     * @required
     */
    private $address;

    /**
     * 用户ID
     *
     * @var string
     * @required
     */
    private $userId;

    /**
     * @return string
     */
    public function getIdCardAddress()
    {
        return $this->idCardAddress;
    }

    /**
     * @param string $idCardAddress
     * @return RequestSubmitProfile
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
     * @return RequestSubmitProfile
     */
    public function setIdCardExpirationDate($idCardExpirationDate)
    {
        \Assert\Assertion::string($idCardExpirationDate);

        $this->idCardExpirationDate = $idCardExpirationDate;

        return $this;
    }
    /**
     * @return int
     */
    public function getProfessionId()
    {
        return $this->professionId;
    }

    /**
     * @param int $professionId
     * @return RequestSubmitProfile
     */
    public function setProfessionId($professionId)
    {
        \Assert\Assertion::integer($professionId);

        $this->professionId = $professionId;

        return $this;
    }
    /**
     * @return int
     */
    public function getDegreeId()
    {
        return $this->degreeId;
    }

    /**
     * @param int $degreeId
     * @return RequestSubmitProfile
     */
    public function setDegreeId($degreeId)
    {
        \Assert\Assertion::integer($degreeId);

        $this->degreeId = $degreeId;

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
     * @return RequestSubmitProfile
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
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param string $userId
     * @return RequestSubmitProfile
     */
    public function setUserId($userId)
    {
        \Assert\Assertion::string($userId);

        $this->userId = $userId;

        return $this;
    }

}