<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ResponseBase;
use Assert\Assertion;

/**
 * 获取用户信息接口
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiansong
 */
class ResponseRegisteFundUser extends ResponseBase
{
    const FLAG_SUCCESS = 0;
    const FLAG_EXISTS = 1;
    const FLAG_FAILD = 2;

    /**
     * 注册结果
     *
     * @var int
     * @required
     */
    private $flag;

    /**
     * 错误原因
     *
     * @var string
     * @required
     */
    private $message;

    /**
     * @return int
     */
    public function getFlag()
    {
        return $this->flag;
    }

    /**
     * @param int $flag
     * @return ResponseRegisteFundUser
     */
    public function setFlag($flag)
    {
        \Assert\Assertion::integer($flag);

        $this->flag = $flag;

        return $this;
    }
    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $message
     * @return ResponseRegisteFundUser
     */
    public function setMessage($message)
    {
        \Assert\Assertion::string($message);

        $this->message = $message;

        return $this;
    }

}