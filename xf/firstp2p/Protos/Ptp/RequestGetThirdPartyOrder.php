<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 第三方交互-单笔订单查询接口请求定义
 *
 * 由代码生成器生成, 不可人为修改
 * @author 郭峰 <guofeng3@ucfgroup.com>
 */
class RequestGetThirdPartyOrder extends ProtoBufferBase
{
    /**
     * 商户ID
     *
     * @var int
     * @required
     */
    private $merchantId;

    /**
     * 外部订单号
     *
     * @var string
     * @optional
     */
    private $outOrderId = '';

    /**
     * 查询开始时间戳
     *
     * @var int
     * @optional
     */
    private $startTime = 0;

    /**
     * 查询结束时间戳
     *
     * @var int
     * @optional
     */
    private $endTime = 0;

    /**
     * 当前页码
     *
     * @var int
     * @optional
     */
    private $pageNo = 1;

    /**
     * 每页记录数
     *
     * @var int
     * @optional
     */
    private $pageLimit = 100;

    /**
     * @return int
     */
    public function getMerchantId()
    {
        return $this->merchantId;
    }

    /**
     * @param int $merchantId
     * @return RequestGetThirdPartyOrder
     */
    public function setMerchantId($merchantId)
    {
        \Assert\Assertion::integer($merchantId);

        $this->merchantId = $merchantId;

        return $this;
    }
    /**
     * @return string
     */
    public function getOutOrderId()
    {
        return $this->outOrderId;
    }

    /**
     * @param string $outOrderId
     * @return RequestGetThirdPartyOrder
     */
    public function setOutOrderId($outOrderId = '')
    {
        $this->outOrderId = $outOrderId;

        return $this;
    }
    /**
     * @return int
     */
    public function getStartTime()
    {
        return $this->startTime;
    }

    /**
     * @param int $startTime
     * @return RequestGetThirdPartyOrder
     */
    public function setStartTime($startTime = 0)
    {
        $this->startTime = $startTime;

        return $this;
    }
    /**
     * @return int
     */
    public function getEndTime()
    {
        return $this->endTime;
    }

    /**
     * @param int $endTime
     * @return RequestGetThirdPartyOrder
     */
    public function setEndTime($endTime = 0)
    {
        $this->endTime = $endTime;

        return $this;
    }
    /**
     * @return int
     */
    public function getPageNo()
    {
        return $this->pageNo;
    }

    /**
     * @param int $pageNo
     * @return RequestGetThirdPartyOrder
     */
    public function setPageNo($pageNo = 1)
    {
        $this->pageNo = $pageNo;

        return $this;
    }
    /**
     * @return int
     */
    public function getPageLimit()
    {
        return $this->pageLimit;
    }

    /**
     * @param int $pageLimit
     * @return RequestGetThirdPartyOrder
     */
    public function setPageLimit($pageLimit = 100)
    {
        $this->pageLimit = $pageLimit;

        return $this;
    }

}