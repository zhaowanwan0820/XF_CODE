<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 用户列表proto
 *
 * 由代码生成器生成, 不可人为修改
 * @author longbo
 */
class RequestUserList extends ProtoBufferBase
{
    /**
     * 分页类
     *
     * @var \NCFGroup\Common\Extensions\Base\Pageable
     * @optional
     */
    private $pageable = NULL;

    /**
     * 偏移量
     *
     * @var int
     * @optional
     */
    private $offset = 0;

    /**
     * 数量
     *
     * @var int
     * @optional
     */
    private $count = 10;

    /**
     * 分站ID
     *
     * @var int
     * @required
     */
    private $siteId;

    /**
     * 是否脱敏
     *
     * @var int
     * @optional
     */
    private $isDesensitize = 1;

    /**
     * 查询参数
     *
     * @var array
     * @optional
     */
    private $params = NULL;

    /**
     * @return \NCFGroup\Common\Extensions\Base\Pageable
     */
    public function getPageable()
    {
        return $this->pageable;
    }

    /**
     * @param \NCFGroup\Common\Extensions\Base\Pageable $pageable
     * @return RequestUserList
     */
    public function setPageable(\NCFGroup\Common\Extensions\Base\Pageable $pageable = NULL)
    {
        $this->pageable = $pageable;

        return $this;
    }
    /**
     * @return int
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * @param int $offset
     * @return RequestUserList
     */
    public function setOffset($offset = 0)
    {
        $this->offset = $offset;

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
     * @return RequestUserList
     */
    public function setCount($count = 10)
    {
        $this->count = $count;

        return $this;
    }
    /**
     * @return int
     */
    public function getSiteId()
    {
        return $this->siteId;
    }

    /**
     * @param int $siteId
     * @return RequestUserList
     */
    public function setSiteId($siteId)
    {
        \Assert\Assertion::integer($siteId);

        $this->siteId = $siteId;

        return $this;
    }
    /**
     * @return int
     */
    public function getIsDesensitize()
    {
        return $this->isDesensitize;
    }

    /**
     * @param int $isDesensitize
     * @return RequestUserList
     */
    public function setIsDesensitize($isDesensitize = 1)
    {
        $this->isDesensitize = $isDesensitize;

        return $this;
    }
    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @param array $params
     * @return RequestUserList
     */
    public function setParams(array $params = NULL)
    {
        $this->params = $params;

        return $this;
    }

}