<?php
namespace NCFGroup\Protos\Contract;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 按照智多鑫前缀获取最新的合同模板
 *
 * 由代码生成器生成, 不可人为修改
 * @author duxuefeng
 */
class RequestGetCategoryRecordsByDealId extends ProtoBufferBase
{
    /**
     * 标的ID
     *
     * @var int
     * @required
     */
    private $dealId;

    /**
     * 类型(0:无;1:多投宝)
     *
     * @var int
     * @optional
     */
    private $type = 1;

    /**
     * 分页显示，页数
     *
     * @var int
     * @required
     */
    private $page;

    /**
     * @return int
     */
    public function getDealId()
    {
        return $this->dealId;
    }

    /**
     * @param int $dealId
     * @return RequestGetCategoryRecordsByDealId
     */
    public function setDealId($dealId)
    {
        \Assert\Assertion::integer($dealId);

        $this->dealId = $dealId;

        return $this;
    }
    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     * @return RequestGetCategoryRecordsByDealId
     */
    public function setType($type = 1)
    {
        $this->type = $type;

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
     * @return RequestGetCategoryRecordsByDealId
     */
    public function setPage($page)
    {
        \Assert\Assertion::integer($page);

        $this->page = $page;

        return $this;
    }

}