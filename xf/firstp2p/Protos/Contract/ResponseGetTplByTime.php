<?php
namespace NCFGroup\Protos\Contract;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 按照智多鑫合同前缀取得模板
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiantong
 */
class ResponseGetTplByTime extends ProtoBufferBase
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
     * @required
     */
    private $errorMsg;

    /**
     * 模板信息
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
     * @return ResponseGetTplByTime
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
     * @return ResponseGetTplByTime
     */
    public function setErrorMsg($errorMsg)
    {
        \Assert\Assertion::string($errorMsg);

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
     * @return ResponseGetTplByTime
     */
    public function setData(array $data)
    {
        $this->data = $data;

        return $this;
    }

}