<?php
namespace NCFGroup\Protos\Stock;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 获取股票名称信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author shiyan
 */
class RequestGetStockNameInfo extends AbstractRequestBase
{
    /**
     * 前缀
     *
     * @var string
     * @required
     */
    private $keyword;

    /**
     * 版本
     *
     * @var int
     * @optional
     */
    private $version = 331;

    /**
     * @return string
     */
    public function getKeyword()
    {
        return $this->keyword;
    }

    /**
     * @param string $keyword
     * @return RequestGetStockNameInfo
     */
    public function setKeyword($keyword)
    {
        \Assert\Assertion::string($keyword);

        $this->keyword = $keyword;

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
     * @return RequestGetStockNameInfo
     */
    public function setVersion($version = 331)
    {
        $this->version = $version;

        return $this;
    }

}