<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 获取P2P用户信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author fupingzhou
 */
class ResponseGetP2PUserInfo extends ResponseBase
{
    /**
     * P2P用户信息
     *
     * @var array
     * @required
     */
    private $userInfo;

    /**
     * @return array
     */
    public function getUserInfo()
    {
        return $this->userInfo;
    }

    /**
     * @param array $userInfo
     * @return ResponseGetP2PUserInfo
     */
    public function setUserInfo(array $userInfo)
    {
        $this->userInfo = $userInfo;

        return $this;
    }

}