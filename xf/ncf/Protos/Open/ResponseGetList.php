<?php
namespace NCFGroup\Protos\Open;

use NCFGroup\Common\Extensions\Base\ResponseBase;
use Assert\Assertion;

/**
 * 获取列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author Yu TAO <yutao@ucfgroup.com>
 */
class ResponseGetList extends ResponseBase
{
    /**
     * 分页信息
     *
     * @var array
     * @optional
     */
    private $page = NULL;

    /**
     * 列表数据
     *
     * @var array
     * @optional
     */
    private $list = NULL;

    /**
     * @return array
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * @param array $page
     * @return ResponseGetList
     */
    public function setPage(array $page = NULL)
    {
        $this->page = $page;

        return $this;
    }
    /**
     * @return array
     */
    public function getList()
    {
        return $this->list;
    }

    /**
     * @param array $list
     * @return ResponseGetList
     */
    public function setList(array $list = NULL)
    {
        $this->list = $list;

        return $this;
    }

}