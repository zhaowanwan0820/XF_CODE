<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use NCFGroup\Common\Extensions\Base\Pageable;

/**
 * 获取订单列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author shiyan
 */
class RequestGetOrdersList extends AbstractRequestBase
{
    /**
     * 分页类
     *
     * @var \NCFGroup\Common\Extensions\Base\Pageable
     * @required
     */
    private $pageable;

    /**
     * 订单类型
     *
     * @var string
     * @optional
     */
    private $type = '';

    /**
     * 订单状态
     *
     * @var string
     * @optional
     */
    private $status = '';

    /**
     * 订单号
     *
     * @var string
     * @optional
     */
    private $orderNo = '';

    /**
     * 用户id
     *
     * @var string
     * @optional
     */
    private $uid = '';

    /**
     * 基金编码
     *
     * @var string
     * @optional
     */
    private $fundCode = '';

    /**
     * 起始日期
     *
     * @var string
     * @optional
     */
    private $startDate = '';

    /**
     * 截止日期
     *
     * @var string
     * @optional
     */
    private $endDate = '';

    /**
     * 基金类型
     *
     * @var string
     * @optional
     */
    private $fundType = '';

    /**
     * 起始修改日期
     *
     * @var string
     * @optional
     */
    private $modifyStartDate = '';

    /**
     * 截止修改日期
     *
     * @var string
     * @optional
     */
    private $modifyEndDate = '';

    /**
     * 基金返回订单号
     *
     * @var string
     * @optional
     */
    private $fundOrderId = '';

    /**
     * @return \NCFGroup\Common\Extensions\Base\Pageable
     */
    public function getPageable()
    {
        return $this->pageable;
    }

    /**
     * @param \NCFGroup\Common\Extensions\Base\Pageable $pageable
     * @return RequestGetOrdersList
     */
    public function setPageable(\NCFGroup\Common\Extensions\Base\Pageable $pageable)
    {
        $this->pageable = $pageable;

        return $this;
    }
    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return RequestGetOrdersList
     */
    public function setType($type = '')
    {
        $this->type = $type;

        return $this;
    }
    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     * @return RequestGetOrdersList
     */
    public function setStatus($status = '')
    {
        $this->status = $status;

        return $this;
    }
    /**
     * @return string
     */
    public function getOrderNo()
    {
        return $this->orderNo;
    }

    /**
     * @param string $orderNo
     * @return RequestGetOrdersList
     */
    public function setOrderNo($orderNo = '')
    {
        $this->orderNo = $orderNo;

        return $this;
    }
    /**
     * @return string
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * @param string $uid
     * @return RequestGetOrdersList
     */
    public function setUid($uid = '')
    {
        $this->uid = $uid;

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
     * @return RequestGetOrdersList
     */
    public function setFundCode($fundCode = '')
    {
        $this->fundCode = $fundCode;

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
     * @return RequestGetOrdersList
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
     * @return RequestGetOrdersList
     */
    public function setEndDate($endDate = '')
    {
        $this->endDate = $endDate;

        return $this;
    }
    /**
     * @return string
     */
    public function getFundType()
    {
        return $this->fundType;
    }

    /**
     * @param string $fundType
     * @return RequestGetOrdersList
     */
    public function setFundType($fundType = '')
    {
        $this->fundType = $fundType;

        return $this;
    }
    /**
     * @return string
     */
    public function getModifyStartDate()
    {
        return $this->modifyStartDate;
    }

    /**
     * @param string $modifyStartDate
     * @return RequestGetOrdersList
     */
    public function setModifyStartDate($modifyStartDate = '')
    {
        $this->modifyStartDate = $modifyStartDate;

        return $this;
    }
    /**
     * @return string
     */
    public function getModifyEndDate()
    {
        return $this->modifyEndDate;
    }

    /**
     * @param string $modifyEndDate
     * @return RequestGetOrdersList
     */
    public function setModifyEndDate($modifyEndDate = '')
    {
        $this->modifyEndDate = $modifyEndDate;

        return $this;
    }
    /**
     * @return string
     */
    public function getFundOrderId()
    {
        return $this->fundOrderId;
    }

    /**
     * @param string $fundOrderId
     * @return RequestGetOrdersList
     */
    public function setFundOrderId($fundOrderId = '')
    {
        $this->fundOrderId = $fundOrderId;

        return $this;
    }

}