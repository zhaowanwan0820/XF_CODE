<?php
namespace NCFGroup\Protos\Contract;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 添加模板
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiantong
 */
class ResponseShowContract extends ProtoBufferBase
{
    /**
     * 状态(0:失败,1:成功)
     *
     * @var boolean
     * @required
     */
    private $status;

    /**
     * 合同数据
     *
     * @var string
     * @optional
     */
    private $data = NULL;

    /**
     * @return boolean
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param boolean $status
     * @return ResponseShowContract
     */
    public function setStatus($status)
    {
        \Assert\Assertion::boolean($status);

        $this->status = $status;

        return $this;
    }
    /**
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param string $data
     * @return ResponseShowContract
     */
    public function setData($data = NULL)
    {
        $this->data = $data;

        return $this;
    }

}