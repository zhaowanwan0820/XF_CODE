<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 提现列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangfei
 */
class ResponseWithdraw extends ProtoBufferBase
{
    /**
     * 提现列表
     *
     * @var array
     * @required
     */
    private $list;

    /**
     * @return array
     */
    public function getList()
    {
        return $this->list;
    }

    /**
     * @param array $list
     * @return ResponseWithdraw
     */
    public function setList(array $list)
    {
        $this->list = $list;

        return $this;
    }

}