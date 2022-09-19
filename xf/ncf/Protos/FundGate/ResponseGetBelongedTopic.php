<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 获取基金所属的专题（可能为多个）
 *
 * 由代码生成器生成, 不可人为修改
 * @author fupingzhou
 */
class ResponseGetBelongedTopic extends ResponseBase
{
    /**
     * 专题列表
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
     * @return ResponseGetBelongedTopic
     */
    public function setList(array $list)
    {
        $this->list = $list;

        return $this;
    }

}