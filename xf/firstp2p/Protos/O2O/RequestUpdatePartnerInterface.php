<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * o2o:更新合作方接口配置
 *
 * 由代码生成器生成, 不可人为修改
 * @author liguizhi
 */
class RequestUpdatePartnerInterface extends ProtoBufferBase
{
    /**
     * id
     *
     * @var string
     * @required
     */
    private $id;

    /**
     * 合作方标志
     *
     * @var string
     * @required
     */
    private $couponProvider;

    /**
     * 公共配置
     *
     * @var string
     * @required
     */
    private $commonConf;

    /**
     * 接口字段映射
     *
     * @var string
     * @required
     */
    private $mapConf;

    /**
     * 表单描述
     *
     * @var string
     * @optional
     */
    private $remark = '';

    /**
     * 是否删除
     *
     * @var int
     * @optional
     */
    private $isDelete = 0;

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     * @return RequestUpdatePartnerInterface
     */
    public function setId($id)
    {
        \Assert\Assertion::string($id);

        $this->id = $id;

        return $this;
    }
    /**
     * @return string
     */
    public function getCouponProvider()
    {
        return $this->couponProvider;
    }

    /**
     * @param string $couponProvider
     * @return RequestUpdatePartnerInterface
     */
    public function setCouponProvider($couponProvider)
    {
        \Assert\Assertion::string($couponProvider);

        $this->couponProvider = $couponProvider;

        return $this;
    }
    /**
     * @return string
     */
    public function getCommonConf()
    {
        return $this->commonConf;
    }

    /**
     * @param string $commonConf
     * @return RequestUpdatePartnerInterface
     */
    public function setCommonConf($commonConf)
    {
        \Assert\Assertion::string($commonConf);

        $this->commonConf = $commonConf;

        return $this;
    }
    /**
     * @return string
     */
    public function getMapConf()
    {
        return $this->mapConf;
    }

    /**
     * @param string $mapConf
     * @return RequestUpdatePartnerInterface
     */
    public function setMapConf($mapConf)
    {
        \Assert\Assertion::string($mapConf);

        $this->mapConf = $mapConf;

        return $this;
    }
    /**
     * @return string
     */
    public function getRemark()
    {
        return $this->remark;
    }

    /**
     * @param string $remark
     * @return RequestUpdatePartnerInterface
     */
    public function setRemark($remark = '')
    {
        $this->remark = $remark;

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
     * @return RequestUpdatePartnerInterface
     */
    public function setIsDelete($isDelete = 0)
    {
        $this->isDelete = $isDelete;

        return $this;
    }

}