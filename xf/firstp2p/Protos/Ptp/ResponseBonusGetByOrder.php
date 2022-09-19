<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ResponseBase;
use Assert\Assertion;

/**
 * 红包使用记录
 *
 * 由代码生成器生成, 不可人为修改
 * @author zhangzhuyan
 */
class ResponseBonusGetByOrder extends ResponseBase
{
    /**
     * 红包记录
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
     * @return ResponseBonusGetByOrder
     */
    public function setList(array $list)
    {
        $this->list = $list;

        return $this;
    }

}