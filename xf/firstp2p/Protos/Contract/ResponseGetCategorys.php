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
class ResponseGetCategorys extends ProtoBufferBase
{
    /**
     * 错误代码(0:调用成功,1:调用失败)
     *
     * @var int
     * @optional
     */
    private $error_code = NULL;

    /**
     * 错误信息
     *
     * @var string
     * @optional
     */
    private $error_msg = NULL;

    /**
     * 分类列表
     *
     * @var array
     * @required
     */
    private $list;

    /**
     * @return int
     */
    public function getError_code()
    {
        return $this->error_code;
    }

    /**
     * @param int $error_code
     * @return ResponseGetCategorys
     */
    public function setError_code($error_code = NULL)
    {
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
     * @return ResponseGetCategorys
     */
    public function setError_msg($error_msg = NULL)
    {
        $this->error_msg = $error_msg;

        return $this;
    }
    /**
     * @return array
     */
    public function getList()
    {
        return $this->list;
    }

    /**
     * @param array $list
     * @return ResponseGetCategorys
     */
    public function setList(array $list)
    {
        $this->list = $list;

        return $this;
    }

}