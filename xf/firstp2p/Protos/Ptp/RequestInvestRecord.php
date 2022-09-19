<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use NCFGroup\Common\Extensions\Base\Pageable;
use Assert\Assertion;

/**
 * 获取佣金列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangfei@
 */
class RequestInvestRecord extends AbstractRequestBase
{
    /**
     * 分页类
     *
     * @var \NCFGroup\Common\Extensions\Base\Pageable
     * @required
     */
    private $pageable;

    /**
     * 理财师ID
     *
     * @var int
     * @required
     */
    private $cfpId;

    /**
     * 客户ID
     *
     * @var int
     * @optional
     */
    private $userId = 0;

    /**
     * 日期筛选(2015-01-01,2015-02-01)
     *
     * @var string
     * @optional
     */
    private $skeyDt = '';

    /**
     * 状态(0-为未返，1-为已返，其余为所有)
     *
     * @var int
     * @optional
     */
    private $skeySt = -1;

    /**
     * 客户姓名或者手机号
     *
     * @var string
     * @optional
     */
    private $skeyUser = '';

    /**
     * 项目名称
     *
     * @var string
     * @optional
     */
    private $skeyDealName = '';

    /**
     * 返回接口是否计算用户的总佣金情况。默认0不计算，1计算
     *
     * @var int
     * @optional
     */
    private $calProfit = 0;

    /**
     * 检索条件，最小投资额
     *
     * @var string
     * @optional
     */
    private $investMin = '';

    /**
     * 检索条件，最大投资额
     *
     * @var string
     * @optional
     */
    private $investMax = '';

    /**
     * @return \NCFGroup\Common\Extensions\Base\Pageable
     */
    public function getPageable()
    {
        return $this->pageable;
    }

    /**
     * @param \NCFGroup\Common\Extensions\Base\Pageable $pageable
     * @return RequestInvestRecord
     */
    public function setPageable(\NCFGroup\Common\Extensions\Base\Pageable $pageable)
    {
        $this->pageable = $pageable;

        return $this;
    }
    /**
     * @return int
     */
    public function getCfpId()
    {
        return $this->cfpId;
    }

    /**
     * @param int $cfpId
     * @return RequestInvestRecord
     */
    public function setCfpId($cfpId)
    {
        \Assert\Assertion::integer($cfpId);

        $this->cfpId = $cfpId;

        return $this;
    }
    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return RequestInvestRecord
     */
    public function setUserId($userId = 0)
    {
        $this->userId = $userId;

        return $this;
    }
    /**
     * @return string
     */
    public function getSkeyDt()
    {
        return $this->skeyDt;
    }

    /**
     * @param string $skeyDt
     * @return RequestInvestRecord
     */
    public function setSkeyDt($skeyDt = '')
    {
        $this->skeyDt = $skeyDt;

        return $this;
    }
    /**
     * @return int
     */
    public function getSkeySt()
    {
        return $this->skeySt;
    }

    /**
     * @param int $skeySt
     * @return RequestInvestRecord
     */
    public function setSkeySt($skeySt = -1)
    {
        $this->skeySt = $skeySt;

        return $this;
    }
    /**
     * @return string
     */
    public function getSkeyUser()
    {
        return $this->skeyUser;
    }

    /**
     * @param string $skeyUser
     * @return RequestInvestRecord
     */
    public function setSkeyUser($skeyUser = '')
    {
        $this->skeyUser = $skeyUser;

        return $this;
    }
    /**
     * @return string
     */
    public function getSkeyDealName()
    {
        return $this->skeyDealName;
    }

    /**
     * @param string $skeyDealName
     * @return RequestInvestRecord
     */
    public function setSkeyDealName($skeyDealName = '')
    {
        $this->skeyDealName = $skeyDealName;

        return $this;
    }
    /**
     * @return int
     */
    public function getCalProfit()
    {
        return $this->calProfit;
    }

    /**
     * @param int $calProfit
     * @return RequestInvestRecord
     */
    public function setCalProfit($calProfit = 0)
    {
        $this->calProfit = $calProfit;

        return $this;
    }
    /**
     * @return string
     */
    public function getInvestMin()
    {
        return $this->investMin;
    }

    /**
     * @param string $investMin
     * @return RequestInvestRecord
     */
    public function setInvestMin($investMin = '')
    {
        $this->investMin = $investMin;

        return $this;
    }
    /**
     * @return string
     */
    public function getInvestMax()
    {
        return $this->investMax;
    }

    /**
     * @param string $investMax
     * @return RequestInvestRecord
     */
    public function setInvestMax($investMax = '')
    {
        $this->investMax = $investMax;

        return $this;
    }

}