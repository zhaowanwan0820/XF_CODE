<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 用户登录接口
 *
 * 由代码生成器生成, 不可人为修改
 * @author yutao
 */
class RequestUserLogin extends AbstractRequestBase
{
    /**
     * 用户名
     *
     * @var string
     * @required
     */
    private $account;

    /**
     * 密码
     *
     * @var string
     * @required
     */
    private $password;

    /**
     * 验证码
     *
     * @var string
     * @optional
     */
    private $verify = '';

    /**
     * @return string
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * @param string $account
     * @return RequestUserLogin
     */
    public function setAccount($account)
    {
        \Assert\Assertion::string($account);

        $this->account = $account;

        return $this;
    }
    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     * @return RequestUserLogin
     */
    public function setPassword($password)
    {
        \Assert\Assertion::string($password);

        $this->password = $password;

        return $this;
    }
    /**
     * @return string
     */
    public function getVerify()
    {
        return $this->verify;
    }

    /**
     * @param string $verify
     * @return RequestUserLogin
     */
    public function setVerify($verify = '')
    {
        $this->verify = $verify;

        return $this;
    }

}