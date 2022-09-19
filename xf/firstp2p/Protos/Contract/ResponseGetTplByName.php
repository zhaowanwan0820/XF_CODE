<?php
namespace NCFGroup\Protos\Contract;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 按照模板id取得模板列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiantong
 */
class ResponseGetTplByName extends ProtoBufferBase
{
    /**
     * 错误码(0为成功调用)
     *
     * @var int
     * @required
     */
    private $error_code;

    /**
     * 错误信息
     *
     * @var string
     * @required
     */
    private $error_msg;

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
    public function getError_code()
    {
        return $this->error_code;
    }

    /**
     * @param int $error_code
     * @return ResponseGetTplByName
     */
    public function setError_code($error_code)
    {
        \Assert\Assertion::integer($error_code);

        $this->error_code = $error_code;

        return $this;
    }
    /**
     * @return string
     */
    public function getError_msg()
    {
        return $this->error_msg;
    }

    /**
     * @param string $error_msg
     * @return ResponseGetTplByName
     */
    public function setError_msg($error_msg)
    {
        \Assert\Assertion::string($error_msg);

        $this->error_msg = $error_msg;

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
     * @return ResponseGetTplByName
     */
    public function setData(array $data)
    {
        $this->data = $data;

        return $this;
    }

}