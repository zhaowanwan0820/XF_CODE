<?php
namespace NCFGroup\Protos\Stock;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 协议书内容
 *
 * 由代码生成器生成, 不可人为修改
 * @author zhangzuoyang
 */
class ResponseGetContractInfo extends ResponseBase
{
    /**
     * 业务科目
     *
     * @var string
     * @optional
     */
    private $businessSubjects = '';

    /**
     * 协议标题
     *
     * @var string
     * @optional
     */
    private $title = '';

    /**
     * 协议类型
     *
     * @var string
     * @optional
     */
    private $type = '';

    /**
     * 银行代码
     *
     * @var string
     * @optional
     */
    private $bankCode = '';

    /**
     * 协议页眉
     *
     * @var string
     * @optional
     */
    private $header = '';

    /**
     * 协议内容
     *
     * @var string
     * @optional
     */
    private $content = '';

    /**
     * 协议备注
     *
     * @var string
     * @optional
     */
    private $comment = '';

    /**
     * 协议页脚
     *
     * @var string
     * @optional
     */
    private $footer = '';

    /**
     * 协议版本
     *
     * @var string
     * @optional
     */
    private $version = '';

    /**
     * 阅读时间
     *
     * @var string
     * @optional
     */
    private $readTime = '';

    /**
     * 开通业务
     *
     * @var string
     * @optional
     */
    private $openBusiness = '';

    /**
     * 协议状态
     *
     * @var string
     * @optional
     */
    private $state = '';

    /**
     * 开户显示
     *
     * @var string
     * @optional
     */
    private $openAnAcount = '';

    /**
     * 排序
     *
     * @var string
     * @optional
     */
    private $sort = '';

    /**
     * ID
     *
     * @var string
     * @optional
     */
    private $id = '';

    /**
     * @return string
     */
    public function getBusinessSubjects()
    {
        return $this->businessSubjects;
    }

    /**
     * @param string $businessSubjects
     * @return ResponseGetContractInfo
     */
    public function setBusinessSubjects($businessSubjects = '')
    {
        $this->businessSubjects = $businessSubjects;

        return $this;
    }
    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return ResponseGetContractInfo
     */
    public function setTitle($title = '')
    {
        $this->title = $title;

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
     * @return ResponseGetContractInfo
     */
    public function setType($type = '')
    {
        $this->type = $type;

        return $this;
    }
    /**
     * @return string
     */
    public function getBankCode()
    {
        return $this->bankCode;
    }

    /**
     * @param string $bankCode
     * @return ResponseGetContractInfo
     */
    public function setBankCode($bankCode = '')
    {
        $this->bankCode = $bankCode;

        return $this;
    }
    /**
     * @return string
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * @param string $header
     * @return ResponseGetContractInfo
     */
    public function setHeader($header = '')
    {
        $this->header = $header;

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
     * @return ResponseGetContractInfo
     */
    public function setContent($content = '')
    {
        $this->content = $content;

        return $this;
    }
    /**
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @param string $comment
     * @return ResponseGetContractInfo
     */
    public function setComment($comment = '')
    {
        $this->comment = $comment;

        return $this;
    }
    /**
     * @return string
     */
    public function getFooter()
    {
        return $this->footer;
    }

    /**
     * @param string $footer
     * @return ResponseGetContractInfo
     */
    public function setFooter($footer = '')
    {
        $this->footer = $footer;

        return $this;
    }
    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param string $version
     * @return ResponseGetContractInfo
     */
    public function setVersion($version = '')
    {
        $this->version = $version;

        return $this;
    }
    /**
     * @return string
     */
    public function getReadTime()
    {
        return $this->readTime;
    }

    /**
     * @param string $readTime
     * @return ResponseGetContractInfo
     */
    public function setReadTime($readTime = '')
    {
        $this->readTime = $readTime;

        return $this;
    }
    /**
     * @return string
     */
    public function getOpenBusiness()
    {
        return $this->openBusiness;
    }

    /**
     * @param string $openBusiness
     * @return ResponseGetContractInfo
     */
    public function setOpenBusiness($openBusiness = '')
    {
        $this->openBusiness = $openBusiness;

        return $this;
    }
    /**
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param string $state
     * @return ResponseGetContractInfo
     */
    public function setState($state = '')
    {
        $this->state = $state;

        return $this;
    }
    /**
     * @return string
     */
    public function getOpenAnAcount()
    {
        return $this->openAnAcount;
    }

    /**
     * @param string $openAnAcount
     * @return ResponseGetContractInfo
     */
    public function setOpenAnAcount($openAnAcount = '')
    {
        $this->openAnAcount = $openAnAcount;

        return $this;
    }
    /**
     * @return string
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * @param string $sort
     * @return ResponseGetContractInfo
     */
    public function setSort($sort = '')
    {
        $this->sort = $sort;

        return $this;
    }
    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     * @return ResponseGetContractInfo
     */
    public function setId($id = '')
    {
        $this->id = $id;

        return $this;
    }

}