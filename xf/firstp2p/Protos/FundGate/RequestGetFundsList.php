<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use NCFGroup\Common\Extensions\Base\Pageable;

/**
 * 获取基金列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author shiyan
 */
class RequestGetFundsList extends AbstractRequestBase
{
    /**
     * 分页
     *
     * @var \NCFGroup\Common\Extensions\Base\Pageable
     * @required
     */
    private $pageable;

    /**
     * 是否已删除
     *
     * @var int
     * @optional
     */
    private $isDelete = -1;

    /**
     * 基金类型
     *
     * @var int
     * @optional
     */
    private $type = -1;

    /**
     * 基金编码
     *
     * @var string
     * @optional
     */
    private $fundCode = '‘’';

    /**
     * 基金名称
     *
     * @var string
     * @optional
     */
    private $fundName = '‘’';

    /**
     * 供应商
     *
     * @var int
     * @optional
     */
    private $vendorId = -1;

    /**
     * @return \NCFGroup\Common\Extensions\Base\Pageable
     */
    public function getPageable()
    {
        return $this->pageable;
    }

    /**
     * @param \NCFGroup\Common\Extensions\Base\Pageable $pageable
     * @return RequestGetFundsList
     */
    public function setPageable(\NCFGroup\Common\Extensions\Base\Pageable $pageable)
    {
        $this->pageable = $pageable;

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
     * @return RequestGetFundsList
     */
    public function setIsDelete($isDelete = -1)
    {
        $this->isDelete = $isDelete;

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
     * @return RequestGetFundsList
     */
    public function setType($type = -1)
    {
        $this->type = $type;

        return $this;
    }
    /**
     * @return string
     */
    public function getFundCode()
    {
        return $this->fundCode;
    }

    /**
     * @param string $fundCode
     * @return RequestGetFundsList
     */
    public function setFundCode($fundCode = '‘’')
    {
        $this->fundCode = $fundCode;

        return $this;
    }
    /**
     * @return string
     */
    public function getFundName()
    {
        return $this->fundName;
    }

    /**
     * @param string $fundName
     * @return RequestGetFundsList
     */
    public function setFundName($fundName = '‘’')
    {
        $this->fundName = $fundName;

        return $this;
    }
    /**
     * @return int
     */
    public function getVendorId()
    {
        return $this->vendorId;
    }

    /**
     * @param int $vendorId
     * @return RequestGetFundsList
     */
    public function setVendorId($vendorId = -1)
    {
        $this->vendorId = $vendorId;

        return $this;
    }

}