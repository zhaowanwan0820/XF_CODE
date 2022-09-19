<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * userFund信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author jingxu
 */
class ResponseGetUserFund extends ResponseBase
{
    /**
     * userFund信息
     *
     * @var array
     * @required
     */
    private $userFundInfo;

    /**
     * @return array
     */
    public function getUserFundInfo()
    {
        return $this->userFundInfo;
    }

    /**
     * @param array $userFundInfo
     * @return ResponseGetUserFund
     */
    public function setUserFundInfo(array $userFundInfo)
    {
        $this->userFundInfo = $userFundInfo;

        return $this;
    }

}