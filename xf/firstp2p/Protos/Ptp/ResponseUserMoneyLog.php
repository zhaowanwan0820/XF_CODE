<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 资金纪录proto
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangfei5
 */
class ResponseUserMoneyLog extends ProtoBufferBase
{
    /**
     * 资金纪录列表
     *
     * @var array
     * @required
     */
    private $list;

    /**
     * 纪录偏移量
     *
     * @var int
     * @optional
     */
    private $offset = 0;

    /**
     * 纪录数量
     *
     * @var int
     * @optional
     */
    private $count = 20;

    /**
     * 资金记录筛选的类型
     *
     * @var array
     * @optional
     */
    private $logType = NULL;

    /**
     * @return array
     */
    public function getList()
    {
        return $this->list;
    }

    /**
     * @param array $list
     * @return ResponseUserMoneyLog
     */
    public function setList(array $list)
    {
        $this->list = $list;

        return $this;
    }
    /**
     * @return int
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * @param int $offset
     * @return ResponseUserMoneyLog
     */
    public function setOffset($offset = 0)
    {
        $this->offset = $offset;

        return $this;
    }
    /**
     * @return int
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * @param int $count
     * @return ResponseUserMoneyLog
     */
    public function setCount($count = 20)
    {
        $this->count = $count;

        return $this;
    }
    /**
     * @return array
     */
    public function getLogType()
    {
        return $this->logType;
    }

    /**
     * @param array $logType
     * @return ResponseUserMoneyLog
     */
    public function setLogType(array $logType = NULL)
    {
        $this->logType = $logType;

        return $this;
    }

}