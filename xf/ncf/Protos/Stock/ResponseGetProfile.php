<?php
namespace NCFGroup\Protos\Stock;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 获取用户基本信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author zhangzuoyang
 */
class ResponseGetProfile extends ResponseBase
{
    /**
     * 用户住址
     *
     * @var string
     * @optional
     */
    private $address = '';

    /**
     * 用户名称
     *
     * @var string
     * @optional
     */
    private $customerName = '';

    /**
     * 学历
     *
     * @var int
     * @optional
     */
    private $degreeId = 0;

    /**
     * 学历名称
     *
     * @var string
     * @optional
     */
    private $degreeName = '';

    /**
     * 身份证号
     *
     * @var string
     * @optional
     */
    private $idCardNumber = '';

    /**
     * 身份证地址
     *
     * @var string
     * @optional
     */
    private $idCardAddress = '';

    /**
     * 职业
     *
     * @var int
     * @optional
     */
    private $professionId = 0;

    /**
     * 职业名称
     *
     * @var string
     * @optional
     */
    private $professionName = '';

    /**
     * 身份证到期时间
     *
     * @var string
     * @optional
     */
    private $idCardExpirationDate = '';

    /**
     * 性别
     *
     * @var int
     * @optional
     */
    private $gender = 0;

    /**
     * 是否注册p2p
     *
     * @var bool
     * @optional
     */
    private $authByP2p = false;

    /**
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param string $address
     * @return ResponseGetProfile
     */
    public function setAddress($address = '')
    {
        $this->address = $address;

        return $this;
    }
    /**
     * @return string
     */
    public function getCustomerName()
    {
        return $this->customerName;
    }

    /**
     * @param string $customerName
     * @return ResponseGetProfile
     */
    public function setCustomerName($customerName = '')
    {
        $this->customerName = $customerName;

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
     * @return ResponseGetProfile
     */
    public function setDegreeId($degreeId = 0)
    {
        $this->degreeId = $degreeId;

        return $this;
    }
    /**
     * @return string
     */
    public function getDegreeName()
    {
        return $this->degreeName;
    }

    /**
     * @param string $degreeName
     * @return ResponseGetProfile
     */
    public function setDegreeName($degreeName = '')
    {
        $this->degreeName = $degreeName;

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
     * @return ResponseGetProfile
     */
    public function setIdCardNumber($idCardNumber = '')
    {
        $this->idCardNumber = $idCardNumber;

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
     * @return ResponseGetProfile
     */
    public function setIdCardAddress($idCardAddress = '')
    {
        $this->idCardAddress = $idCardAddress;

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
     * @return ResponseGetProfile
     */
    public function setProfessionId($professionId = 0)
    {
        $this->professionId = $professionId;

        return $this;
    }
    /**
     * @return string
     */
    public function getProfessionName()
    {
        return $this->professionName;
    }

    /**
     * @param string $professionName
     * @return ResponseGetProfile
     */
    public function setProfessionName($professionName = '')
    {
        $this->professionName = $professionName;

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
     * @return ResponseGetProfile
     */
    public function setIdCardExpirationDate($idCardExpirationDate = '')
    {
        $this->idCardExpirationDate = $idCardExpirationDate;

        return $this;
    }
    /**
     * @return int
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * @param int $gender
     * @return ResponseGetProfile
     */
    public function setGender($gender = 0)
    {
        $this->gender = $gender;

        return $this;
    }
    /**
     * @return bool
     */
    public function getAuthByP2p()
    {
        return $this->authByP2p;
    }

    /**
     * @param bool $authByP2p
     * @return ResponseGetProfile
     */
    public function setAuthByP2p($authByP2p = false)
    {
        $this->authByP2p = $authByP2p;

        return $this;
    }

}