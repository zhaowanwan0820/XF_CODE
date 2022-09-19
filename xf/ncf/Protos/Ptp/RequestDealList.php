<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 标列表接口
 *
 * 由代码生成器生成, 不可人为修改
 * @author yutao
 */
class RequestDealList extends ProtoBufferBase
{
    /**
     * cate
     *
     * @var int
     * @optional
     */
    private $cate = NULL;

    /**
     * 分页
     *
     * @var int
     * @required
     */
    private $page;

    /**
     * 每页显示
     *
     * @var int
     * @optional
     */
    private $pageSize = 10;

    /**
     * 类型
     *
     * @var int
     * @optional
     */
    private $type = NULL;

    /**
     * 字段
     *
     * @var int
     * @required
     */
    private $field;

    /**
     * 分站ID
     *
     * @var int
     * @required
     */
    private $siteId;

    /**
     * is_all_site
     *
     * @var boolean
     * @optional
     */
    private $isAllSite = false;

    /**
     * 是否显示‘特定用户组’的标
     *
     * @var boolean
     * @optional
     */
    private $showCrowdSpecific = true;

    /**
     * tagNames 逗号分割
     *
     * @var string
     * @optional
     */
    private $tagName = '';

    /**
     * 标的列表类型
     *
     * @var string
     * @optional
     */
    private $dealListType = '';

    /**
     * @return int
     */
    public function getCate()
    {
        return $this->cate;
    }

    /**
     * @param int $cate
     * @return RequestDealList
     */
    public function setCate($cate = NULL)
    {
        $this->cate = $cate;

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
     * @return RequestDealList
     */
    public function setPage($page)
    {
        \Assert\Assertion::integer($page);

        $this->page = $page;

        return $this;
    }
    /**
     * @return int
     */
    public function getPageSize()
    {
        return $this->pageSize;
    }

    /**
     * @param int $pageSize
     * @return RequestDealList
     */
    public function setPageSize($pageSize = 10)
    {
        $this->pageSize = $pageSize;

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
     * @return RequestDealList
     */
    public function setType($type = NULL)
    {
        $this->type = $type;

        return $this;
    }
    /**
     * @return int
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * @param int $field
     * @return RequestDealList
     */
    public function setField($field)
    {
        \Assert\Assertion::integer($field);

        $this->field = $field;

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
     * @return RequestDealList
     */
    public function setSiteId($siteId)
    {
        \Assert\Assertion::integer($siteId);

        $this->siteId = $siteId;

        return $this;
    }
    /**
     * @return boolean
     */
    public function getIsAllSite()
    {
        return $this->isAllSite;
    }

    /**
     * @param boolean $isAllSite
     * @return RequestDealList
     */
    public function setIsAllSite($isAllSite = false)
    {
        $this->isAllSite = $isAllSite;

        return $this;
    }
    /**
     * @return boolean
     */
    public function getShowCrowdSpecific()
    {
        return $this->showCrowdSpecific;
    }

    /**
     * @param boolean $showCrowdSpecific
     * @return RequestDealList
     */
    public function setShowCrowdSpecific($showCrowdSpecific = true)
    {
        $this->showCrowdSpecific = $showCrowdSpecific;

        return $this;
    }
    /**
     * @return string
     */
    public function getTagName()
    {
        return $this->tagName;
    }

    /**
     * @param string $tagName
     * @return RequestDealList
     */
    public function setTagName($tagName = '')
    {
        $this->tagName = $tagName;

        return $this;
    }
    /**
     * @return string
     */
    public function getDealListType()
    {
        return $this->dealListType;
    }

    /**
     * @param string $dealListType
     * @return RequestDealList
     */
    public function setDealListType($dealListType = '')
    {
        $this->dealListType = $dealListType;

        return $this;
    }

}