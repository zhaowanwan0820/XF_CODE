<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 通用的获取列表数据的数据项
 *
 * 由代码生成器生成, 不可人为修改
 * @author <yanbingrong@ucfgroup.com>
 */
class ProtoItem extends ProtoBufferBase
{
    /**
     * 具体数据内容，这里用通用的关联数组array
     *
     * @var array
     * @required
     */
    private $data;

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param array $data
     * @return ProtoItem
     */
    public function setData(array $data)
    {
        $this->data = $data;

        return $this;
    }

}