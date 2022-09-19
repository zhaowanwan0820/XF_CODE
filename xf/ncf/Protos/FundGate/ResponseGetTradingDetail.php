<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 获取用户交易详情
 *
 * 由代码生成器生成, 不可人为修改
 * @author sunqing
 */
class ResponseGetTradingDetail extends ResponseBase
{
    /**
     * 用户交易详情
     *
     * @var array
     * @required
     */
    private $detail;

    /**
     * @return array
     */
    public function getDetail()
    {
        return $this->detail;
    }

    /**
     * @param array $detail
     * @return ResponseGetTradingDetail
     */
    public function setDetail(array $detail)
    {
        $this->detail = $detail;

        return $this;
    }

}