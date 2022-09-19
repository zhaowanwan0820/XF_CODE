<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * o2o:向用户直推券
 *
 * 由代码生成器生成, 不可人为修改
 * @author liguizhi
 */
class RequestAcquireMedalCoupon extends ProtoBufferBase
{
    /**
     * 业务token[勋章or其他应用]
     *
     * @var string
     * @required
     */
    private $appToken;

    /**
     * 网信用户id
     *
     * @var string
     * @required
     */
    private $userId;

    /**
     * 券组编号
     *
     * @var array
     * @required
     */
    private $groupIds;

    /**
     * @return string
     */
    public function getAppToken()
    {
        return $this->appToken;
    }

    /**
     * @param string $appToken
     * @return RequestAcquireMedalCoupon
     */
    public function setAppToken($appToken)
    {
        \Assert\Assertion::string($appToken);

        $this->appToken = $appToken;

        return $this;
    }
    /**
     * @return string
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param string $userId
     * @return RequestAcquireMedalCoupon
     */
    public function setUserId($userId)
    {
        \Assert\Assertion::string($userId);

        $this->userId = $userId;

        return $this;
    }
    /**
     * @return array
     */
    public function getGroupIds()
    {
        return $this->groupIds;
    }

    /**
     * @param array $groupIds
     * @return RequestAcquireMedalCoupon
     */
    public function setGroupIds(array $groupIds)
    {
        $this->groupIds = $groupIds;

        return $this;
    }

}