<?php
namespace NCFGroup\Protos\Contract;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 获取标的分类信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiantong
 */
class ResponseGetProjectDescription extends ProtoBufferBase
{
    /**
     * 错误码(0为成功调用)
     *
     * @var int
     * @required
     */
    private $errorCode;

    /**
     * 错误信息
     *
     * @var string
     * @optional
     */
    private $errorMsg = NULL;

    /**
     * 返回数据(项目ID，项目描述, 类型 0:项目描述,1:委托投资说明)
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
     * @return ResponseGetProjectDescription
     */
    public function setErrorCode($errorCode)
    {
        \Assert\Assertion::integer($errorCode);

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
     * @return ResponseGetProjectDescription
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
     * @return ResponseGetProjectDescription
     */
    public function setData(array $data)
    {
        $this->data = $data;

        return $this;
    }

}