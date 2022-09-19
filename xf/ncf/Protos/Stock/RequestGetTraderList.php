<?php
namespace NCFGroup\Protos\Stock;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 获取券商列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author libing
 */
class RequestGetTraderList extends AbstractRequestBase
{
    /**
     * 分页
     *
     * @var \NCFGroup\Common\Extensions\Base\Pageable
     * @required
     */
    private $pageable;

    /**
     * 券商名
     *
     * @var string
     * @required
     */
    private $name;

    /**
     * 名称缩写
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
     * @return \NCFGroup\Common\Extensions\Base\Pageable
     */
    public function getPageable()
    {
        return $this->pageable;
    }

    /**
     * @param \NCFGroup\Common\Extensions\Base\Pageable $pageable
     * @return RequestGetTraderList
     */
    public function setPageable(\NCFGroup\Common\Extensions\Base\Pageable $pageable)
    {
        $this->pageable = $pageable;

        return $this;
    }
    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return RequestGetTraderList
     */
    public function setName($name)
    {
        \Assert\Assertion::string($name);

        $this->name = $name;

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
     * @return RequestGetTraderList
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
     * @return RequestGetTraderList
     */
    public function setSource($source)
    {
        \Assert\Assertion::string($source);

        $this->source = $source;

        return $this;
    }

}