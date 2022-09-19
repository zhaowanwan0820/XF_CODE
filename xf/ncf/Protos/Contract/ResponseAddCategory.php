<?php
namespace NCFGroup\Protos\Contract;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 添加分类
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiantong
 */
class ResponseAddCategory extends ProtoBufferBase
{
    /**
     * 错误代码(0:调用成功,1:调用失败)
     *
     * @var int
     * @optional
     */
    private $errorCode = NULL;

    /**
     * 错误信息
     *
     * @var string
     * @optional
     */
    private $errorMsg = NULL;

    /**
     * 状态(0:失败,1:成功)
     *
     * @var boolean
     * @required
     */
    private $status;

    /**
     * 状态(0:失败,1:成功)
     *
     * @var int
     * @optional
     */
    private $id = NULL;

    /**
     * @return int
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * @param int $errorCode
     * @return ResponseAddCategory
     */
    public function setErrorCode($errorCode = NULL)
    {
        $this->errorCode = $errorCode;

        return $this;
    }
    /**
     * @return string
     */
    public function getErrorMsg()
    {
        return $this->errorMsg;
    }

    /**
     * @param string $errorMsg
     * @return ResponseAddCategory
     */
    public function setErrorMsg($errorMsg = NULL)
    {
        $this->errorMsg = $errorMsg;

        return $this;
    }
    /**
     * @return boolean
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param boolean $status
     * @return ResponseAddCategory
     */
    public function setStatus($status)
    {
        \Assert\Assertion::boolean($status);

        $this->status = $status;

        return $this;
    }
    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return ResponseAddCategory
     */
    public function setId($id = NULL)
    {
        $this->id = $id;

        return $this;
    }

}