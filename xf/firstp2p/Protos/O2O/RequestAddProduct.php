<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 添加商品
 *
 * 由代码生成器生成, 不可人为修改
 * @author Yu Tao <yutao@ucfgroup.com>
 */
class RequestAddProduct extends AbstractRequestBase
{
    /**
     * 商品名称
     *
     * @var string
     * @required
     */
    private $productName;

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
     * @return string
     */
    public function getProductName()
    {
        return $this->productName;
    }

    /**
     * @param string $productName
     * @return RequestAddProduct
     */
    public function setProductName($productName)
    {
        \Assert\Assertion::string($productName);

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
     * @return RequestAddProduct
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
     * @return RequestAddProduct
     */
    public function setPic($pic = '')
    {
        $this->pic = $pic;

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
     * @return RequestAddProduct
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
     * @return RequestAddProduct
     */
    public function setPcPic($pcPic = '')
    {
        $this->pcPic = $pcPic;

        return $this;
    }

}