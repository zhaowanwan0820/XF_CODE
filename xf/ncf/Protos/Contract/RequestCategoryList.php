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
class RequestCategoryList extends ProtoBufferBase
{
    /**
     * 类型 0:p2p,1:DT
     *
     * @var int
     * @optional
     */
    private $type = '0';

    /**
     * 页数
     *
     * @var int
     * @optional
     */
    private $pageNum = '1';

    /**
     * 一页大小
     *
     * @var int
     * @optional
     */
    private $pageSize = '30';

    /**
     * 是否删除
     *
     * @var int
     * @optional
     */
    private $isDelete = '0';

    /**
     * 使用状态
     *
     * @var int
     * @optional
     */
    private $useStatus = NULL;

    /**
     * 分类名称
     *
     * @var string
     * @optional
     */
    private $typeName = NULL;

    /**
     * 个人/公司借款
     *
     * @var int
     * @optional
     */
    private $contractType = NULL;

    /**
     * 标的类型 0:P2P,2:交易所,3:专享,5:小贷,100:黄金
     *
     * @var int
     * @optional
     */
    private $sourceType = NULL;

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     * @return RequestCategoryList
     */
    public function setType($type = '0')
    {
        $this->type = $type;

        return $this;
    }
    /**
     * @return int
     */
    public function getPageNum()
    {
        return $this->pageNum;
    }

    /**
     * @param int $pageNum
     * @return RequestCategoryList
     */
    public function setPageNum($pageNum = '1')
    {
        $this->pageNum = $pageNum;

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
     * @return RequestCategoryList
     */
    public function setPageSize($pageSize = '30')
    {
        $this->pageSize = $pageSize;

        return $this;
    }
    /**
     * @return int
     */
    public function getIsDelete()
    {
        return $this->isDelete;
    }

    /**
     * @param int $isDelete
     * @return RequestCategoryList
     */
    public function setIsDelete($isDelete = '0')
    {
        $this->isDelete = $isDelete;

        return $this;
    }
    /**
     * @return int
     */
    public function getUseStatus()
    {
        return $this->useStatus;
    }

    /**
     * @param int $useStatus
     * @return RequestCategoryList
     */
    public function setUseStatus($useStatus = NULL)
    {
        $this->useStatus = $useStatus;

        return $this;
    }
    /**
     * @return string
     */
    public function getTypeName()
    {
        return $this->typeName;
    }

    /**
     * @param string $typeName
     * @return RequestCategoryList
     */
    public function setTypeName($typeName = NULL)
    {
        $this->typeName = $typeName;

        return $this;
    }
    /**
     * @return int
     */
    public function getContractType()
    {
        return $this->contractType;
    }

    /**
     * @param int $contractType
     * @return RequestCategoryList
     */
    public function setContractType($contractType = NULL)
    {
        $this->contractType = $contractType;

        return $this;
    }
    /**
     * @return int
     */
    public function getSourceType()
    {
        return $this->sourceType;
    }

    /**
     * @param int $sourceType
     * @return RequestCategoryList
     */
    public function setSourceType($sourceType = NULL)
    {
        $this->sourceType = $sourceType;

        return $this;
    }

}