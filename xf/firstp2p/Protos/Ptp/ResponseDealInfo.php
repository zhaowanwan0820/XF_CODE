<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 标详情接口
 *
 * 由代码生成器生成, 不可人为修改
 * @author yutao
 */
class ResponseDealInfo extends ProtoBufferBase
{
    /**
     * 标项目ID
     *
     * @var int
     * @required
     */
    private $dealId;

    /**
     * 标项目详情
     *
     * @var array
     * @required
     */
    private $dealInfo;

    /**
     * 标项目简介
     *
     * @var array
     * @optional
     */
    private $projectIntro = NULL;

    /**
     * 借款人信息
     *
     * @var array
     * @optional
     */
    private $dealUserInfo = NULL;

    /**
     * 机构名义贷款信息
     *
     * @var array
     * @optional
     */
    private $company = NULL;

    /**
     * 借款列表
     *
     * @var array
     * @optional
     */
    private $loadList = NULL;

    /**
     * 投资列表页数
     *
     * @var int
     * @optional
     */
    private $totalPage = 0;

    /**
     * 是否满标
     *
     * @var int
     * @optional
     */
    private $isFull = 0;

    /**
     * @return int
     */
    public function getDealId()
    {
        return $this->dealId;
    }

    /**
     * @param int $dealId
     * @return ResponseDealInfo
     */
    public function setDealId($dealId)
    {
        \Assert\Assertion::integer($dealId);

        $this->dealId = $dealId;

        return $this;
    }
    /**
     * @return array
     */
    public function getDealInfo()
    {
        return $this->dealInfo;
    }

    /**
     * @param array $dealInfo
     * @return ResponseDealInfo
     */
    public function setDealInfo(array $dealInfo)
    {
        $this->dealInfo = $dealInfo;

        return $this;
    }
    /**
     * @return array
     */
    public function getProjectIntro()
    {
        return $this->projectIntro;
    }

    /**
     * @param array $projectIntro
     * @return ResponseDealInfo
     */
    public function setProjectIntro(array $projectIntro = NULL)
    {
        $this->projectIntro = $projectIntro;

        return $this;
    }
    /**
     * @return array
     */
    public function getDealUserInfo()
    {
        return $this->dealUserInfo;
    }

    /**
     * @param array $dealUserInfo
     * @return ResponseDealInfo
     */
    public function setDealUserInfo(array $dealUserInfo = NULL)
    {
        $this->dealUserInfo = $dealUserInfo;

        return $this;
    }
    /**
     * @return array
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * @param array $company
     * @return ResponseDealInfo
     */
    public function setCompany(array $company = NULL)
    {
        $this->company = $company;

        return $this;
    }
    /**
     * @return array
     */
    public function getLoadList()
    {
        return $this->loadList;
    }

    /**
     * @param array $loadList
     * @return ResponseDealInfo
     */
    public function setLoadList(array $loadList = NULL)
    {
        $this->loadList = $loadList;

        return $this;
    }
    /**
     * @return int
     */
    public function getTotalPage()
    {
        return $this->totalPage;
    }

    /**
     * @param int $totalPage
     * @return ResponseDealInfo
     */
    public function setTotalPage($totalPage = 0)
    {
        $this->totalPage = $totalPage;

        return $this;
    }
    /**
     * @return int
     */
    public function getIsFull()
    {
        return $this->isFull;
    }

    /**
     * @param int $isFull
     * @return ResponseDealInfo
     */
    public function setIsFull($isFull = 0)
    {
        $this->isFull = $isFull;

        return $this;
    }

}