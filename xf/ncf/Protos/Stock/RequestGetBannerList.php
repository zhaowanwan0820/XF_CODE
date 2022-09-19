<?php
namespace NCFGroup\Protos\Stock;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 获取banner列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author sunqing
 */
class RequestGetBannerList extends AbstractRequestBase
{
    /**
     * 分页
     *
     * @var \NCFGroup\Common\Extensions\Base\Pageable
     * @required
     */
    private $pageable;

    /**
     * banner类型
     *
     * @var int
     * @optional
     */
    private $type = -1;

    /**
     * 是否上线
     *
     * @var int
     * @optional
     */
    private $isDelete = -1;

    /**
     * 起始创建日期
     *
     * @var string
     * @optional
     */
    private $startCDate = '';

    /**
     * 截止创建日期
     *
     * @var string
     * @optional
     */
    private $endCDate = '';

    /**
     * 起始修改日期
     *
     * @var string
     * @optional
     */
    private $startMDate = '';

    /**
     * 截止修改日期
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
     * @return RequestGetBannerList
     */
    public function setPageable(\NCFGroup\Common\Extensions\Base\Pageable $pageable)
    {
        $this->pageable = $pageable;

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
     * @return RequestGetBannerList
     */
    public function setType($type = -1)
    {
        $this->type = $type;

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
     * @return RequestGetBannerList
     */
    public function setIsDelete($isDelete = -1)
    {
        $this->isDelete = $isDelete;

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
     * @return RequestGetBannerList
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
     * @return RequestGetBannerList
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
     * @return RequestGetBannerList
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
     * @return RequestGetBannerList
     */
    public function setEndMDate($endMDate = '')
    {
        $this->endMDate = $endMDate;

        return $this;
    }

}