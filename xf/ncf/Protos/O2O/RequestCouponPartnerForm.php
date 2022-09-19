<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use NCFGroup\Common\Extensions\Base\Pageable;
use Assert\Assertion;

/**
 * 第三方兑换表单信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author yanbingrong
 */
class RequestCouponPartnerForm extends ProtoBufferBase
{
    /**
     * 用户名
     *
     * @var string
     * @optional
     */
    private $userName = '';

    /**
     * 手机号
     *
     * @var string
     * @optional
     */
    private $phone = '';

    /**
     * 身份证号码
     *
     * @var string
     * @optional
     */
    private $idno = '';

    /**
     * 邮箱
     *
     * @var string
     * @optional
     */
    private $email = '';

    /**
     * @return string
     */
    public function getUserName()
    {
        return $this->userName;
    }

    /**
     * @param string $userName
     * @return RequestCouponPartnerForm
     */
    public function setUserName($userName = '')
    {
        $this->userName = $userName;

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
     * @return RequestCouponPartnerForm
     */
    public function setPhone($phone = '')
    {
        $this->phone = $phone;

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
     * @return RequestCouponPartnerForm
     */
    public function setIdno($idno = '')
    {
        $this->idno = $idno;

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
     * @return RequestCouponPartnerForm
     */
    public function setEmail($email = '')
    {
        $this->email = $email;

        return $this;
    }

}