<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 获取基金分类列表信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author fupingzhou
 */
class ResponseGetFundTypeList extends ResponseBase
{
    /**
     * 基金分类列表信息
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
     * @return ResponseGetFundTypeList
     */
    public function setList(array $list)
    {
        $this->list = $list;

        return $this;
    }

}