<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * o2o:获取用户最近投资券的受赠时间
 *
 * 由代码生成器生成, 不可人为修改
 * @author yanbingrong
 */
class RequestGetUserGivenTimeList extends ProtoBufferBase
{
    /**
     * 用户列表
     *
     * @var array
     * @required
     */
    private $userIds;

    /**
     * @return array
     */
    public function getUserIds()
    {
        return $this->userIds;
    }

    /**
     * @param array $userIds
     * @return RequestGetUserGivenTimeList
     */
    public function setUserIds(array $userIds)
    {
        $this->userIds = $userIds;

        return $this;
    }

}