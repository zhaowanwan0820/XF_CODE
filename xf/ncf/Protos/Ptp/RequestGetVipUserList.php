<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * vip会员批量接口
 *
 * 由代码生成器生成, 不可人为修改
 * @author liguizhi
 */
class RequestGetVipUserList extends ProtoBufferBase
{
    /**
     * 会员id,逗号分割
     *
     * @var string
     * @required
     */
    private $userIds;

    /**
     * @return string
     */
    public function getUserIds()
    {
        return $this->userIds;
    }

    /**
     * @param string $userIds
     * @return RequestGetVipUserList
     */
    public function setUserIds($userIds)
    {
        \Assert\Assertion::string($userIds);

        $this->userIds = $userIds;

        return $this;
    }

}