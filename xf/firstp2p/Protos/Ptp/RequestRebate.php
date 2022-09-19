<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 开发平台获取优惠码接口
 *
 * 由代码生成器生成, 不可人为修改
 * @author milining
 */
class RequestRebate extends ProtoBufferBase
{
    /**
     * 用户id
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * 查询类型：p2p，多投宝,reg
     *
     * @var string
     * @optional
     */
    private $type = 'p2p';

    /**
     * 站点id
     *
     * @var int
     * @optional
     */
    private $siteId = 0;

    /**
     * 结算状态（参加CouponService常量，6:线下结算; all:所有）
     *
     * @var string
     * @optional
     */
    private $payStatus = 'all';

    /**
     * 结算时间起点（包括）
     *
     * @var int
     * @optional
     */
    private $payTimeStart = 0;

    /**
     * 结算时间终点（包括）
     *
     * @var int
     * @optional
     */
    private $payTimeEnd = 0;

    /**
     * 获取结果的类型(stat/list/all)
     *
     * @var string
     * @optional
     */
    private $getResType = 'all';

    /**
     * 创建时间起点（包括）
     *
     * @var int
     * @optional
     */
    private $createTimeStart = 0;

    /**
     * 创建时间终点（包括）
     *
     * @var int
     * @optional
     */
    private $createTimeEnd = 0;

    /**
     * 起始页
     *
     * @var int
     * @optional
     */
    private $pageNum = 1;

    /**
     * 页大小
     *
     * @var int
     * @optional
     */
    private $pageSize = 10;

    /**
     * 手机号
     *
     * @var string
     * @optional
     */
    private $mobile = '';

    /**
     * 投资者用户id
     *
     * @var int
     * @optional
     */
    private $consumeUserId = 0;

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return RequestRebate
     */
    public function setUserId($userId)
    {
        \Assert\Assertion::integer($userId);

        $this->userId = $userId;

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
     * @return RequestRebate
     */
    public function setType($type = 'p2p')
    {
        $this->type = $type;

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
     * @return RequestRebate
     */
    public function setSiteId($siteId = 0)
    {
        $this->siteId = $siteId;

        return $this;
    }
    /**
     * @return string
     */
    public function getPayStatus()
    {
        return $this->payStatus;
    }

    /**
     * @param string $payStatus
     * @return RequestRebate
     */
    public function setPayStatus($payStatus = 'all')
    {
        $this->payStatus = $payStatus;

        return $this;
    }
    /**
     * @return int
     */
    public function getPayTimeStart()
    {
        return $this->payTimeStart;
    }

    /**
     * @param int $payTimeStart
     * @return RequestRebate
     */
    public function setPayTimeStart($payTimeStart = 0)
    {
        $this->payTimeStart = $payTimeStart;

        return $this;
    }
    /**
     * @return int
     */
    public function getPayTimeEnd()
    {
        return $this->payTimeEnd;
    }

    /**
     * @param int $payTimeEnd
     * @return RequestRebate
     */
    public function setPayTimeEnd($payTimeEnd = 0)
    {
        $this->payTimeEnd = $payTimeEnd;

        return $this;
    }
    /**
     * @return string
     */
    public function getGetResType()
    {
        return $this->getResType;
    }

    /**
     * @param string $getResType
     * @return RequestRebate
     */
    public function setGetResType($getResType = 'all')
    {
        $this->getResType = $getResType;

        return $this;
    }
    /**
     * @return int
     */
    public function getCreateTimeStart()
    {
        return $this->createTimeStart;
    }

    /**
     * @param int $createTimeStart
     * @return RequestRebate
     */
    public function setCreateTimeStart($createTimeStart = 0)
    {
        $this->createTimeStart = $createTimeStart;

        return $this;
    }
    /**
     * @return int
     */
    public function getCreateTimeEnd()
    {
        return $this->createTimeEnd;
    }

    /**
     * @param int $createTimeEnd
     * @return RequestRebate
     */
    public function setCreateTimeEnd($createTimeEnd = 0)
    {
        $this->createTimeEnd = $createTimeEnd;

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
     * @return RequestRebate
     */
    public function setPageNum($pageNum = 1)
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
     * @return RequestRebate
     */
    public function setPageSize($pageSize = 10)
    {
        $this->pageSize = $pageSize;

        return $this;
    }
    /**
     * @return string
     */
    public function getMobile()
    {
        return $this->mobile;
    }

    /**
     * @param string $mobile
     * @return RequestRebate
     */
    public function setMobile($mobile = '')
    {
        $this->mobile = $mobile;

        return $this;
    }
    /**
     * @return int
     */
    public function getConsumeUserId()
    {
        return $this->consumeUserId;
    }

    /**
     * @param int $consumeUserId
     * @return RequestRebate
     */
    public function setConsumeUserId($consumeUserId = 0)
    {
        $this->consumeUserId = $consumeUserId;

        return $this;
    }

}