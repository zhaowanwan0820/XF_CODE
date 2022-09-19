<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use NCFGroup\Common\Extensions\Base\Pageable;

/**
 * 获取基金协议列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author sunqing
 */
class RequestGetAgreementsList extends AbstractRequestBase
{
    /**
     * 分页
     *
     * @var \NCFGroup\Common\Extensions\Base\Pageable
     * @required
     */
    private $pageable;

    /**
     * 协议类型
     *
     * @var string
     * @optional
     */
    private $agreementType = '';

    /**
     * 显示名称
     *
     * @var string
     * @optional
     */
    private $displayName = '';

    /**
     * 版本号
     *
     * @var int
     * @optional
     */
    private $version = 0;

    /**
     * 协议内容
     *
     * @var string
     * @optional
     */
    private $content = '';

    /**
     * 是否激活
     *
     * @var int
     * @optional
     */
    private $active = 0;

    /**
     * 开始日期（创建）
     *
     * @var string
     * @optional
     */
    private $startCDate = '';

    /**
     * 截止日期（创建）
     *
     * @var string
     * @optional
     */
    private $endCDate = '';

    /**
     * 开始日期（修改）
     *
     * @var string
     * @optional
     */
    private $startMDate = '';

    /**
     * 截止日期（修改）
     *
     * @var string
     * @optional
     */
    private $endMDate = '';

    /**
     * @return \NCFGroup\Common\Extensions\Base\Pageable
     */
    public function getPageable()
    {
        return $this->pageable;
    }

    /**
     * @param \NCFGroup\Common\Extensions\Base\Pageable $pageable
     * @return RequestGetAgreementsList
     */
    public function setPageable(\NCFGroup\Common\Extensions\Base\Pageable $pageable)
    {
        $this->pageable = $pageable;

        return $this;
    }
    /**
     * @return string
     */
    public function getAgreementType()
    {
        return $this->agreementType;
    }

    /**
     * @param string $agreementType
     * @return RequestGetAgreementsList
     */
    public function setAgreementType($agreementType = '')
    {
        $this->agreementType = $agreementType;

        return $this;
    }
    /**
     * @return string
     */
    public function getDisplayName()
    {
        return $this->displayName;
    }

    /**
     * @param string $displayName
     * @return RequestGetAgreementsList
     */
    public function setDisplayName($displayName = '')
    {
        $this->displayName = $displayName;

        return $this;
    }
    /**
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param int $version
     * @return RequestGetAgreementsList
     */
    public function setVersion($version = 0)
    {
        $this->version = $version;

        return $this;
    }
    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string $content
     * @return RequestGetAgreementsList
     */
    public function setContent($content = '')
    {
        $this->content = $content;

        return $this;
    }
    /**
     * @return int
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * @param int $active
     * @return RequestGetAgreementsList
     */
    public function setActive($active = 0)
    {
        $this->active = $active;

        return $this;
    }
    /**
     * @return string
     */
    public function getStartCDate()
    {
        return $this->startCDate;
    }

    /**
     * @param string $startCDate
     * @return RequestGetAgreementsList
     */
    public function setStartCDate($startCDate = '')
    {
        $this->startCDate = $startCDate;

        return $this;
    }
    /**
     * @return string
     */
    public function getEndCDate()
    {
        return $this->endCDate;
    }

    /**
     * @param string $endCDate
     * @return RequestGetAgreementsList
     */
    public function setEndCDate($endCDate = '')
    {
        $this->endCDate = $endCDate;

        return $this;
    }
    /**
     * @return string
     */
    public function getStartMDate()
    {
        return $this->startMDate;
    }

    /**
     * @param string $startMDate
     * @return RequestGetAgreementsList
     */
    public function setStartMDate($startMDate = '')
    {
        $this->startMDate = $startMDate;

        return $this;
    }
    /**
     * @return string
     */
    public function getEndMDate()
    {
        return $this->endMDate;
    }

    /**
     * @param string $endMDate
     * @return RequestGetAgreementsList
     */
    public function setEndMDate($endMDate = '')
    {
        $this->endMDate = $endMDate;

        return $this;
    }

}