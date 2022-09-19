<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * o2o:商品信息Proto
 *
 * 由代码生成器生成, 不可人为修改
 * @author yutao
 */
class ProtoProduct extends ProtoBufferBase
{
    /**
     * 商品ID
     *
     * @var int
     * @required
     */
    private $id;

    /**
     * 商品名
     *
     * @var string
     * @optional
     */
    private $productName = '';

    /**
     * 商品描述
     *
     * @var string
     * @optional
     */
    private $productDesc = '';

    /**
     * APP&WAP上商品缩略图
     *
     * @var string
     * @optional
     */
    private $pic = '';

    /**
     * 创建时间
     *
     * @var int
     * @optional
     */
    private $createTime = '';

    /**
     * 最后修改时间
     *
     * @var int
     * @optional
     */
    private $updateTime = '';

    /**
     * 合作方商品ID
     *
     * @var string
     * @optional
     */
    private $partnerProductId = '';

    /**
     * PC上商品缩略图
     *
     * @var string
     * @optional
     */
    private $pcPic = '';

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return ProtoProduct
     */
    public function setId($id)
    {
        \Assert\Assertion::integer($id);

        $this->id = $id;

        return $this;
    }
    /**
     * @return string
     */
    public function getProductName()
    {
        return $this->productName;
    }

    /**
     * @param string $productName
     * @return ProtoProduct
     */
    public function setProductName($productName = '')
    {
        $this->productName = $productName;

        return $this;
    }
    /**
     * @return string
     */
    public function getProductDesc()
    {
        return $this->productDesc;
    }

    /**
     * @param string $productDesc
     * @return ProtoProduct
     */
    public function setProductDesc($productDesc = '')
    {
        $this->productDesc = $productDesc;

        return $this;
    }
    /**
     * @return string
     */
    public function getPic()
    {
        return $this->pic;
    }

    /**
     * @param string $pic
     * @return ProtoProduct
     */
    public function setPic($pic = '')
    {
        $this->pic = $pic;

        return $this;
    }
    /**
     * @return int
     */
    public function getCreateTime()
    {
        return $this->createTime;
    }

    /**
     * @param int $createTime
     * @return ProtoProduct
     */
    public function setCreateTime($createTime = '')
    {
        $this->createTime = $createTime;

        return $this;
    }
    /**
     * @return int
     */
    public function getUpdateTime()
    {
        return $this->updateTime;
    }

    /**
     * @param int $updateTime
     * @return ProtoProduct
     */
    public function setUpdateTime($updateTime = '')
    {
        $this->updateTime = $updateTime;

        return $this;
    }
    /**
     * @return string
     */
    public function getPartnerProductId()
    {
        return $this->partnerProductId;
    }

    /**
     * @param string $partnerProductId
     * @return ProtoProduct
     */
    public function setPartnerProductId($partnerProductId = '')
    {
        $this->partnerProductId = $partnerProductId;

        return $this;
    }
    /**
     * @return string
     */
    public function getPcPic()
    {
        return $this->pcPic;
    }

    /**
     * @param string $pcPic
     * @return ProtoProduct
     */
    public function setPcPic($pcPic = '')
    {
        $this->pcPic = $pcPic;

        return $this;
    }

}