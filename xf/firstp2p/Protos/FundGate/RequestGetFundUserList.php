<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use NCFGroup\Common\Extensions\Base\Pageable;

/**
 * 获取基金用户列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author fupingzhou
 */
class RequestGetFundUserList extends AbstractRequestBase
{
    /**
     * 用户id
     *
     * @var string
     * @optional
     */
    private $uid = '';

    /**
     * 交易账号
     *
     * @var string
     * @optional
     */
    private $tradeId = '';

    /**
     * 开户起始时间
     *
     * @var string
     * @optional
     */
    private $startDate = '';

    /**
     * 开户截止时间
     *
     * @var string
     * @optional
     */
    private $endDate = '';

    /**
     * 分页类
     *
     * @var \NCFGroup\Common\Extensions\Base\Pageable
     * @required
     */
    private $pageable;

    /**
     * 提供商ID
     *
     * @var string
     * @optional
     */
    private $vendorId = '';

    /**
     * @return string
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * @param string $uid
     * @return RequestGetFundUserList
     */
    public function setUid($uid = '')
    {
        $this->uid = $uid;

        return $this;
    }
    /**
     * @return string
     */
    public function getTradeId()
    {
        return $this->tradeId;
    }

    /**
     * @param string $tradeId
     * @return RequestGetFundUserList
     */
    public function setTradeId($tradeId = '')
    {
        $this->tradeId = $tradeId;

        return $this;
    }
    /**
     * @return string
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * @param string $startDate
     * @return RequestGetFundUserList
     */
    public function setStartDate($startDate = '')
    {
        $this->startDate = $startDate;

        return $this;
    }
    /**
     * @return string
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * @param string $endDate
     * @return RequestGetFundUserList
     */
    public function setEndDate($endDate = '')
    {
        $this->endDate = $endDate;

        return $this;
    }
    /**
     * @return \NCFGroup\Common\Extensions\Base\Pageable
     */
    public function getPageable()
    {
        return $this->pageable;
    }

    /**
     * @param \NCFGroup\Common\Extensions\Base\Pageable $pageable
     * @return RequestGetFundUserList
     */
    public function setPageable(\NCFGroup\Common\Extensions\Base\Pageable $pageable)
    {
        $this->pageable = $pageable;

        return $this;
    }
    /**
     * @return string
     */
    public function getVendorId()
    {
        return $this->vendorId;
    }

    /**
     * @param string $vendorId
     * @return RequestGetFundUserList
     */
    public function setVendorId($vendorId = '')
    {
        $this->vendorId = $vendorId;

        return $this;
    }

}