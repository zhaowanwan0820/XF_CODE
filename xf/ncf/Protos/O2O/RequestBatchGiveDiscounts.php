<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 批量赠送投资券
 *
 * 由代码生成器生成, 不可人为修改
 * @author liguizhi<liguizhi@ucfgroup.com>
 */
class RequestBatchGiveDiscounts extends AbstractRequestBase
{
    /**
     * 赠送者ID
     *
     * @var int
     * @required
     */
    private $fromUserId;

    /**
     * 赠送列表
     *
     * @var array
     * @required
     */
    private $giveList;

    /**
     * @return int
     */
    public function getFromUserId()
    {
        return $this->fromUserId;
    }

    /**
     * @param int $fromUserId
     * @return RequestBatchGiveDiscounts
     */
    public function setFromUserId($fromUserId)
    {
        \Assert\Assertion::integer($fromUserId);

        $this->fromUserId = $fromUserId;

        return $this;
    }
    /**
     * @return array
     */
    public function getGiveList()
    {
        return $this->giveList;
    }

    /**
     * @param array $giveList
     * @return RequestBatchGiveDiscounts
     */
    public function setGiveList(array $giveList)
    {
        $this->giveList = $giveList;

        return $this;
    }

}