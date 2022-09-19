<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 用户发送的红包列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangshijie
 */
class RequestBonusSendList extends AbstractRequestBase
{
    /**
     * 用户ID
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * 页码
     *
     * @var int
     * @optional
     */
    private $page = '1';

    /**
     * 分页个数
     *
     * @var int
     * @optional
     */
    private $count = '10';

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return RequestBonusSendList
     */
    public function setUserId($userId)
    {
        \Assert\Assertion::integer($userId);

        $this->userId = $userId;

        return $this;
    }
    /**
     * @return int
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * @param int $page
     * @return RequestBonusSendList
     */
    public function setPage($page = '1')
    {
        $this->page = $page;

        return $this;
    }
    /**
     * @return int
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * @param int $count
     * @return RequestBonusSendList
     */
    public function setCount($count = '10')
    {
        $this->count = $count;

        return $this;
    }

}