<?php
namespace NCFGroup\Protos\Future;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 操作订单列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author jingxu
 */
class ResponseOpOrderList extends AbstractRequestBase
{
    /**
     * 操作订单列表
     *
     * @var array
     * @required
     */
    private $opOrderList;

    /**
     * @return array
     */
    public function getOpOrderList()
    {
        return $this->opOrderList;
    }

    /**
     * @param array $opOrderList
     * @return ResponseOpOrderList
     */
    public function setOpOrderList(array $opOrderList)
    {
        $this->opOrderList = $opOrderList;

        return $this;
    }

}