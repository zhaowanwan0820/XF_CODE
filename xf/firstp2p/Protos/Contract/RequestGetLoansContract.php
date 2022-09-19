<?php
namespace NCFGroup\Protos\Contract;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 获取分类列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiantong
 */
class RequestGetLoansContract extends ProtoBufferBase
{
    /**
     * 类型 0:p2p,1:DT
     *
     * @var int
     * @required
     */
    private $type;

    /**
     * 标的ID
     *
     * @var array
     * @required
     */
    private $dealInfo;

    /**
     * 用户ID
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     * @return RequestGetLoansContract
     */
    public function setType($type)
    {
        \Assert\Assertion::integer($type);

        $this->type = $type;

        return $this;
    }
    /**
     * @return array
     */
    public function getDealInfo()
    {
        return $this->dealInfo;
    }

    /**
     * @param array $dealInfo
     * @return RequestGetLoansContract
     */
    public function setDealInfo(array $dealInfo)
    {
        $this->dealInfo = $dealInfo;

        return $this;
    }
    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return RequestGetLoansContract
     */
    public function setUserId($userId)
    {
        \Assert\Assertion::integer($userId);

        $this->userId = $userId;

        return $this;
    }

}