<?php
namespace NCFGroup\Protos\Stock;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 获取开户信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author libing
 */
class RequestGetAccountList extends AbstractRequestBase
{
    /**
     * 分页
     *
     * @var \NCFGroup\Common\Extensions\Base\Pageable
     * @required
     */
    private $pageable;

    /**
     * 券商名称缩写
     *
     * @var string
     * @required
     */
    private $briefName;

    /**
     * 券商来源
     *
     * @var string
     * @required
     */
    private $source;

    /**
     * 查询开始时间
     *
     * @var string
     * @required
     */
    private $startDate;

    /**
     * 开户状态
     *
     * @var string
     * @required
     */
    private $status;

    /**
     * 查询截止时间
     *
     * @var string
     * @required
     */
    private $endDate;

    /**
     * 用户id
     *
     * @var string
     * @required
     */
    private $userId;

    /**
     * 用户手机号
     *
     * @var string
     * @required
     */
    private $mobile;

    /**
     * @return \NCFGroup\Common\Extensions\Base\Pageable
     */
    public function getPageable()
    {
        return $this->pageable;
    }

    /**
     * @param \NCFGroup\Common\Extensions\Base\Pageable $pageable
     * @return RequestGetAccountList
     */
    public function setPageable(\NCFGroup\Common\Extensions\Base\Pageable $pageable)
    {
        $this->pageable = $pageable;

        return $this;
    }
    /**
     * @return string
     */
    public function getBriefName()
    {
        return $this->briefName;
    }

    /**
     * @param string $briefName
     * @return RequestGetAccountList
     */
    public function setBriefName($briefName)
    {
        \Assert\Assertion::string($briefName);

        $this->briefName = $briefName;

        return $this;
    }
    /**
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param string $source
     * @return RequestGetAccountList
     */
    public function setSource($source)
    {
        \Assert\Assertion::string($source);

        $this->source = $source;

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
     * @return RequestGetAccountList
     */
    public function setStartDate($startDate)
    {
        \Assert\Assertion::string($startDate);

        $this->startDate = $startDate;

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
     * @return RequestGetAccountList
     */
    public function setStatus($status)
    {
        \Assert\Assertion::string($status);

        $this->status = $status;

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
     * @return RequestGetAccountList
     */
    public function setEndDate($endDate)
    {
        \Assert\Assertion::string($endDate);

        $this->endDate = $endDate;

        return $this;
    }
    /**
     * @return string
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param string $userId
     * @return RequestGetAccountList
     */
    public function setUserId($userId)
    {
        \Assert\Assertion::string($userId);

        $this->userId = $userId;

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
     * @return RequestGetAccountList
     */
    public function setMobile($mobile)
    {
        \Assert\Assertion::string($mobile);

        $this->mobile = $mobile;

        return $this;
    }

}