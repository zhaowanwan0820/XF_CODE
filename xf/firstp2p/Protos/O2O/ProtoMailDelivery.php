<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * o2o:发货信息邮件通知
 *
 * 由代码生成器生成, 不可人为修改
 * @author jinhaidong
 */
class ProtoMailDelivery extends ProtoBufferBase
{
    /**
     * 待发货纪录ID
     *
     * @var int
     * @required
     */
    private $id;

    /**
     * 供应商ID
     *
     * @var int
     * @required
     */
    private $supplierId;

    /**
     * 报表地址
     *
     * @var string
     * @required
     */
    private $attachment;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return ProtoMailDelivery
     */
    public function setId($id)
    {
        \Assert\Assertion::integer($id);

        $this->id = $id;

        return $this;
    }
    /**
     * @return int
     */
    public function getSupplierId()
    {
        return $this->supplierId;
    }

    /**
     * @param int $supplierId
     * @return ProtoMailDelivery
     */
    public function setSupplierId($supplierId)
    {
        \Assert\Assertion::integer($supplierId);

        $this->supplierId = $supplierId;

        return $this;
    }
    /**
     * @return string
     */
    public function getAttachment()
    {
        return $this->attachment;
    }

    /**
     * @param string $attachment
     * @return ProtoMailDelivery
     */
    public function setAttachment($attachment)
    {
        \Assert\Assertion::string($attachment);

        $this->attachment = $attachment;

        return $this;
    }

}