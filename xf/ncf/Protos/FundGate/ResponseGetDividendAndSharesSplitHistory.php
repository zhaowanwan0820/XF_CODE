<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 获取基金分红及拆分折算历史数据
 *
 * 由代码生成器生成, 不可人为修改
 * @author fupingzhou
 */
class ResponseGetDividendAndSharesSplitHistory extends ResponseBase
{
    /**
     * 数据列表
     *
     * @var array
     * @required
     */
    private $list;

    /**
     * 页数
     *
     * @var int
     * @required
     */
    private $pages;

    /**
     * @return array
     */
    public function getList()
    {
        return $this->list;
    }

    /**
     * @param array $list
     * @return ResponseGetDividendAndSharesSplitHistory
     */
    public function setList(array $list)
    {
        $this->list = $list;

        return $this;
    }
    /**
     * @return int
     */
    public function getPages()
    {
        return $this->pages;
    }

    /**
     * @param int $pages
     * @return ResponseGetDividendAndSharesSplitHistory
     */
    public function setPages($pages)
    {
        \Assert\Assertion::integer($pages);

        $this->pages = $pages;

        return $this;
    }

}