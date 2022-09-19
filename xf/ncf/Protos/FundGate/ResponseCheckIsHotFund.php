<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 检查某支基金是否属于热门基金
 *
 * 由代码生成器生成, 不可人为修改
 * @author fupingzhou
 */
class ResponseCheckIsHotFund extends ResponseBase
{
    /**
     * 基金信息
     *
     * @var array
     * @required
     */
    private $info;

    /**
     * @return array
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * @param array $info
     * @return ResponseCheckIsHotFund
     */
    public function setInfo(array $info)
    {
        $this->info = $info;

        return $this;
    }

}