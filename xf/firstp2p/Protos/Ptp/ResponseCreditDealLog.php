<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ResponseBase;
use Assert\Assertion;

/**
 * 中国信贷投资记录
 *
 * 由代码生成器生成, 不可人为修改
 * @author yangqing
 */
class ResponseCreditDealLog extends ResponseBase
{
    /**
     * 投资列表
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
     * @return ResponseCreditDealLog
     */
    public function setList(array $list)
    {
        $this->list = $list;

        return $this;
    }

}