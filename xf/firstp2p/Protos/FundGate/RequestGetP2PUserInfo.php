<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 获取P2P用户信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author fupingzhou
 */
class RequestGetP2PUserInfo extends AbstractRequestBase
{
    /**
     * p2p用户id
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return RequestGetP2PUserInfo
     */
    public function setUserId($userId)
    {
        \Assert\Assertion::integer($userId);

        $this->userId = $userId;

        return $this;
    }

}