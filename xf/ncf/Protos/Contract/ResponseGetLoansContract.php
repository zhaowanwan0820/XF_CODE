<?php
namespace NCFGroup\Protos\Contract;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 获取分类列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiantong
 */
class ResponseGetLoansContract extends ProtoBufferBase
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
     * 合同列表数据
     *
     * @var array
     * @required
     */
    private $data;

    /**
     * @return int
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * @param int $errorCode
     * @return ResponseGetLoansContract
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
     * @return ResponseGetLoansContract
     */
    public function setErrorMsg($errorMsg = NULL)
    {
        $this->errorMsg = $errorMsg;

        return $this;
    }
    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param array $data
     * @return ResponseGetLoansContract
     */
    public function setData(array $data)
    {
        $this->data = $data;

        return $this;
    }

}